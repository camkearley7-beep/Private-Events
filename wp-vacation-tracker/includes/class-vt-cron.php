<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scheduled tasks (blueprint Section 10, flows F6/F7).
 *
 * IMPORTANT (see README): WordPress's default "cron" only runs when a
 * visitor loads a page. For a reliable daily reminder/escalation/
 * reconciliation job, set up a real server-side cron hitting wp-cron.php
 * (or use a "real cron" plugin) - this file just registers the events.
 */
class VT_Cron {

	public static function init() {
		add_action( 'vt_daily_check', array( __CLASS__, 'run_daily_check' ) );
		add_action( 'vt_nightly_reconciliation', array( __CLASS__, 'run_nightly_reconciliation' ) );
	}

	public static function schedule_events() {
		if ( ! wp_next_scheduled( 'vt_daily_check' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 7:00am' ), 'daily', 'vt_daily_check' );
		}
		if ( ! wp_next_scheduled( 'vt_nightly_reconciliation' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 1:00am' ), 'daily', 'vt_nightly_reconciliation' );
		}
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'vt_daily_check' );
		wp_clear_scheduled_hook( 'vt_nightly_reconciliation' );
	}

	public static function run_nightly_reconciliation() {
		VT_Balances::reconcile_all();
	}

	public static function run_daily_check() {
		self::process_reminders_and_escalation();
		self::send_upcoming_vacation_reminders();
		self::send_carry_forward_expiry_reminders();
	}

	private static function process_reminders_and_escalation() {
		global $wpdb;
		$statuses = "'" . implode( "','", array( 'pending_team_lead', 'pending_department', 'pending_hr' ) ) . "'";
		$requests = $wpdb->get_results( "SELECT * FROM " . VT_DB::requests() . " WHERE status IN ($statuses)" );

		$reminder1_days   = VT_Settings::get_number( 'approval_reminder_days_1', 2 );
		$reminder2_days   = VT_Settings::get_number( 'approval_reminder_days_2', 4 );
		$escalation_days  = VT_Settings::get_number( 'approval_escalation_days', 5 );

		foreach ( $requests as $request ) {
			$flags = json_decode( $request->flags, true );
			if ( ! is_array( $flags ) ) {
				$flags = array();
			}
			$business_days = VT_Calc::business_days_since( $request->stage_entered_at );
			$employee      = VT_Balances::get_employee( $request->employee_id );
			$approver      = VT_Balances::get_employee( $request->assigned_approver_id );
			if ( ! $employee || ! $approver ) {
				continue;
			}

			if ( $business_days >= $escalation_days && empty( $flags['escalated'] ) ) {
				$new_approver_id = self::find_escalation_target( $employee, $request->current_stage, $approver->employee_id );
				if ( $new_approver_id && (int) $new_approver_id !== (int) $approver->employee_id ) {
					$new_approver = VT_Balances::get_employee( $new_approver_id );
					$wpdb->update(
						VT_DB::requests(),
						array( 'assigned_approver_id' => $new_approver_id ),
						array( 'request_id' => $request->request_id )
					);
					$flags['escalated'] = true;
					$wpdb->update( VT_DB::requests(), array( 'flags' => wp_json_encode( $flags ) ), array( 'request_id' => $request->request_id ) );
					VT_Audit::log( 'Request', $request->request_code, 'Escalated', array( 'from' => $approver->employee_id ), array( 'to' => $new_approver_id ), 'System: Escalation' );
					if ( $new_approver ) {
						VT_Notifications::escalation( $new_approver, $approver, $employee, $request );
					}
				}
				continue;
			}

			if ( $business_days >= $reminder2_days && empty( $flags['reminder2_sent'] ) ) {
				VT_Notifications::reminder( $approver, $employee, $request, 2 );
				$flags['reminder2_sent'] = true;
				$wpdb->update( VT_DB::requests(), array( 'flags' => wp_json_encode( $flags ) ), array( 'request_id' => $request->request_id ) );
			} elseif ( $business_days >= $reminder1_days && empty( $flags['reminder1_sent'] ) ) {
				VT_Notifications::reminder( $approver, $employee, $request, 1 );
				$flags['reminder1_sent'] = true;
				$wpdb->update( VT_DB::requests(), array( 'flags' => wp_json_encode( $flags ) ), array( 'request_id' => $request->request_id ) );
			}
		}
	}

	private static function find_escalation_target( $employee, $stage, $current_approver_id ) {
		global $wpdb;
		if ( $employee->department_id ) {
			$dept = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . VT_DB::departments() . " WHERE department_id = %d", $employee->department_id ) );
			if ( $dept && $dept->backup_approver_id && (int) $dept->backup_approver_id !== (int) $current_approver_id ) {
				return $dept->backup_approver_id;
			}
			if ( $dept && $dept->manager_employee_id && (int) $dept->manager_employee_id !== (int) $current_approver_id ) {
				return $dept->manager_employee_id;
			}
		}
		if ( $employee->secondary_approver_id && (int) $employee->secondary_approver_id !== (int) $current_approver_id ) {
			return $employee->secondary_approver_id;
		}
		$hr_users = get_users( array( 'role' => 'vt_hr_admin', 'fields' => 'ID' ) );
		foreach ( $hr_users as $user_id ) {
			$emp = VT_Requests::get_employee_by_wp_user( $user_id );
			if ( $emp && (int) $emp->employee_id !== (int) $current_approver_id ) {
				return $emp->employee_id;
			}
		}
		return null;
	}

	private static function send_upcoming_vacation_reminders() {
		global $wpdb;
		$target_date = date( 'Y-m-d', strtotime( '+3 days' ) );
		$requests = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM " . VT_DB::requests() . " WHERE status = 'approved' AND start_date = %s AND flags NOT LIKE %s",
				$target_date,
				'%upcoming_reminder_sent%'
			)
		);
		foreach ( $requests as $request ) {
			$employee = VT_Balances::get_employee( $request->employee_id );
			if ( ! $employee ) {
				continue;
			}
			VT_Notifications::upcoming_vacation_reminder( $employee, $request );
			$flags = json_decode( $request->flags, true );
			$flags = is_array( $flags ) ? $flags : array();
			$flags['upcoming_reminder_sent'] = true;
			$wpdb->update( VT_DB::requests(), array( 'flags' => wp_json_encode( $flags ) ), array( 'request_id' => $request->request_id ) );
		}
	}

	private static function send_carry_forward_expiry_reminders() {
		global $wpdb;
		$year = VT_Calc::current_vacation_year();
		foreach ( array( 30, 14, 7 ) as $days_before ) {
			$target_date = date( 'Y-m-d', strtotime( "+{$days_before} days" ) );
			$balances = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM " . VT_DB::balances() . " WHERE vacation_year = %d AND carry_forward > 0 AND carry_forward_expiry = %s",
					$year,
					$target_date
				)
			);
			foreach ( $balances as $balance ) {
				$employee = VT_Balances::get_employee( $balance->employee_id );
				if ( $employee ) {
					VT_Notifications::carry_forward_expiry_reminder( $employee, $balance->carry_forward, $balance->carry_forward_expiry );
				}
			}
		}
	}
}
