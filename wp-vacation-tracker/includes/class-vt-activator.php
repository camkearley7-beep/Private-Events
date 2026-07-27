<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs once on plugin activation: creates tables, registers roles, seeds default settings.
 */
class VT_Activator {

	public static function activate() {
		self::create_tables();
		self::register_roles();
		self::seed_settings();
		update_option( 'vt_db_version', VT_VERSION );
		VT_Cron::schedule_events();
	}

	private static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = array();

		$sql[] = "CREATE TABLE " . VT_DB::departments() . " (
			department_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL,
			manager_employee_id BIGINT UNSIGNED NULL,
			default_approver_id BIGINT UNSIGNED NULL,
			backup_approver_id BIGINT UNSIGNED NULL,
			min_staffing INT NULL,
			max_away INT NULL,
			active TINYINT(1) NOT NULL DEFAULT 1,
			PRIMARY KEY  (department_id),
			KEY active (active)
		) $charset_collate;";

		$sql[] = "CREATE TABLE " . VT_DB::employees() . " (
			employee_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			wp_user_id BIGINT UNSIGNED NOT NULL,
			first_name VARCHAR(100) NOT NULL,
			last_name VARCHAR(100) NOT NULL,
			work_email VARCHAR(191) NOT NULL,
			employment_status VARCHAR(20) NOT NULL DEFAULT 'active',
			hire_date DATE NULL,
			termination_date DATE NULL,
			department_id BIGINT UNSIGNED NULL,
			team VARCHAR(100) NULL,
			job_title VARCHAR(150) NULL,
			primary_team_lead_id BIGINT UNSIGNED NULL,
			secondary_approver_id BIGINT UNSIGNED NULL,
			hr_administrator_id BIGINT UNSIGNED NULL,
			employment_type VARCHAR(20) NOT NULL DEFAULT 'full_time',
			work_schedule VARCHAR(20) NOT NULL DEFAULT 'standard',
			standard_workdays VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5',
			hours_per_workday DECIMAL(4,2) NOT NULL DEFAULT 8.00,
			vacation_entitlement DECIMAL(5,2) NOT NULL DEFAULT 15.00,
			carry_forward DECIMAL(5,2) NOT NULL DEFAULT 0.00,
			location VARCHAR(150) NULL,
			jurisdiction VARCHAR(100) NULL,
			is_executive TINYINT(1) NOT NULL DEFAULT 0,
			active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (employee_id),
			UNIQUE KEY wp_user_id (wp_user_id),
			KEY department_id (department_id),
			KEY primary_team_lead_id (primary_team_lead_id),
			KEY active (active)
		) $charset_collate;";

		$sql[] = "CREATE TABLE " . VT_DB::requests() . " (
			request_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			request_code VARCHAR(40) NOT NULL,
			employee_id BIGINT UNSIGNED NOT NULL,
			leave_type VARCHAR(20) NOT NULL DEFAULT 'vacation',
			start_date DATE NOT NULL,
			end_date DATE NOT NULL,
			start_duration VARCHAR(10) NOT NULL DEFAULT 'full',
			end_duration VARCHAR(10) NOT NULL DEFAULT 'full',
			total_days DECIMAL(5,2) NOT NULL DEFAULT 0,
			total_hours DECIMAL(6,2) NOT NULL DEFAULT 0,
			employee_comments TEXT NULL,
			status VARCHAR(30) NOT NULL DEFAULT 'draft',
			flags VARCHAR(255) NULL,
			date_submitted DATETIME NULL,
			current_stage VARCHAR(20) NULL,
			stage_entered_at DATETIME NULL,
			assigned_approver_id BIGINT UNSIGNED NULL,
			approval_date DATETIME NULL,
			decision VARCHAR(20) NULL,
			approver_comments TEXT NULL,
			cancellation_status VARCHAR(30) NOT NULL DEFAULT 'none',
			pending_applied TINYINT(1) NOT NULL DEFAULT 0,
			balance_applied TINYINT(1) NOT NULL DEFAULT 0,
			created_by BIGINT UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (request_id),
			UNIQUE KEY request_code (request_code),
			KEY employee_id (employee_id),
			KEY status (status),
			KEY assigned_approver_id (assigned_approver_id)
		) $charset_collate;";

		$sql[] = "CREATE TABLE " . VT_DB::balances() . " (
			balance_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			employee_id BIGINT UNSIGNED NOT NULL,
			vacation_year INT NOT NULL,
			annual_entitlement DECIMAL(5,2) NOT NULL DEFAULT 0,
			carry_forward DECIMAL(5,2) NOT NULL DEFAULT 0,
			manual_credits DECIMAL(5,2) NOT NULL DEFAULT 0,
			manual_deductions DECIMAL(5,2) NOT NULL DEFAULT 0,
			approved_days_used DECIMAL(5,2) NOT NULL DEFAULT 0,
			pending_days DECIMAL(5,2) NOT NULL DEFAULT 0,
			remaining_days DECIMAL(6,2) NOT NULL DEFAULT 0,
			carry_forward_expiry DATE NULL,
			is_closed TINYINT(1) NOT NULL DEFAULT 0,
			last_recalculated DATETIME NULL,
			PRIMARY KEY  (balance_id),
			UNIQUE KEY employee_year (employee_id, vacation_year)
		) $charset_collate;";

		$sql[] = "CREATE TABLE " . VT_DB::holidays() . " (
			holiday_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(150) NOT NULL,
			holiday_date DATE NOT NULL,
			jurisdiction VARCHAR(100) NOT NULL DEFAULT 'all',
			paid TINYINT(1) NOT NULL DEFAULT 1,
			active TINYINT(1) NOT NULL DEFAULT 1,
			excluded_from_calc TINYINT(1) NOT NULL DEFAULT 1,
			PRIMARY KEY  (holiday_id),
			KEY holiday_date (holiday_date),
			KEY jurisdiction (jurisdiction)
		) $charset_collate;";

		$sql[] = "CREATE TABLE " . VT_DB::blackout_periods() . " (
			blackout_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(150) NOT NULL,
			start_date DATE NOT NULL,
			end_date DATE NOT NULL,
			department_id BIGINT UNSIGNED NULL,
			location VARCHAR(150) NULL,
			reason TEXT NULL,
			mode VARCHAR(10) NOT NULL DEFAULT 'warn',
			exception_approver_id BIGINT UNSIGNED NULL,
			active TINYINT(1) NOT NULL DEFAULT 1,
			PRIMARY KEY  (blackout_id),
			KEY start_date (start_date),
			KEY end_date (end_date)
		) $charset_collate;";

		$sql[] = "CREATE TABLE " . VT_DB::delegations() . " (
			delegation_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			original_approver_id BIGINT UNSIGNED NOT NULL,
			delegate_approver_id BIGINT UNSIGNED NOT NULL,
			start_date DATE NOT NULL,
			end_date DATE NOT NULL,
			scope_note VARCHAR(255) NULL,
			active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (delegation_id),
			KEY original_approver_id (original_approver_id),
			KEY dates (start_date, end_date)
		) $charset_collate;";

		$sql[] = "CREATE TABLE " . VT_DB::adjustments() . " (
			adjustment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			employee_id BIGINT UNSIGNED NOT NULL,
			vacation_year INT NOT NULL,
			adjustment_type VARCHAR(20) NOT NULL,
			amount DECIMAL(5,2) NOT NULL,
			reason TEXT NOT NULL,
			admin_wp_user_id BIGINT UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL,
			notes TEXT NULL,
			PRIMARY KEY  (adjustment_id),
			KEY employee_id (employee_id, vacation_year)
		) $charset_collate;";

		$sql[] = "CREATE TABLE " . VT_DB::audit_log() . " (
			audit_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			record_type VARCHAR(40) NOT NULL,
			record_id VARCHAR(60) NOT NULL,
			action VARCHAR(60) NOT NULL,
			previous_value LONGTEXT NULL,
			new_value LONGTEXT NULL,
			performed_by VARCHAR(150) NOT NULL,
			created_at DATETIME NOT NULL,
			workflow_ref VARCHAR(100) NULL,
			comments TEXT NULL,
			PRIMARY KEY  (audit_id),
			KEY record_type (record_type),
			KEY record_id (record_id),
			KEY created_at (created_at)
		) $charset_collate;";

		$sql[] = "CREATE TABLE " . VT_DB::settings() . " (
			setting_key VARCHAR(100) NOT NULL,
			setting_value TEXT NULL,
			data_type VARCHAR(20) NOT NULL DEFAULT 'text',
			description VARCHAR(255) NULL,
			PRIMARY KEY  (setting_key)
		) $charset_collate;";

		$sql[] = "CREATE TABLE " . VT_DB::error_log() . " (
			error_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			source VARCHAR(100) NOT NULL,
			message TEXT NOT NULL,
			context LONGTEXT NULL,
			resolved TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (error_id),
			KEY resolved (resolved)
		) $charset_collate;";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}

	private static function register_roles() {
		// Base employee role: everyone who uses the portal.
		add_role(
			'vt_employee',
			__( 'Vacation Employee', 'vacation-tracker' ),
			array(
				'read'             => true,
				'vt_use_portal'    => true,
			)
		);

		// Manager: has everything an employee has, plus team approval capability.
		add_role(
			'vt_manager',
			__( 'Vacation Manager', 'vacation-tracker' ),
			array(
				'read'           => true,
				'vt_use_portal'  => true,
				'vt_manage_team' => true,
			)
		);

		// HR Admin: full back-end access (business authority, not technical/server authority).
		add_role(
			'vt_hr_admin',
			__( 'Vacation HR Admin', 'vacation-tracker' ),
			array(
				'read'          => true,
				'vt_use_portal' => true,
				'vt_manage_all' => true,
			)
		);

		// Make sure existing WordPress Administrators can always reach the HR back end
		// (System Administrator role, per the blueprint, is the WP Administrator account itself).
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->add_cap( 'vt_manage_all' );
			$admin_role->add_cap( 'vt_manage_team' );
			$admin_role->add_cap( 'vt_use_portal' );
		}
	}

	private static function seed_settings() {
		$defaults = array(
			'approval_reminder_days_1'        => array( '2', 'number', 'Business days before first approval reminder' ),
			'approval_reminder_days_2'        => array( '4', 'number', 'Business days before second approval reminder' ),
			'approval_escalation_days'        => array( '5', 'number', 'Business days before escalation' ),
			'max_carry_forward_days'          => array( '5', 'number', 'Maximum carry-forward days permitted' ),
			'carry_forward_expiry_month_day'  => array( '06-30', 'text', 'MM-DD carry-forward expiry each year' ),
			'default_workday_hours'           => array( '8', 'number', 'Default hours per workday' ),
			'hr_notification_email'           => array( get_option( 'admin_email' ), 'email', 'HR notification address' ),
			'system_admin_notification_email' => array( get_option( 'admin_email' ), 'email', 'Technical/system admin notification address' ),
			'email_sender_name'               => array( get_bloginfo( 'name' ) . ' Vacation Tracker', 'text', 'Email "From" display name' ),
			'allow_over_balance_requests'     => array( 'yes', 'boolean', 'Allow submission over balance, routed to HR' ),
			'long_request_threshold_days'     => array( '5', 'number', 'Working days at/above which Department Manager approval is required' ),
			'staffing_conflict_check_enabled' => array( 'yes', 'boolean', 'Warn on department staffing conflicts' ),
			'cancellation_after_start_needs_hr' => array( 'yes', 'boolean', 'Cancellations after the vacation has started require HR' ),
			'date_format'                     => array( 'd M Y', 'text', 'Display date format (PHP date() format)' ),
			'timezone'                        => array( wp_timezone_string(), 'text', 'Timezone identifier' ),
		);

		foreach ( $defaults as $key => $data ) {
			VT_Settings::set_if_missing( $key, $data[0], $data[1], $data[2] );
		}
	}
}
