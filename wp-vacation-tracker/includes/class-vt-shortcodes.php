<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The [vt_app] front-end portal: Employee dashboard/request form/history,
 * plus a Manager "Approvals" tab shown only to users who hold the
 * vt_manage_team capability (blueprint Section 14). Data-layer scoping
 * (which rows a user's queries can even return) is enforced in
 * VT_Requests/VT_Balances, not just by which tabs are rendered here.
 */
class VT_Shortcodes {

	public static function init() {
		add_shortcode( 'vt_app', array( __CLASS__, 'render_app' ) );
	}

	public static function render_app( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<div class="vt-app vt-login-notice"><p>Please <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">log in</a> to use the Vacation Tracker.</p></div>';
		}
		if ( ! current_user_can( 'vt_use_portal' ) ) {
			return '<div class="vt-app vt-login-notice"><p>Your account does not have access to the Vacation Tracker portal.</p></div>';
		}

		$employee = VT_Requests::get_employee_by_wp_user( get_current_user_id() );
		if ( ! $employee ) {
			return '<div class="vt-app vt-login-notice"><p>Your account is not yet linked to an employee profile. Please contact HR.</p></div>';
		}

		if ( ! get_option( 'vt_portal_page_recorded' ) && get_the_ID() ) {
			VT_Settings::set( 'portal_page_id', get_the_ID(), 'number', 'The page containing [vt_app]' );
			update_option( 'vt_portal_page_recorded', 1 );
		}

		$is_manager = current_user_can( 'vt_manage_team' ) || current_user_can( 'vt_manage_all' );

		ob_start();
		?>
		<div class="vt-app" id="vt-app">
			<nav class="vt-tabs">
				<button type="button" class="vt-tab active" data-tab="dashboard">My Vacation</button>
				<button type="button" class="vt-tab" data-tab="request">New Request</button>
				<button type="button" class="vt-tab" data-tab="calendar">Team Calendar</button>
				<?php if ( $is_manager ) : ?>
					<button type="button" class="vt-tab" data-tab="approvals">Approvals</button>
				<?php endif; ?>
			</nav>

			<section class="vt-panel active" data-panel="dashboard">
				<?php self::render_dashboard( $employee ); ?>
			</section>

			<section class="vt-panel" data-panel="request">
				<?php self::render_request_form( $employee ); ?>
			</section>

			<section class="vt-panel" data-panel="calendar">
				<?php self::render_calendar( $employee, $is_manager ); ?>
			</section>

			<?php if ( $is_manager ) : ?>
			<section class="vt-panel" data-panel="approvals">
				<?php self::render_approvals( $employee ); ?>
			</section>
			<?php endif; ?>

			<div class="vt-toast" id="vt-toast" role="status" aria-live="polite"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	private static function status_label( $status ) {
		$labels = array(
			'draft'                  => 'Draft',
			'submitted'              => 'Submitted',
			'validation_failed'      => 'Validation Failed',
			'pending_team_lead'      => 'Pending Team Lead Approval',
			'pending_department'     => 'Pending Department Approval',
			'pending_hr'             => 'Pending HR Approval',
			'more_info_required'     => 'More Information Required',
			'approved'               => 'Approved',
			'rejected'               => 'Rejected',
			'cancellation_requested' => 'Cancellation Requested',
			'cancellation_pending'   => 'Cancellation Pending Approval',
			'cancelled'              => 'Cancelled',
			'withdrawn'              => 'Withdrawn',
			'expired'                => 'Expired',
			'system_error'           => 'System Error',
		);
		return isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( str_replace( '_', ' ', $status ) );
	}

	private static function render_dashboard( $employee ) {
		$year        = VT_Calc::current_vacation_year();
		$balance     = VT_Balances::get_or_create( $employee->employee_id, $year );
		$pending     = VT_Requests::get_pending_for_employee( $employee->employee_id );
		$upcoming    = VT_Requests::get_upcoming_approved( $employee->employee_id );
		$history     = VT_Requests::get_history( $employee->employee_id, 25 );
		$projected   = $balance ? (float) $balance->remaining_days - (float) $balance->pending_days : 0;
		?>
		<h2>Welcome, <?php echo esc_html( $employee->first_name ); ?></h2>

		<div class="vt-tiles">
			<div class="vt-tile"><span class="vt-tile-label">Annual Entitlement</span><span class="vt-tile-value"><?php echo esc_html( $balance->annual_entitlement ); ?></span></div>
			<div class="vt-tile"><span class="vt-tile-label">Used</span><span class="vt-tile-value"><?php echo esc_html( $balance->approved_days_used ); ?></span></div>
			<div class="vt-tile"><span class="vt-tile-label">Pending</span><span class="vt-tile-value"><?php echo esc_html( $balance->pending_days ); ?></span></div>
			<div class="vt-tile vt-tile-highlight"><span class="vt-tile-label">Available Now</span><span class="vt-tile-value"><?php echo esc_html( $balance->remaining_days ); ?></span></div>
			<div class="vt-tile"><span class="vt-tile-label">Projected if all Pending Approved</span><span class="vt-tile-value"><?php echo esc_html( $projected ); ?></span></div>
		</div>

		<h3>Pending Requests</h3>
		<?php if ( empty( $pending ) ) : ?>
			<p class="vt-empty">You have no pending requests. Use the "New Request" tab to submit one.</p>
		<?php else : ?>
			<table class="vt-table">
				<thead><tr><th>Dates</th><th>Days</th><th>Status</th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $pending as $req ) : ?>
					<tr>
						<td><?php echo esc_html( $req->start_date . ' - ' . $req->end_date ); ?></td>
						<td><?php echo esc_html( $req->total_days ); ?></td>
						<td><span class="vt-badge vt-badge-<?php echo esc_attr( $req->status ); ?>"><?php echo esc_html( self::status_label( $req->status ) ); ?></span></td>
						<td><button type="button" class="vt-btn vt-btn-small vt-action-withdraw" data-id="<?php echo esc_attr( $req->request_id ); ?>">Withdraw</button></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h3>Upcoming Approved Vacation</h3>
		<?php if ( empty( $upcoming ) ) : ?>
			<p class="vt-empty">No upcoming approved vacation.</p>
		<?php else : ?>
			<table class="vt-table">
				<thead><tr><th>Dates</th><th>Days</th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $upcoming as $req ) : ?>
					<tr>
						<td><?php echo esc_html( $req->start_date . ' - ' . $req->end_date ); ?></td>
						<td><?php echo esc_html( $req->total_days ); ?></td>
						<td><button type="button" class="vt-btn vt-btn-small vt-action-cancel" data-id="<?php echo esc_attr( $req->request_id ); ?>">Request Change/Cancellation</button></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h3>Request History</h3>
		<?php if ( empty( $history ) ) : ?>
			<p class="vt-empty">You haven't submitted any vacation requests yet.</p>
		<?php else : ?>
			<table class="vt-table">
				<thead><tr><th>Request</th><th>Dates</th><th>Days</th><th>Status</th></tr></thead>
				<tbody>
				<?php foreach ( $history as $req ) : ?>
					<tr>
						<td><?php echo esc_html( $req->request_code ); ?></td>
						<td><?php echo esc_html( $req->start_date . ' - ' . $req->end_date ); ?></td>
						<td><?php echo esc_html( $req->total_days ); ?></td>
						<td><span class="vt-badge vt-badge-<?php echo esc_attr( $req->status ); ?>"><?php echo esc_html( self::status_label( $req->status ) ); ?></span></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}

	private static function render_request_form( $employee ) {
		?>
		<h2>New Vacation Request</h2>
		<form id="vt-request-form" class="vt-form">
			<label>Leave Type
				<select name="leave_type">
					<option value="vacation">Vacation</option>
					<option value="sick">Sick</option>
					<option value="personal">Personal</option>
					<option value="unpaid">Unpaid</option>
					<option value="other">Other</option>
				</select>
			</label>
			<div class="vt-form-row">
				<label>Start Date <input type="date" name="start_date" required></label>
				<label>Start-Day Duration
					<select name="start_duration">
						<option value="full">Full Day</option>
						<option value="am">AM Half</option>
						<option value="pm">PM Half</option>
					</select>
				</label>
			</div>
			<div class="vt-form-row">
				<label>End Date <input type="date" name="end_date" required></label>
				<label>End-Day Duration
					<select name="end_duration">
						<option value="full">Full Day</option>
						<option value="am">AM Half</option>
						<option value="pm">PM Half</option>
					</select>
				</label>
			</div>
			<div class="vt-preview" id="vt-preview">
				<span>Working days: <strong id="vt-preview-days">-</strong></span>
				<span>Current balance: <strong id="vt-preview-balance">-</strong></span>
				<span>Projected after this request: <strong id="vt-preview-projected">-</strong></span>
			</div>
			<label>Comments
				<textarea name="comments" rows="3" placeholder="Optional note for your approver"></textarea>
			</label>
			<input type="hidden" name="request_id" value="0">
			<div class="vt-warning" id="vt-form-warning" hidden></div>
			<div class="vt-form-actions">
				<button type="button" class="vt-btn vt-btn-secondary" id="vt-save-draft">Save Draft</button>
				<button type="submit" class="vt-btn vt-btn-primary">Submit Request</button>
			</div>
		</form>
		<div class="vt-confirmation" id="vt-confirmation" hidden>
			<p>Your request has been submitted. You'll receive an email confirmation shortly.</p>
		</div>
		<?php
	}

	private static function render_calendar( $employee, $is_manager ) {
		$dept_id = current_user_can( 'vt_manage_all' ) ? null : $employee->department_id;
		$entries = VT_Requests::get_calendar_entries( $dept_id );
		?>
		<h2>Team Vacation Calendar</h2>
		<p class="vt-hint">Privacy-safe view - names and dates only.</p>
		<?php if ( empty( $entries ) ) : ?>
			<p class="vt-empty">No upcoming approved absences in your view.</p>
		<?php else : ?>
			<table class="vt-table">
				<thead><tr><th>Employee</th><th>Dates</th></tr></thead>
				<tbody>
				<?php foreach ( $entries as $entry ) : ?>
					<tr>
						<td><?php echo esc_html( $entry->first_name . ' ' . $entry->last_name ); ?> - Away</td>
						<td><?php echo esc_html( $entry->start_date . ' - ' . $entry->end_date ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}

	private static function render_approvals( $employee ) {
		$pending = VT_Requests::get_pending_for_approver( $employee->employee_id );
		?>
		<h2>Approvals</h2>
		<?php if ( empty( $pending ) ) : ?>
			<p class="vt-empty">You have no pending approvals. You're all caught up.</p>
		<?php else : ?>
			<table class="vt-table">
				<thead><tr><th>Employee</th><th>Dates</th><th>Days</th><th>Status</th><th>Action</th></tr></thead>
				<tbody>
				<?php foreach ( $pending as $req ) :
					$req_employee = VT_Balances::get_employee( $req->employee_id );
					$is_cancellation = ( 'cancellation_pending' === $req->status );
					?>
					<tr>
						<td><?php echo esc_html( $req_employee ? $req_employee->first_name . ' ' . $req_employee->last_name : '-' ); ?></td>
						<td><?php echo esc_html( $req->start_date . ' - ' . $req->end_date ); ?></td>
						<td><?php echo esc_html( $req->total_days ); ?></td>
						<td><span class="vt-badge vt-badge-<?php echo esc_attr( $req->status ); ?>"><?php echo esc_html( self::status_label( $req->status ) ); ?></span></td>
						<td>
							<?php if ( $is_cancellation ) : ?>
								<button type="button" class="vt-btn vt-btn-small vt-action-cancel-decide" data-id="<?php echo esc_attr( $req->request_id ); ?>" data-approved="yes">Approve Cancellation</button>
								<button type="button" class="vt-btn vt-btn-small vt-action-cancel-decide" data-id="<?php echo esc_attr( $req->request_id ); ?>" data-approved="no">Deny</button>
							<?php else : ?>
								<button type="button" class="vt-btn vt-btn-small vt-action-decide" data-id="<?php echo esc_attr( $req->request_id ); ?>" data-decision="approve">Approve</button>
								<button type="button" class="vt-btn vt-btn-small vt-action-decide" data-id="<?php echo esc_attr( $req->request_id ); ?>" data-decision="reject">Reject</button>
								<button type="button" class="vt-btn vt-btn-small vt-action-decide" data-id="<?php echo esc_attr( $req->request_id ); ?>" data-decision="more_info">Request Info</button>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h3>Delegate My Approval Authority</h3>
		<form id="vt-delegation-form" class="vt-form">
			<label>Delegate to (WordPress user ID) <input type="number" name="delegate_user_id" required></label>
			<div class="vt-form-row">
				<label>Start Date <input type="date" name="start_date" required></label>
				<label>End Date <input type="date" name="end_date" required></label>
			</div>
			<label>Note <input type="text" name="scope_note" placeholder="e.g. all my team's requests"></label>
			<button type="submit" class="vt-btn vt-btn-primary">Save Delegation</button>
		</form>
		<?php
	}
}
