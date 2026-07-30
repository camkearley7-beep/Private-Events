<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end AJAX endpoints. Every handler re-checks the nonce, that the
 * user is logged in, and that they hold the relevant capability - the
 * server is the enforcement point, not just which buttons the page shows.
 */
class VT_Ajax {

	public static function init() {
		$actions = array(
			'vt_preview_days',
			'vt_save_draft',
			'vt_submit_request',
			'vt_withdraw_request',
			'vt_request_cancellation',
			'vt_decide',
			'vt_decide_cancellation',
			'vt_set_delegation',
			'vt_mark_notifications_read',
		);
		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_' . $action, array( __CLASS__, $action ) );
		}
	}

	private static function verify() {
		check_ajax_referer( 'vt_frontend_nonce', 'nonce' );
		if ( ! is_user_logged_in() || ! current_user_can( 'vt_use_portal' ) ) {
			wp_send_json_error( array( 'message' => 'Not authorized.' ), 403 );
		}
	}

	private static function current_employee() {
		$employee = VT_Requests::get_employee_by_wp_user( get_current_user_id() );
		if ( ! $employee ) {
			wp_send_json_error( array( 'message' => 'No employee profile is linked to your account yet. Contact HR.' ), 404 );
		}
		return $employee;
	}

	public static function vt_preview_days() {
		self::verify();
		$employee = self::current_employee();
		$calc = VT_Calc::calculate_chargeable_days(
			$employee,
			sanitize_text_field( $_POST['start_date'] ?? '' ),
			sanitize_text_field( $_POST['end_date'] ?? '' ),
			sanitize_text_field( $_POST['start_duration'] ?? 'full' ),
			sanitize_text_field( $_POST['end_duration'] ?? 'full' )
		);
		$balance = VT_Balances::get_or_create( $employee->employee_id, VT_Calc::current_vacation_year() );
		wp_send_json_success(
			array(
				'days'      => $calc['days'],
				'hours'     => $calc['hours'],
				'balance'   => $balance ? (float) $balance->remaining_days : 0,
				'projected' => $balance ? (float) $balance->remaining_days - $calc['days'] : 0,
			)
		);
	}

	public static function vt_save_draft() {
		self::verify();
		$employee = self::current_employee();
		$id = VT_Requests::save_draft(
			$employee->employee_id,
			array(
				'leave_type'     => sanitize_text_field( $_POST['leave_type'] ?? 'vacation' ),
				'start_date'     => sanitize_text_field( $_POST['start_date'] ?? '' ),
				'end_date'       => sanitize_text_field( $_POST['end_date'] ?? '' ),
				'start_duration' => sanitize_text_field( $_POST['start_duration'] ?? 'full' ),
				'end_duration'   => sanitize_text_field( $_POST['end_duration'] ?? 'full' ),
				'comments'       => sanitize_textarea_field( $_POST['comments'] ?? '' ),
			),
			absint( $_POST['request_id'] ?? 0 )
		);
		wp_send_json_success( array( 'request_id' => $id, 'message' => 'Draft saved.' ) );
	}

	public static function vt_submit_request() {
		self::verify();
		$employee = self::current_employee();
		$id = VT_Requests::save_draft(
			$employee->employee_id,
			array(
				'leave_type'     => sanitize_text_field( $_POST['leave_type'] ?? 'vacation' ),
				'start_date'     => sanitize_text_field( $_POST['start_date'] ?? '' ),
				'end_date'       => sanitize_text_field( $_POST['end_date'] ?? '' ),
				'start_duration' => sanitize_text_field( $_POST['start_duration'] ?? 'full' ),
				'end_duration'   => sanitize_text_field( $_POST['end_duration'] ?? 'full' ),
				'comments'       => sanitize_textarea_field( $_POST['comments'] ?? '' ),
			),
			absint( $_POST['request_id'] ?? 0 )
		);
		$result = VT_Requests::submit( $id );
		if ( $result['ok'] ) {
			wp_send_json_success( $result );
		}
		wp_send_json_error( $result );
	}

	public static function vt_withdraw_request() {
		self::verify();
		$employee = self::current_employee();
		$result = VT_Requests::withdraw( absint( $_POST['request_id'] ?? 0 ), $employee->employee_id );
		$result['ok'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
	}

	public static function vt_request_cancellation() {
		self::verify();
		$employee = self::current_employee();
		$result = VT_Requests::request_cancellation( absint( $_POST['request_id'] ?? 0 ), $employee->employee_id );
		$result['ok'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
	}

	public static function vt_decide() {
		self::verify();
		if ( ! current_user_can( 'vt_manage_team' ) && ! current_user_can( 'vt_manage_all' ) ) {
			wp_send_json_error( array( 'message' => 'Not authorized.' ), 403 );
		}
		$decision = sanitize_text_field( $_POST['decision'] ?? '' );
		if ( ! in_array( $decision, array( 'approve', 'reject', 'more_info' ), true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid decision.' ) );
		}
		$result = VT_Requests::decide(
			absint( $_POST['request_id'] ?? 0 ),
			$decision,
			sanitize_textarea_field( $_POST['comments'] ?? '' ),
			get_current_user_id()
		);
		$result['ok'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
	}

	public static function vt_decide_cancellation() {
		self::verify();
		if ( ! current_user_can( 'vt_manage_team' ) && ! current_user_can( 'vt_manage_all' ) ) {
			wp_send_json_error( array( 'message' => 'Not authorized.' ), 403 );
		}
		$result = VT_Requests::decide_cancellation(
			absint( $_POST['request_id'] ?? 0 ),
			'yes' === sanitize_text_field( $_POST['approved'] ?? '' ),
			get_current_user_id()
		);
		$result['ok'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
	}

	public static function vt_set_delegation() {
		self::verify();
		if ( ! current_user_can( 'vt_manage_team' ) ) {
			wp_send_json_error( array( 'message' => 'Not authorized.' ), 403 );
		}
		$employee = self::current_employee();
		global $wpdb;
		$delegate_wp_user_id = absint( $_POST['delegate_user_id'] ?? 0 );
		$delegate = VT_Requests::get_employee_by_wp_user( $delegate_wp_user_id );
		if ( ! $delegate ) {
			wp_send_json_error( array( 'message' => 'Select a valid delegate.' ) );
		}
		$wpdb->insert(
			VT_DB::delegations(),
			array(
				'original_approver_id' => $employee->employee_id,
				'delegate_approver_id' => $delegate->employee_id,
				'start_date'           => sanitize_text_field( $_POST['start_date'] ?? current_time( 'Y-m-d' ) ),
				'end_date'             => sanitize_text_field( $_POST['end_date'] ?? current_time( 'Y-m-d' ) ),
				'scope_note'           => sanitize_text_field( $_POST['scope_note'] ?? '' ),
				'active'               => 1,
				'created_at'           => current_time( 'mysql' ),
			)
		);
		VT_Audit::log( 'Delegation', $employee->employee_id, 'Created', null, array( 'delegate' => $delegate->employee_id ), null );
		wp_send_json_success( array( 'message' => 'Delegation saved.' ) );
	}

	public static function vt_mark_notifications_read() {
		self::verify();
		VT_Notifications::mark_all_read( get_current_user_id() );
		wp_send_json_success();
	}
}
