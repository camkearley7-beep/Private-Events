<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The wp-admin "back end" (blueprint's HR/Vacation Administrator dashboard):
 * employees, departments, holidays, blackout periods, all requests (with
 * override), balance adjustments, delegations, settings, audit log, annual
 * rollover, and a system/error log. Gated to the vt_manage_all capability
 * (HR Admins and WordPress Administrators) - never shown to Employee/Manager
 * roles, and every write below goes through a nonce + capability check
 * server-side, not just a hidden menu item.
 */
class VT_Admin {

	const CAP = 'vt_manage_all';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'process_forms' ) );
	}

	public static function register_menu() {
		add_menu_page( 'Vacation Tracker', 'Vacation Tracker', self::CAP, 'vt-dashboard', array( __CLASS__, 'page_dashboard' ), 'dashicons-palmtree', 30 );
		add_submenu_page( 'vt-dashboard', 'Dashboard', 'Dashboard', self::CAP, 'vt-dashboard', array( __CLASS__, 'page_dashboard' ) );
		add_submenu_page( 'vt-dashboard', 'Employees', 'Employees', self::CAP, 'vt-employees', array( __CLASS__, 'page_employees' ) );
		add_submenu_page( 'vt-dashboard', 'Departments', 'Departments', self::CAP, 'vt-departments', array( __CLASS__, 'page_departments' ) );
		add_submenu_page( 'vt-dashboard', 'Holidays', 'Holidays', self::CAP, 'vt-holidays', array( __CLASS__, 'page_holidays' ) );
		add_submenu_page( 'vt-dashboard', 'Blackout Periods', 'Blackout Periods', self::CAP, 'vt-blackout', array( __CLASS__, 'page_blackout' ) );
		add_submenu_page( 'vt-dashboard', 'All Requests', 'All Requests', self::CAP, 'vt-requests', array( __CLASS__, 'page_requests' ) );
		add_submenu_page( 'vt-dashboard', 'Balance Adjustments', 'Balance Adjustments', self::CAP, 'vt-adjustments', array( __CLASS__, 'page_adjustments' ) );
		add_submenu_page( 'vt-dashboard', 'Delegations', 'Delegations', self::CAP, 'vt-delegations', array( __CLASS__, 'page_delegations' ) );
		add_submenu_page( 'vt-dashboard', 'Annual Rollover', 'Annual Rollover', self::CAP, 'vt-rollover', array( __CLASS__, 'page_rollover' ) );
		add_submenu_page( 'vt-dashboard', 'Audit Log', 'Audit Log', self::CAP, 'vt-audit', array( __CLASS__, 'page_audit' ) );
		add_submenu_page( 'vt-dashboard', 'System Log', 'System Log', self::CAP, 'vt-system-log', array( __CLASS__, 'page_system_log' ) );
		add_submenu_page( 'vt-dashboard', 'Settings', 'Settings', self::CAP, 'vt-settings', array( __CLASS__, 'page_settings' ) );
	}

	private static function redirect( $page, $msg = '', $type = 'success' ) {
		$url = add_query_arg( array( 'page' => $page, 'vt_msg' => rawurlencode( $msg ), 'vt_msg_type' => $type ), admin_url( 'admin.php' ) );
		wp_safe_redirect( $url );
		exit;
	}

	private static function notice() {
		if ( empty( $_GET['vt_msg'] ) ) {
			return;
		}
		$type = ( isset( $_GET['vt_msg_type'] ) && 'error' === $_GET['vt_msg_type'] ) ? 'error' : 'success';
		echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( wp_unslash( rawurldecode( $_GET['vt_msg'] ) ) ) . '</p></div>';
	}

	/** Dispatch every admin form POST from a single admin_init hook, so redirects happen before any HTML is sent. */
	public static function process_forms() {
		if ( empty( $_POST['vt_action'] ) || ! current_user_can( self::CAP ) ) {
			return;
		}
		$action = sanitize_key( $_POST['vt_action'] );

		switch ( $action ) {
			case 'add_employee': self::handle_add_employee(); break;
			case 'edit_employee': self::handle_edit_employee(); break;
			case 'deactivate_employee': self::handle_deactivate_employee(); break;
			case 'save_department': self::handle_save_department(); break;
			case 'save_holiday': self::handle_save_holiday(); break;
			case 'delete_holiday': self::handle_delete_holiday(); break;
			case 'save_blackout': self::handle_save_blackout(); break;
			case 'delete_blackout': self::handle_delete_blackout(); break;
			case 'override_decision': self::handle_override_decision(); break;
			case 'add_adjustment': self::handle_add_adjustment(); break;
			case 'add_delegation': self::handle_add_delegation(); break;
			case 'run_rollover': self::handle_run_rollover(); break;
			case 'save_settings': self::handle_save_settings(); break;
		}
	}

	/* ---------------------------------------------------------------- */
	/* Dashboard                                                         */
	/* ---------------------------------------------------------------- */

	public static function page_dashboard() {
		global $wpdb;
		$pending_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . VT_DB::requests() . " WHERE status IN ('pending_team_lead','pending_department','pending_hr','cancellation_pending')" );
		$exceptions    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . VT_DB::requests() . " WHERE status IN ('validation_failed','system_error')" );
		$errors        = (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . VT_DB::error_log() . " WHERE resolved = 0" );
		$employees     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . VT_DB::employees() . " WHERE active = 1" );
		?>
		<div class="wrap">
			<h1>Vacation Tracker</h1>
			<?php self::notice(); ?>
			<div class="vt-admin-tiles" style="display:flex;gap:16px;margin-top:16px;flex-wrap:wrap;">
				<div class="card" style="padding:16px;min-width:160px;"><h2 style="margin:0;font-size:28px;"><?php echo esc_html( $pending_count ); ?></h2><p>Pending approvals org-wide</p></div>
				<div class="card" style="padding:16px;min-width:160px;"><h2 style="margin:0;font-size:28px;"><?php echo esc_html( $exceptions ); ?></h2><p>Exceptions needing HR attention</p></div>
				<div class="card" style="padding:16px;min-width:160px;"><h2 style="margin:0;font-size:28px;"><?php echo esc_html( $errors ); ?></h2><p>Unresolved workflow errors</p></div>
				<div class="card" style="padding:16px;min-width:160px;"><h2 style="margin:0;font-size:28px;"><?php echo esc_html( $employees ); ?></h2><p>Active employees</p></div>
			</div>
			<p style="margin-top:20px;">Add the <code>[vt_app]</code> shortcode to a page for the employee/manager portal.</p>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------- */
	/* Employees                                                         */
	/* ---------------------------------------------------------------- */

	private static function handle_add_employee() {
		check_admin_referer( 'vt_add_employee' );
		global $wpdb;

		$email = sanitize_email( $_POST['work_email'] );
		if ( ! is_email( $email ) ) {
			self::redirect( 'vt-employees', 'Please provide a valid work email.', 'error' );
		}

		$existing_user_id = absint( $_POST['existing_user_id'] ?? 0 );
		if ( $existing_user_id ) {
			$wp_user_id = $existing_user_id;
		} else {
			$login = sanitize_user( current( explode( '@', $email ) ) . '.' . wp_generate_password( 4, false ) );
			$wp_user_id = wp_insert_user(
				array(
					'user_login' => $login,
					'user_email' => $email,
					'user_pass'  => wp_generate_password( 20 ),
					'first_name' => sanitize_text_field( $_POST['first_name'] ),
					'last_name'  => sanitize_text_field( $_POST['last_name'] ),
					'role'       => 'vt_employee',
				)
			);
			if ( is_wp_error( $wp_user_id ) ) {
				self::redirect( 'vt-employees', 'Could not create account: ' . $wp_user_id->get_error_message(), 'error' );
			}
			wp_new_user_notification( $wp_user_id, null, 'user' ); // Emails the employee a "set your password" link.
		}

		$user = get_userdata( $wp_user_id );
		if ( ! empty( $_POST['role_manager'] ) ) {
			$user->add_role( 'vt_manager' );
		}
		if ( ! empty( $_POST['role_hr_admin'] ) ) {
			$user->add_role( 'vt_hr_admin' );
		}

		$workdays = isset( $_POST['standard_workdays'] ) ? array_map( 'absint', (array) $_POST['standard_workdays'] ) : array( 1, 2, 3, 4, 5 );

		$now = current_time( 'mysql' );
		$wpdb->insert(
			VT_DB::employees(),
			array(
				'wp_user_id'            => $wp_user_id,
				'first_name'            => sanitize_text_field( $_POST['first_name'] ),
				'last_name'             => sanitize_text_field( $_POST['last_name'] ),
				'work_email'            => $email,
				'employment_status'     => 'active',
				'hire_date'             => sanitize_text_field( $_POST['hire_date'] ?: $now ),
				'department_id'         => absint( $_POST['department_id'] ) ?: null,
				'team'                  => sanitize_text_field( $_POST['team'] ),
				'job_title'             => sanitize_text_field( $_POST['job_title'] ),
				'primary_team_lead_id'  => absint( $_POST['primary_team_lead_id'] ) ?: null,
				'secondary_approver_id' => absint( $_POST['secondary_approver_id'] ) ?: null,
				'hr_administrator_id'   => absint( $_POST['hr_administrator_id'] ) ?: null,
				'employment_type'       => sanitize_text_field( $_POST['employment_type'] ),
				'work_schedule'         => sanitize_text_field( $_POST['work_schedule'] ),
				'standard_workdays'     => implode( ',', $workdays ),
				'hours_per_workday'     => floatval( $_POST['hours_per_workday'] ),
				'vacation_entitlement'  => floatval( $_POST['vacation_entitlement'] ),
				'carry_forward'         => floatval( $_POST['carry_forward'] ),
				'location'              => sanitize_text_field( $_POST['location'] ),
				'jurisdiction'          => sanitize_text_field( $_POST['jurisdiction'] ),
				'is_executive'          => ! empty( $_POST['is_executive'] ) ? 1 : 0,
				'active'                => 1,
				'created_at'            => $now,
				'updated_at'            => $now,
			)
		);

		VT_Audit::log( 'Employee', $wpdb->insert_id, 'Created', null, array( 'email' => $email ), null );
		self::redirect( 'vt-employees', 'Employee added.' );
	}

	private static function handle_edit_employee() {
		check_admin_referer( 'vt_edit_employee' );
		global $wpdb;
		$employee_id = absint( $_POST['employee_id'] );
		$workdays = isset( $_POST['standard_workdays'] ) ? array_map( 'absint', (array) $_POST['standard_workdays'] ) : array( 1, 2, 3, 4, 5 );

		$wpdb->update(
			VT_DB::employees(),
			array(
				'department_id'         => absint( $_POST['department_id'] ) ?: null,
				'team'                  => sanitize_text_field( $_POST['team'] ),
				'job_title'             => sanitize_text_field( $_POST['job_title'] ),
				'primary_team_lead_id'  => absint( $_POST['primary_team_lead_id'] ) ?: null,
				'secondary_approver_id' => absint( $_POST['secondary_approver_id'] ) ?: null,
				'hr_administrator_id'   => absint( $_POST['hr_administrator_id'] ) ?: null,
				'employment_type'       => sanitize_text_field( $_POST['employment_type'] ),
				'work_schedule'         => sanitize_text_field( $_POST['work_schedule'] ),
				'standard_workdays'     => implode( ',', $workdays ),
				'hours_per_workday'     => floatval( $_POST['hours_per_workday'] ),
				'vacation_entitlement'  => floatval( $_POST['vacation_entitlement'] ),
				'location'              => sanitize_text_field( $_POST['location'] ),
				'jurisdiction'          => sanitize_text_field( $_POST['jurisdiction'] ),
				'is_executive'          => ! empty( $_POST['is_executive'] ) ? 1 : 0,
				'updated_at'            => current_time( 'mysql' ),
			),
			array( 'employee_id' => $employee_id )
		);
		VT_Audit::log( 'Employee', $employee_id, 'Updated', null, null, null );
		self::redirect( 'vt-employees', 'Employee updated.' );
	}

	private static function handle_deactivate_employee() {
		check_admin_referer( 'vt_deactivate_employee' );
		global $wpdb;
		$employee_id = absint( $_POST['employee_id'] );

		$wpdb->update(
			VT_DB::employees(),
			array(
				'active'             => 0,
				'employment_status'  => 'terminated',
				'termination_date'   => current_time( 'Y-m-d' ),
				'updated_at'         => current_time( 'mysql' ),
			),
			array( 'employee_id' => $employee_id )
		);

		// Withdraw any pending requests - they can no longer take future vacation.
		$pending = VT_Requests::get_pending_for_employee( $employee_id );
		foreach ( $pending as $req ) {
			VT_Requests::withdraw( $req->request_id, $employee_id );
		}

		// Flag anyone who relied on this person as their approver, for HR reassignment.
		$affected = $wpdb->get_results( $wpdb->prepare( "SELECT employee_id FROM " . VT_DB::employees() . " WHERE primary_team_lead_id = %d AND active = 1", $employee_id ) );
		if ( $affected ) {
			VT_Audit::log( 'Employee', $employee_id, 'Manager-Reassignment-Needed', null, array( 'affected_count' => count( $affected ) ), null );
		}

		VT_Audit::log( 'Employee', $employee_id, 'Deactivated', null, null, null );
		self::redirect( 'vt-employees', 'Employee deactivated.' );
	}

	public static function page_employees() {
		global $wpdb;
		$employees   = $wpdb->get_results( "SELECT * FROM " . VT_DB::employees() . " ORDER BY active DESC, last_name ASC" );
		$departments = $wpdb->get_results( "SELECT * FROM " . VT_DB::departments() . " WHERE active = 1 ORDER BY name ASC" );
		$edit_id     = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		$editing     = $edit_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . VT_DB::employees() . " WHERE employee_id = %d", $edit_id ) ) : null;
		$days_labels = array( 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun' );
		?>
		<div class="wrap">
			<h1>Employees</h1>
			<?php self::notice(); ?>

			<h2><?php echo $editing ? 'Edit Employee' : 'Add Employee'; ?></h2>
			<form method="post" style="max-width:640px;">
				<?php wp_nonce_field( $editing ? 'vt_edit_employee' : 'vt_add_employee' ); ?>
				<input type="hidden" name="vt_action" value="<?php echo $editing ? 'edit_employee' : 'add_employee'; ?>">
				<?php if ( $editing ) : ?><input type="hidden" name="employee_id" value="<?php echo esc_attr( $editing->employee_id ); ?>"><?php endif; ?>
				<table class="form-table">
					<?php if ( ! $editing ) : ?>
					<tr><th>First Name</th><td><input type="text" name="first_name" required></td></tr>
					<tr><th>Last Name</th><td><input type="text" name="last_name" required></td></tr>
					<tr><th>Work Email</th><td><input type="email" name="work_email" required></td></tr>
					<tr><th>Existing WP User ID (optional)</th><td><input type="number" name="existing_user_id" placeholder="Leave blank to create a new login"></td></tr>
					<tr><th>Hire Date</th><td><input type="date" name="hire_date"></td></tr>
					<tr><th>Roles</th><td>
						<label><input type="checkbox" name="role_manager" value="1"> Manager (can approve their team's requests)</label><br>
						<label><input type="checkbox" name="role_hr_admin" value="1"> HR Admin (full back-end access)</label>
					</td></tr>
					<?php endif; ?>
					<tr><th>Department</th><td>
						<select name="department_id">
							<option value="">-</option>
							<?php foreach ( $departments as $d ) : ?>
								<option value="<?php echo esc_attr( $d->department_id ); ?>" <?php selected( $editing && $editing->department_id == $d->department_id ); ?>><?php echo esc_html( $d->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</td></tr>
					<tr><th>Team</th><td><input type="text" name="team" value="<?php echo esc_attr( $editing->team ?? '' ); ?>"></td></tr>
					<tr><th>Job Title</th><td><input type="text" name="job_title" value="<?php echo esc_attr( $editing->job_title ?? '' ); ?>"></td></tr>
					<tr><th>Primary Team Lead (Employee ID)</th><td><input type="number" name="primary_team_lead_id" value="<?php echo esc_attr( $editing->primary_team_lead_id ?? '' ); ?>"></td></tr>
					<tr><th>Secondary Approver (Employee ID)</th><td><input type="number" name="secondary_approver_id" value="<?php echo esc_attr( $editing->secondary_approver_id ?? '' ); ?>"></td></tr>
					<tr><th>HR Administrator (Employee ID)</th><td><input type="number" name="hr_administrator_id" value="<?php echo esc_attr( $editing->hr_administrator_id ?? '' ); ?>"></td></tr>
					<tr><th>Employment Type</th><td>
						<select name="employment_type">
							<?php foreach ( array( 'full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract' ) as $val => $label ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $editing && $editing->employment_type === $val ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td></tr>
					<tr><th>Work Schedule</th><td>
						<select name="work_schedule">
							<?php foreach ( array( 'standard' => 'Standard', 'compressed' => 'Compressed', 'custom' => 'Custom' ) as $val => $label ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $editing && $editing->work_schedule === $val ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td></tr>
					<tr><th>Standard Workdays</th><td>
						<?php $current_days = $editing ? array_map( 'intval', explode( ',', $editing->standard_workdays ) ) : array( 1, 2, 3, 4, 5 ); ?>
						<?php foreach ( $days_labels as $num => $label ) : ?>
							<label><input type="checkbox" name="standard_workdays[]" value="<?php echo esc_attr( $num ); ?>" <?php checked( in_array( $num, $current_days, true ) ); ?>> <?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?>
					</td></tr>
					<tr><th>Hours per Workday</th><td><input type="number" step="0.25" name="hours_per_workday" value="<?php echo esc_attr( $editing->hours_per_workday ?? 8 ); ?>"></td></tr>
					<tr><th>Vacation Entitlement (days/yr)</th><td><input type="number" step="0.5" name="vacation_entitlement" value="<?php echo esc_attr( $editing->vacation_entitlement ?? 15 ); ?>"></td></tr>
					<?php if ( ! $editing ) : ?>
					<tr><th>Carry-Forward</th><td><input type="number" step="0.5" name="carry_forward" value="0"></td></tr>
					<?php endif; ?>
					<tr><th>Location</th><td><input type="text" name="location" value="<?php echo esc_attr( $editing->location ?? '' ); ?>"></td></tr>
					<tr><th>Province/Jurisdiction</th><td><input type="text" name="jurisdiction" value="<?php echo esc_attr( $editing->jurisdiction ?? '' ); ?>" placeholder="e.g. Ontario"></td></tr>
					<tr><th>Executive</th><td><label><input type="checkbox" name="is_executive" value="1" <?php checked( $editing && $editing->is_executive ); ?>> Route this employee's requests to the executive approver</label></td></tr>
				</table>
				<p><button class="button button-primary"><?php echo $editing ? 'Save Changes' : 'Add Employee'; ?></button>
				<?php if ( $editing ) : ?> <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=vt-employees' ) ); ?>">Cancel</a><?php endif; ?></p>
			</form>

			<h2>All Employees</h2>
			<table class="widefat striped">
				<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Department</th><th>Entitlement</th><th>Status</th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $employees as $emp ) : ?>
					<tr>
						<td><?php echo esc_html( $emp->employee_id ); ?></td>
						<td><?php echo esc_html( $emp->first_name . ' ' . $emp->last_name ); ?></td>
						<td><?php echo esc_html( $emp->work_email ); ?></td>
						<td><?php echo esc_html( $emp->department_id ); ?></td>
						<td><?php echo esc_html( $emp->vacation_entitlement ); ?></td>
						<td><?php echo $emp->active ? 'Active' : 'Inactive'; ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=vt-employees&edit=' . $emp->employee_id ) ); ?>">Edit</a>
							<?php if ( $emp->active ) : ?>
								&nbsp;|&nbsp;
								<form method="post" style="display:inline;" onsubmit="return confirm('Deactivate this employee?');">
									<?php wp_nonce_field( 'vt_deactivate_employee' ); ?>
									<input type="hidden" name="vt_action" value="deactivate_employee">
									<input type="hidden" name="employee_id" value="<?php echo esc_attr( $emp->employee_id ); ?>">
									<button class="button-link" style="color:#a33;">Deactivate</button>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------- */
	/* Departments                                                       */
	/* ---------------------------------------------------------------- */

	private static function handle_save_department() {
		check_admin_referer( 'vt_save_department' );
		global $wpdb;
		$id = absint( $_POST['department_id'] ?? 0 );
		$data = array(
			'name'                 => sanitize_text_field( $_POST['name'] ),
			'manager_employee_id'  => absint( $_POST['manager_employee_id'] ) ?: null,
			'default_approver_id'  => absint( $_POST['default_approver_id'] ) ?: null,
			'backup_approver_id'   => absint( $_POST['backup_approver_id'] ) ?: null,
			'min_staffing'         => absint( $_POST['min_staffing'] ) ?: null,
			'max_away'             => absint( $_POST['max_away'] ) ?: null,
			'active'               => 1,
		);
		if ( $id ) {
			$wpdb->update( VT_DB::departments(), $data, array( 'department_id' => $id ) );
		} else {
			$wpdb->insert( VT_DB::departments(), $data );
		}
		VT_Audit::log( 'Department', $id ?: $wpdb->insert_id, $id ? 'Updated' : 'Created', null, $data, null );
		self::redirect( 'vt-departments', 'Department saved.' );
	}

	public static function page_departments() {
		global $wpdb;
		$departments = $wpdb->get_results( "SELECT * FROM " . VT_DB::departments() . " ORDER BY name ASC" );
		?>
		<div class="wrap">
			<h1>Departments</h1>
			<?php self::notice(); ?>
			<h2>Add Department</h2>
			<form method="post" style="max-width:520px;">
				<?php wp_nonce_field( 'vt_save_department' ); ?>
				<input type="hidden" name="vt_action" value="save_department">
				<table class="form-table">
					<tr><th>Name</th><td><input type="text" name="name" required></td></tr>
					<tr><th>Department Manager (Employee ID)</th><td><input type="number" name="manager_employee_id"></td></tr>
					<tr><th>Default Approver (Employee ID)</th><td><input type="number" name="default_approver_id"></td></tr>
					<tr><th>Backup Approver (Employee ID)</th><td><input type="number" name="backup_approver_id"></td></tr>
					<tr><th>Minimum Staffing</th><td><input type="number" name="min_staffing"></td></tr>
					<tr><th>Max Employees Away Simultaneously</th><td><input type="number" name="max_away"></td></tr>
				</table>
				<p><button class="button button-primary">Save Department</button></p>
			</form>

			<h2>All Departments</h2>
			<table class="widefat striped">
				<thead><tr><th>ID</th><th>Name</th><th>Manager (Emp ID)</th><th>Max Away</th></tr></thead>
				<tbody>
				<?php foreach ( $departments as $d ) : ?>
					<tr><td><?php echo esc_html( $d->department_id ); ?></td><td><?php echo esc_html( $d->name ); ?></td><td><?php echo esc_html( $d->manager_employee_id ); ?></td><td><?php echo esc_html( $d->max_away ); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------- */
	/* Holidays                                                          */
	/* ---------------------------------------------------------------- */

	private static function handle_save_holiday() {
		check_admin_referer( 'vt_save_holiday' );
		global $wpdb;
		$wpdb->insert(
			VT_DB::holidays(),
			array(
				'name'               => sanitize_text_field( $_POST['name'] ),
				'holiday_date'       => sanitize_text_field( $_POST['holiday_date'] ),
				'jurisdiction'       => sanitize_text_field( $_POST['jurisdiction'] ?: 'all' ),
				'paid'               => ! empty( $_POST['paid'] ) ? 1 : 0,
				'active'             => 1,
				'excluded_from_calc' => ! empty( $_POST['excluded_from_calc'] ) ? 1 : 0,
			)
		);
		VT_Audit::log( 'Holiday', $wpdb->insert_id, 'Created', null, null, null );
		self::redirect( 'vt-holidays', 'Holiday added.' );
	}

	private static function handle_delete_holiday() {
		check_admin_referer( 'vt_delete_holiday' );
		global $wpdb;
		$id = absint( $_POST['holiday_id'] );
		$wpdb->update( VT_DB::holidays(), array( 'active' => 0 ), array( 'holiday_id' => $id ) );
		VT_Audit::log( 'Holiday', $id, 'Deactivated', null, null, null );
		self::redirect( 'vt-holidays', 'Holiday removed.' );
	}

	public static function page_holidays() {
		global $wpdb;
		$holidays = $wpdb->get_results( "SELECT * FROM " . VT_DB::holidays() . " WHERE active = 1 ORDER BY holiday_date ASC" );
		?>
		<div class="wrap">
			<h1>Holidays</h1>
			<?php self::notice(); ?>
			<h2>Add Holiday</h2>
			<form method="post" style="max-width:480px;">
				<?php wp_nonce_field( 'vt_save_holiday' ); ?>
				<input type="hidden" name="vt_action" value="save_holiday">
				<table class="form-table">
					<tr><th>Name</th><td><input type="text" name="name" required></td></tr>
					<tr><th>Date</th><td><input type="date" name="holiday_date" required></td></tr>
					<tr><th>Jurisdiction</th><td><input type="text" name="jurisdiction" placeholder="all, or e.g. Ontario"></td></tr>
					<tr><th>Paid</th><td><input type="checkbox" name="paid" value="1" checked></td></tr>
					<tr><th>Excluded from vacation calc</th><td><input type="checkbox" name="excluded_from_calc" value="1" checked></td></tr>
				</table>
				<p><button class="button button-primary">Add Holiday</button></p>
			</form>
			<h2>Holidays</h2>
			<table class="widefat striped">
				<thead><tr><th>Name</th><th>Date</th><th>Jurisdiction</th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $holidays as $h ) : ?>
					<tr>
						<td><?php echo esc_html( $h->name ); ?></td>
						<td><?php echo esc_html( $h->holiday_date ); ?></td>
						<td><?php echo esc_html( $h->jurisdiction ); ?></td>
						<td>
							<form method="post" style="display:inline;" onsubmit="return confirm('Remove this holiday?');">
								<?php wp_nonce_field( 'vt_delete_holiday' ); ?>
								<input type="hidden" name="vt_action" value="delete_holiday">
								<input type="hidden" name="holiday_id" value="<?php echo esc_attr( $h->holiday_id ); ?>">
								<button class="button-link" style="color:#a33;">Remove</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------- */
	/* Blackout Periods                                                  */
	/* ---------------------------------------------------------------- */

	private static function handle_save_blackout() {
		check_admin_referer( 'vt_save_blackout' );
		global $wpdb;
		$wpdb->insert(
			VT_DB::blackout_periods(),
			array(
				'name'                   => sanitize_text_field( $_POST['name'] ),
				'start_date'             => sanitize_text_field( $_POST['start_date'] ),
				'end_date'               => sanitize_text_field( $_POST['end_date'] ),
				'department_id'          => absint( $_POST['department_id'] ) ?: null,
				'location'               => sanitize_text_field( $_POST['location'] ),
				'reason'                 => sanitize_textarea_field( $_POST['reason'] ),
				'mode'                   => ( 'block' === $_POST['mode'] ) ? 'block' : 'warn',
				'exception_approver_id'  => absint( $_POST['exception_approver_id'] ) ?: null,
				'active'                 => 1,
			)
		);
		VT_Audit::log( 'Blackout Period', $wpdb->insert_id, 'Created', null, null, null );
		self::redirect( 'vt-blackout', 'Blackout period added.' );
	}

	private static function handle_delete_blackout() {
		check_admin_referer( 'vt_delete_blackout' );
		global $wpdb;
		$id = absint( $_POST['blackout_id'] );
		$wpdb->update( VT_DB::blackout_periods(), array( 'active' => 0 ), array( 'blackout_id' => $id ) );
		VT_Audit::log( 'Blackout Period', $id, 'Deactivated', null, null, null );
		self::redirect( 'vt-blackout', 'Blackout period removed.' );
	}

	public static function page_blackout() {
		global $wpdb;
		$periods     = $wpdb->get_results( "SELECT * FROM " . VT_DB::blackout_periods() . " WHERE active = 1 ORDER BY start_date ASC" );
		$departments = $wpdb->get_results( "SELECT * FROM " . VT_DB::departments() . " WHERE active = 1 ORDER BY name ASC" );
		?>
		<div class="wrap">
			<h1>Blackout Periods</h1>
			<?php self::notice(); ?>
			<h2>Add Blackout Period</h2>
			<form method="post" style="max-width:560px;">
				<?php wp_nonce_field( 'vt_save_blackout' ); ?>
				<input type="hidden" name="vt_action" value="save_blackout">
				<table class="form-table">
					<tr><th>Name</th><td><input type="text" name="name" required></td></tr>
					<tr><th>Start Date</th><td><input type="date" name="start_date" required></td></tr>
					<tr><th>End Date</th><td><input type="date" name="end_date" required></td></tr>
					<tr><th>Department (blank = all)</th><td>
						<select name="department_id"><option value="">All</option>
							<?php foreach ( $departments as $d ) : ?><option value="<?php echo esc_attr( $d->department_id ); ?>"><?php echo esc_html( $d->name ); ?></option><?php endforeach; ?>
						</select>
					</td></tr>
					<tr><th>Location</th><td><input type="text" name="location"></td></tr>
					<tr><th>Reason</th><td><textarea name="reason" rows="2"></textarea></td></tr>
					<tr><th>Mode</th><td>
						<label><input type="radio" name="mode" value="warn" checked> Warn only</label><br>
						<label><input type="radio" name="mode" value="block"> Block (requires Exception Approver)</label>
					</td></tr>
					<tr><th>Exception Approver (Employee ID)</th><td><input type="number" name="exception_approver_id"></td></tr>
				</table>
				<p><button class="button button-primary">Add Blackout Period</button></p>
			</form>
			<h2>Blackout Periods</h2>
			<table class="widefat striped">
				<thead><tr><th>Name</th><th>Dates</th><th>Mode</th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $periods as $p ) : ?>
					<tr>
						<td><?php echo esc_html( $p->name ); ?></td>
						<td><?php echo esc_html( $p->start_date . ' - ' . $p->end_date ); ?></td>
						<td><?php echo esc_html( ucfirst( $p->mode ) ); ?></td>
						<td>
							<form method="post" style="display:inline;" onsubmit="return confirm('Remove this blackout period?');">
								<?php wp_nonce_field( 'vt_delete_blackout' ); ?>
								<input type="hidden" name="vt_action" value="delete_blackout">
								<input type="hidden" name="blackout_id" value="<?php echo esc_attr( $p->blackout_id ); ?>">
								<button class="button-link" style="color:#a33;">Remove</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------- */
	/* All Requests + Override                                           */
	/* ---------------------------------------------------------------- */

	private static function handle_override_decision() {
		check_admin_referer( 'vt_override_decision' );
		$request_id = absint( $_POST['request_id'] );
		$decision   = sanitize_text_field( $_POST['decision'] );
		$comments   = sanitize_textarea_field( $_POST['comments'] );

		if ( '' === trim( $comments ) ) {
			self::redirect( 'vt-requests', 'An override requires a comment explaining the reason.', 'error' );
		}

		if ( in_array( $decision, array( 'approve', 'reject', 'more_info' ), true ) ) {
			$result = VT_Requests::decide( $request_id, $decision, '[HR OVERRIDE] ' . $comments, get_current_user_id() );
		} elseif ( 'cancel_approve' === $decision ) {
			$result = VT_Requests::decide_cancellation( $request_id, true, get_current_user_id() );
		} elseif ( 'cancel_deny' === $decision ) {
			$result = VT_Requests::decide_cancellation( $request_id, false, get_current_user_id() );
		} else {
			self::redirect( 'vt-requests', 'Unknown action.', 'error' );
		}

		VT_Audit::log( 'Request', $request_id, 'HR-Override', null, array( 'decision' => $decision, 'comments' => $comments ), null );
		self::redirect( 'vt-requests', $result['message'], $result['ok'] ? 'success' : 'error' );
	}

	public static function page_requests() {
		global $wpdb;
		$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
		$sql = "SELECT * FROM " . VT_DB::requests() . " WHERE status != 'draft'";
		if ( $status_filter ) {
			$sql .= $wpdb->prepare( " AND status = %s", $status_filter );
		}
		$sql .= " ORDER BY created_at DESC LIMIT 200";
		$requests = $wpdb->get_results( $sql );
		?>
		<div class="wrap">
			<h1>All Vacation Requests</h1>
			<?php self::notice(); ?>
			<table class="widefat striped">
				<thead><tr><th>Code</th><th>Employee</th><th>Dates</th><th>Days</th><th>Status</th><th>Approver</th><th>Override</th></tr></thead>
				<tbody>
				<?php foreach ( $requests as $req ) :
					$employee = VT_Balances::get_employee( $req->employee_id );
					$approver = $req->assigned_approver_id ? VT_Balances::get_employee( $req->assigned_approver_id ) : null;
					$is_pending = in_array( $req->status, array( 'pending_team_lead', 'pending_department', 'pending_hr' ), true );
					$is_cancel_pending = ( 'cancellation_pending' === $req->status );
					?>
					<tr>
						<td><?php echo esc_html( $req->request_code ); ?></td>
						<td><?php echo esc_html( $employee ? $employee->first_name . ' ' . $employee->last_name : $req->employee_id ); ?></td>
						<td><?php echo esc_html( $req->start_date . ' - ' . $req->end_date ); ?></td>
						<td><?php echo esc_html( $req->total_days ); ?></td>
						<td><?php echo esc_html( $req->status ); ?></td>
						<td><?php echo esc_html( $approver ? $approver->first_name . ' ' . $approver->last_name : '-' ); ?></td>
						<td>
							<?php if ( $is_pending || $is_cancel_pending ) : ?>
							<form method="post" onsubmit="return confirm('Override this request\'s decision?');">
								<?php wp_nonce_field( 'vt_override_decision' ); ?>
								<input type="hidden" name="vt_action" value="override_decision">
								<input type="hidden" name="request_id" value="<?php echo esc_attr( $req->request_id ); ?>">
								<select name="decision">
									<?php if ( $is_cancel_pending ) : ?>
										<option value="cancel_approve">Approve Cancellation</option>
										<option value="cancel_deny">Deny Cancellation</option>
									<?php else : ?>
										<option value="approve">Approve</option>
										<option value="reject">Reject</option>
										<option value="more_info">Request Info</option>
									<?php endif; ?>
								</select>
								<input type="text" name="comments" placeholder="Reason (required)" style="width:160px;">
								<button class="button">Override</button>
							</form>
							<?php else : ?>-<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------- */
	/* Balance Adjustments                                               */
	/* ---------------------------------------------------------------- */

	private static function handle_add_adjustment() {
		check_admin_referer( 'vt_add_adjustment' );
		$employee_id = absint( $_POST['employee_id'] );
		$year        = absint( $_POST['vacation_year'] );
		$type        = ( 'deduction' === $_POST['adjustment_type'] ) ? 'deduction' : 'credit';
		$amount      = floatval( $_POST['amount'] );
		$reason      = sanitize_textarea_field( $_POST['reason'] );

		if ( '' === trim( $reason ) ) {
			self::redirect( 'vt-adjustments', 'A reason is required for balance adjustments.', 'error' );
		}

		VT_Balances::apply_manual_adjustment( $employee_id, $year, $type, $amount, $reason, get_current_user_id() );
		self::redirect( 'vt-adjustments', 'Adjustment recorded.' );
	}

	public static function page_adjustments() {
		global $wpdb;
		$adjustments = $wpdb->get_results( "SELECT * FROM " . VT_DB::adjustments() . " ORDER BY created_at DESC LIMIT 100" );
		$year        = VT_Calc::current_vacation_year();

		$lookup_id = isset( $_GET['employee_id'] ) ? absint( $_GET['employee_id'] ) : 0;
		$balance   = $lookup_id ? VT_Balances::get_or_create( $lookup_id, $year ) : null;
		?>
		<div class="wrap">
			<h1>Balance Adjustments</h1>
			<?php self::notice(); ?>

			<h2>Look Up Balance</h2>
			<form method="get" style="margin-bottom:20px;">
				<input type="hidden" name="page" value="vt-adjustments">
				<input type="number" name="employee_id" placeholder="Employee ID" value="<?php echo esc_attr( $lookup_id ); ?>">
				<button class="button">Look Up</button>
			</form>
			<?php if ( $balance ) : ?>
				<table class="widefat" style="max-width:520px;margin-bottom:20px;">
					<tr><th>Entitlement</th><td><?php echo esc_html( $balance->annual_entitlement ); ?></td></tr>
					<tr><th>Carry-Forward</th><td><?php echo esc_html( $balance->carry_forward ); ?></td></tr>
					<tr><th>Manual Credits</th><td><?php echo esc_html( $balance->manual_credits ); ?></td></tr>
					<tr><th>Manual Deductions</th><td><?php echo esc_html( $balance->manual_deductions ); ?></td></tr>
					<tr><th>Approved Used</th><td><?php echo esc_html( $balance->approved_days_used ); ?></td></tr>
					<tr><th>Pending</th><td><?php echo esc_html( $balance->pending_days ); ?></td></tr>
					<tr><th>Remaining</th><td><strong><?php echo esc_html( $balance->remaining_days ); ?></strong></td></tr>
				</table>
			<?php endif; ?>

			<h2>Add Adjustment</h2>
			<form method="post" style="max-width:480px;">
				<?php wp_nonce_field( 'vt_add_adjustment' ); ?>
				<input type="hidden" name="vt_action" value="add_adjustment">
				<table class="form-table">
					<tr><th>Employee ID</th><td><input type="number" name="employee_id" required value="<?php echo esc_attr( $lookup_id ); ?>"></td></tr>
					<tr><th>Vacation Year</th><td><input type="number" name="vacation_year" value="<?php echo esc_attr( $year ); ?>"></td></tr>
					<tr><th>Type</th><td>
						<select name="adjustment_type"><option value="credit">Credit (add days)</option><option value="deduction">Deduction (remove days)</option></select>
					</td></tr>
					<tr><th>Amount (days)</th><td><input type="number" step="0.5" name="amount" required></td></tr>
					<tr><th>Reason</th><td><textarea name="reason" rows="2" required></textarea></td></tr>
				</table>
				<p><button class="button button-primary">Save Adjustment</button></p>
			</form>

			<h2>Recent Adjustments</h2>
			<table class="widefat striped">
				<thead><tr><th>Employee ID</th><th>Year</th><th>Type</th><th>Amount</th><th>Reason</th><th>Date</th></tr></thead>
				<tbody>
				<?php foreach ( $adjustments as $a ) : ?>
					<tr>
						<td><?php echo esc_html( $a->employee_id ); ?></td>
						<td><?php echo esc_html( $a->vacation_year ); ?></td>
						<td><?php echo esc_html( ucfirst( $a->adjustment_type ) ); ?></td>
						<td><?php echo esc_html( $a->amount ); ?></td>
						<td><?php echo esc_html( $a->reason ); ?></td>
						<td><?php echo esc_html( $a->created_at ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------- */
	/* Delegations                                                       */
	/* ---------------------------------------------------------------- */

	private static function handle_add_delegation() {
		check_admin_referer( 'vt_add_delegation' );
		global $wpdb;
		$wpdb->insert(
			VT_DB::delegations(),
			array(
				'original_approver_id' => absint( $_POST['original_approver_id'] ),
				'delegate_approver_id' => absint( $_POST['delegate_approver_id'] ),
				'start_date'           => sanitize_text_field( $_POST['start_date'] ),
				'end_date'             => sanitize_text_field( $_POST['end_date'] ),
				'scope_note'           => sanitize_text_field( $_POST['scope_note'] ),
				'active'               => 1,
				'created_at'           => current_time( 'mysql' ),
			)
		);
		VT_Audit::log( 'Delegation', $wpdb->insert_id, 'Created-By-HR', null, null, null );
		self::redirect( 'vt-delegations', 'Delegation added.' );
	}

	public static function page_delegations() {
		global $wpdb;
		$delegations = $wpdb->get_results( "SELECT * FROM " . VT_DB::delegations() . " ORDER BY start_date DESC LIMIT 100" );
		?>
		<div class="wrap">
			<h1>Approval Delegations</h1>
			<?php self::notice(); ?>
			<h2>Add Delegation (on behalf of a manager)</h2>
			<form method="post" style="max-width:480px;">
				<?php wp_nonce_field( 'vt_add_delegation' ); ?>
				<input type="hidden" name="vt_action" value="add_delegation">
				<table class="form-table">
					<tr><th>Original Approver (Employee ID)</th><td><input type="number" name="original_approver_id" required></td></tr>
					<tr><th>Delegate (Employee ID)</th><td><input type="number" name="delegate_approver_id" required></td></tr>
					<tr><th>Start Date</th><td><input type="date" name="start_date" required></td></tr>
					<tr><th>End Date</th><td><input type="date" name="end_date" required></td></tr>
					<tr><th>Note</th><td><input type="text" name="scope_note"></td></tr>
				</table>
				<p><button class="button button-primary">Save Delegation</button></p>
			</form>
			<h2>Delegations</h2>
			<table class="widefat striped">
				<thead><tr><th>Original</th><th>Delegate</th><th>Dates</th><th>Active</th></tr></thead>
				<tbody>
				<?php foreach ( $delegations as $d ) : ?>
					<tr>
						<td><?php echo esc_html( $d->original_approver_id ); ?></td>
						<td><?php echo esc_html( $d->delegate_approver_id ); ?></td>
						<td><?php echo esc_html( $d->start_date . ' - ' . $d->end_date ); ?></td>
						<td><?php echo $d->active ? 'Yes' : 'No'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------- */
	/* Annual Rollover                                                   */
	/* ---------------------------------------------------------------- */

	private static function handle_run_rollover() {
		check_admin_referer( 'vt_run_rollover' );
		$to_year = absint( $_POST['to_year'] );
		$commit  = ! empty( $_POST['confirm_commit'] );
		VT_Balances::run_rollover( $to_year, $commit );
		self::redirect( 'vt-rollover', $commit ? "Rollover to {$to_year} committed." : 'Dry-run report generated below (scroll down); nothing was written yet.' );
	}

	public static function page_rollover() {
		$next_year = VT_Calc::current_vacation_year() + 1;
		$report    = VT_Balances::run_rollover( $next_year, false ); // Always show a fresh dry-run.
		?>
		<div class="wrap">
			<h1>Annual Rollover</h1>
			<?php self::notice(); ?>
			<p>This creates <?php echo esc_html( $next_year ); ?> balances for all active employees, applies carry-forward (capped at the configured maximum), and closes <?php echo esc_html( $next_year - 1 ); ?>. It is safe to re-run - employees already rolled over are skipped automatically.</p>

			<h2>Pre-Rollover Review (dry run, nothing saved yet)</h2>
			<table class="widefat striped">
				<thead><tr><th>Employee</th><th>Status</th><th>Prior Remaining</th><th>Carry-Forward</th><th>Forfeited</th><th>New Entitlement</th></tr></thead>
				<tbody>
				<?php foreach ( $report as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row['employee']->first_name . ' ' . $row['employee']->last_name ); ?></td>
						<td><?php echo $row['skipped'] ? 'Already rolled over' : 'Ready'; ?></td>
						<td><?php echo esc_html( $row['skipped'] ? '-' : $row['prior_remaining'] ); ?></td>
						<td><?php echo esc_html( $row['skipped'] ? '-' : $row['carry_forward'] ); ?></td>
						<td><?php echo esc_html( $row['skipped'] ? '-' : $row['forfeited'] ); ?></td>
						<td><?php echo esc_html( $row['skipped'] ? '-' : $row['new_entitlement'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2>Commit Rollover</h2>
			<form method="post" onsubmit="return confirm('This will create next-year balances and close the prior year. Continue?');">
				<?php wp_nonce_field( 'vt_run_rollover' ); ?>
				<input type="hidden" name="vt_action" value="run_rollover">
				<input type="hidden" name="to_year" value="<?php echo esc_attr( $next_year ); ?>">
				<label><input type="checkbox" name="confirm_commit" value="1" required> I have reviewed the report above and confirm this rollover.</label>
				<p><button class="button button-primary">Run Rollover to <?php echo esc_html( $next_year ); ?></button></p>
			</form>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------- */
	/* Audit Log / System Log                                            */
	/* ---------------------------------------------------------------- */

	public static function page_audit() {
		global $wpdb;
		$type_filter = isset( $_GET['record_type'] ) ? sanitize_text_field( $_GET['record_type'] ) : '';
		$sql = "SELECT * FROM " . VT_DB::audit_log();
		if ( $type_filter ) {
			$sql .= $wpdb->prepare( " WHERE record_type = %s", $type_filter );
		}
		$sql .= " ORDER BY created_at DESC LIMIT 300";
		$rows = $wpdb->get_results( $sql );
		?>
		<div class="wrap">
			<h1>Audit Log</h1>
			<p>Read-only, write-once record of every business action. Corrections are new rows here, never edits to history.</p>
			<table class="widefat striped">
				<thead><tr><th>Date</th><th>Type</th><th>Record</th><th>Action</th><th>By</th><th>Comments</th></tr></thead>
				<tbody>
				<?php foreach ( $rows as $r ) : ?>
					<tr>
						<td><?php echo esc_html( $r->created_at ); ?></td>
						<td><?php echo esc_html( $r->record_type ); ?></td>
						<td><?php echo esc_html( $r->record_id ); ?></td>
						<td><?php echo esc_html( $r->action ); ?></td>
						<td><?php echo esc_html( $r->performed_by ); ?></td>
						<td><?php echo esc_html( $r->comments ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function page_system_log() {
		global $wpdb;
		$rows = $wpdb->get_results( "SELECT * FROM " . VT_DB::error_log() . " ORDER BY created_at DESC LIMIT 200" );
		?>
		<div class="wrap">
			<h1>System Log</h1>
			<p>Workflow/technical failures (failed emails, missing approvers, balance-update errors). This is the "Workflow Failures" list from the design blueprint.</p>
			<table class="widefat striped">
				<thead><tr><th>Date</th><th>Source</th><th>Message</th><th>Resolved</th></tr></thead>
				<tbody>
				<?php foreach ( $rows as $r ) : ?>
					<tr>
						<td><?php echo esc_html( $r->created_at ); ?></td>
						<td><?php echo esc_html( $r->source ); ?></td>
						<td><?php echo esc_html( $r->message ); ?></td>
						<td><?php echo $r->resolved ? 'Yes' : 'No'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------- */
	/* Settings                                                          */
	/* ---------------------------------------------------------------- */

	private static function handle_save_settings() {
		check_admin_referer( 'vt_save_settings' );
		$posted = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array();
		foreach ( $posted as $key => $value ) {
			VT_Settings::set( sanitize_key( $key ), sanitize_text_field( $value ) );
		}
		VT_Audit::log( 'Settings', 'bulk', 'Updated', null, null, null );
		self::redirect( 'vt-settings', 'Settings saved.' );
	}

	public static function page_settings() {
		$settings = VT_Settings::get_all();
		?>
		<div class="wrap">
			<h1>System Settings</h1>
			<?php self::notice(); ?>
			<form method="post">
				<?php wp_nonce_field( 'vt_save_settings' ); ?>
				<input type="hidden" name="vt_action" value="save_settings">
				<table class="form-table">
					<?php foreach ( $settings as $s ) : ?>
						<tr>
							<th><?php echo esc_html( $s->setting_key ); ?><br><span style="font-weight:normal;color:#666;font-size:12px;"><?php echo esc_html( $s->description ); ?></span></th>
							<td><input type="text" name="settings[<?php echo esc_attr( $s->setting_key ); ?>]" value="<?php echo esc_attr( $s->setting_value ); ?>" style="width:320px;"></td>
						</tr>
					<?php endforeach; ?>
				</table>
				<p><button class="button button-primary">Save Settings</button></p>
			</form>
		</div>
		<?php
	}
}
