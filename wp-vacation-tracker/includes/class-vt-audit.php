<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Write-only audit trail. Never provide an update/delete method here on purpose -
 * corrections must be new rows, not edits to history.
 */
class VT_Audit {

	public static function log( $record_type, $record_id, $action, $previous_value, $new_value, $performed_by = null, $comments = '', $workflow_ref = '' ) {
		global $wpdb;

		if ( null === $performed_by ) {
			$user         = wp_get_current_user();
			$performed_by = $user && $user->exists() ? $user->user_login : 'System';
		}

		$wpdb->insert(
			VT_DB::audit_log(),
			array(
				'record_type'    => sanitize_text_field( $record_type ),
				'record_id'      => sanitize_text_field( (string) $record_id ),
				'action'         => sanitize_text_field( $action ),
				'previous_value' => is_array( $previous_value ) || is_object( $previous_value ) ? wp_json_encode( $previous_value ) : $previous_value,
				'new_value'      => is_array( $new_value ) || is_object( $new_value ) ? wp_json_encode( $new_value ) : $new_value,
				'performed_by'   => sanitize_text_field( $performed_by ),
				'created_at'     => current_time( 'mysql' ),
				'workflow_ref'   => sanitize_text_field( $workflow_ref ),
				'comments'       => sanitize_textarea_field( $comments ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	public static function log_error( $source, $message, $context = array() ) {
		global $wpdb;
		$wpdb->insert(
			VT_DB::error_log(),
			array(
				'source'     => sanitize_text_field( $source ),
				'message'    => sanitize_textarea_field( $message ),
				'context'    => wp_json_encode( $context ),
				'resolved'   => 0,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%d', '%s' )
		);

		$to = VT_Settings::get( 'system_admin_notification_email' );
		if ( $to ) {
			wp_mail(
				$to,
				'[Action needed] Vacation Tracker error: ' . $source,
				"A workflow error occurred.\n\nSource: {$source}\nMessage: {$message}\n\nCheck Vacation Tracker > System Log in wp-admin for details."
			);
		}
	}
}
