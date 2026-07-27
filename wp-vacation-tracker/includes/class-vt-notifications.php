<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email notifications (blueprint Sections 11-12). Mobile-friendly, single-column
 * HTML, and deliberately excludes any confidential data the recipient isn't
 * entitled to see.
 */
class VT_Notifications {

	private static function send( $to, $subject, $body_html ) {
		$sender_name = VT_Settings::get( 'email_sender_name', get_bloginfo( 'name' ) );
		add_filter( 'wp_mail_from_name', function () use ( $sender_name ) {
			return $sender_name;
		} );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$wrapped = self::wrap_template( $body_html );
		$sent    = wp_mail( $to, $subject, $wrapped, $headers );

		if ( ! $sent ) {
			VT_Audit::log_error( 'email-send', "Failed to send '{$subject}' to {$to}" );
		}
		return $sent;
	}

	private static function wrap_template( $inner ) {
		return '<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;color:#222;">'
			. '<div style="padding:20px;">' . $inner . '</div>'
			. '<div style="padding:16px 20px;color:#888;font-size:12px;border-top:1px solid #eee;">'
			. esc_html( get_bloginfo( 'name' ) ) . ' Vacation Tracker &mdash; automated message, please do not reply.'
			. '</div></div>';
	}

	private static function button( $url, $label, $color = '#2d7a4a' ) {
		return '<a href="' . esc_url( $url ) . '" style="display:inline-block;background:' . esc_attr( $color ) . ';color:#fff;padding:12px 20px;border-radius:6px;text-decoration:none;font-weight:bold;margin:6px 6px 6px 0;">' . esc_html( $label ) . '</a>';
	}

	private static function portal_url() {
		$page_id = VT_Settings::get( 'portal_page_id' );
		return $page_id ? get_permalink( $page_id ) : home_url( '/' );
	}

	private static function format_date( $date ) {
		$format = VT_Settings::get( 'date_format', 'd M Y' );
		return date_i18n( $format, strtotime( $date ) );
	}

	public static function submission_confirmation( $employee, $request, $balance ) {
		$body = "<p>Hi {$employee->first_name},</p>"
			. '<p>Your vacation request has been submitted and is now awaiting approval.</p>'
			. self::detail_table(
				array(
					'Request ID'                       => esc_html( $request->request_code ),
					'Dates requested'                  => self::format_date( $request->start_date ) . ' - ' . self::format_date( $request->end_date ),
					'Working days'                      => esc_html( $request->total_days ),
					'Status'                            => esc_html( self::status_label( $request->status ) ),
					'Current balance'                   => esc_html( $balance->remaining_days ),
					'Projected balance if approved'     => esc_html( $balance->remaining_days - $request->total_days ),
				)
			)
			. self::button( self::portal_url(), 'View my request' );

		self::send( $employee->work_email, "Vacation Request Submitted - {$request->request_code}", $body );
	}

	public static function validation_failed( $employee, $request, $reason ) {
		$body = "<p>Hi {$employee->first_name},</p>"
			. '<p>Your vacation request could not be submitted:</p>'
			. '<p style="background:#fdecea;padding:12px;border-radius:6px;color:#a33;">' . esc_html( $reason ) . '</p>'
			. self::button( self::portal_url(), 'Review and resubmit' );

		self::send( $employee->work_email, "Action needed on your vacation request", $body );
	}

	public static function approval_request( $approver, $employee, $request, $balance, $warnings = array() ) {
		$warning_html = '';
		if ( ! empty( $warnings ) ) {
			$warning_html = '<div style="background:#fff8e1;padding:10px;border-radius:6px;margin:10px 0;">'
				. implode( '<br>', array_map( 'esc_html', $warnings ) ) . '</div>';
		}

		$body = "<p>Hi {$approver->first_name},</p>"
			. "<p><strong>{$employee->first_name} {$employee->last_name}</strong> has requested vacation and needs your decision.</p>"
			. self::detail_table(
				array(
					'Employee'          => esc_html( $employee->first_name . ' ' . $employee->last_name ),
					'Dates requested'   => self::format_date( $request->start_date ) . ' - ' . self::format_date( $request->end_date ),
					'Working days'      => esc_html( $request->total_days ),
					'Employee comment'  => esc_html( $request->employee_comments ),
					'Current balance'   => esc_html( $balance->remaining_days ),
					'Pending (incl. this)' => esc_html( $balance->pending_days ),
				)
			)
			. $warning_html
			. self::button( self::portal_url(), 'Review and decide' );

		self::send( $approver->work_email, "Approval needed - {$employee->first_name} {$employee->last_name}, {$request->total_days} day(s)", $body );
	}

	public static function approval_confirmation( $employee, $request, $balance ) {
		$body = "<p>Hi {$employee->first_name},</p>"
			. '<p>Good news - your vacation request has been approved.</p>'
			. self::detail_table(
				array(
					'Approved dates'   => self::format_date( $request->start_date ) . ' - ' . self::format_date( $request->end_date ),
					'Working days'     => esc_html( $request->total_days ),
					'Updated balance'  => esc_html( $balance->remaining_days ),
					'Approver comment' => esc_html( $request->approver_comments ),
				)
			)
			. '<p>Need to change or cancel this? Use "Request a change or cancellation" on your dashboard.</p>'
			. self::button( self::portal_url(), 'View my dashboard' );

		self::send( $employee->work_email, "Your vacation request is approved - {$request->request_code}", $body );
	}

	public static function rejection_notice( $employee, $request, $balance ) {
		$body = "<p>Hi {$employee->first_name},</p>"
			. '<p>Your vacation request was <strong>not approved</strong>.</p>'
			. self::detail_table(
				array(
					'Requested dates'  => self::format_date( $request->start_date ) . ' - ' . self::format_date( $request->end_date ),
					'Manager comment'  => esc_html( $request->approver_comments ),
					'Current balance'  => esc_html( $balance->remaining_days ) . ' (unchanged)',
				)
			)
			. self::button( self::portal_url(), 'Submit a new request' );

		self::send( $employee->work_email, "Update on your vacation request - {$request->request_code}", $body );
	}

	public static function more_info_required( $employee, $request ) {
		$body = "<p>Hi {$employee->first_name},</p>"
			. '<p>Your approver has a question before making a decision:</p>'
			. '<blockquote style="border-left:3px solid #ccc;margin:10px 0;padding:8px 12px;color:#555;">' . esc_html( $request->approver_comments ) . '</blockquote>'
			. self::button( self::portal_url(), 'Respond to this request' );

		self::send( $employee->work_email, "Question about your vacation request - {$request->request_code}", $body );
	}

	public static function reminder( $approver, $employee, $request, $reminder_number ) {
		$label = ( 1 === $reminder_number ) ? 'Reminder' : 'Second reminder';
		$body  = "<p>Hi {$approver->first_name},</p>"
			. "<p>{$label}: {$employee->first_name} {$employee->last_name}'s vacation request ("
			. self::format_date( $request->start_date ) . ' - ' . self::format_date( $request->end_date )
			. ') is still awaiting your decision.</p>'
			. self::button( self::portal_url(), 'Review and decide' );

		self::send( $approver->work_email, "Reminder - approval needed for {$employee->first_name} {$employee->last_name}", $body );
	}

	public static function escalation( $new_approver, $original_approver, $employee, $request ) {
		$body = "<p>Hi {$new_approver->first_name},</p>"
			. "<p>{$employee->first_name} {$employee->last_name}'s vacation request ("
			. self::format_date( $request->start_date ) . ' - ' . self::format_date( $request->end_date )
			. ") has not been actioned within the standard response window and has been escalated to you.</p>"
			. self::button( self::portal_url(), 'Review and decide' );

		self::send( $new_approver->work_email, "Escalated - no decision on {$employee->first_name} {$employee->last_name}'s request", $body );

		$hr = VT_Settings::get( 'hr_notification_email' );
		if ( $hr ) {
			self::send( $hr, "FYI: Approval escalated for {$employee->first_name} {$employee->last_name}",
				"<p>Request {$request->request_code} was escalated from {$original_approver->first_name} {$original_approver->last_name} to {$new_approver->first_name} {$new_approver->last_name} after the standard response window.</p>" );
		}
	}

	public static function cancellation_request_to_approver( $approver, $employee, $request ) {
		$body = "<p>Hi {$approver->first_name},</p>"
			. "<p>{$employee->first_name} {$employee->last_name} has requested to cancel/change their approved vacation ("
			. self::format_date( $request->start_date ) . ' - ' . self::format_date( $request->end_date ) . ').</p>'
			. self::button( self::portal_url(), 'Review and decide' );
		self::send( $approver->work_email, "Cancellation approval needed - {$employee->first_name} {$employee->last_name}", $body );
	}

	public static function cancellation_requested( $employee, $request ) {
		$body = "<p>Hi {$employee->first_name},</p>"
			. '<p>Your request to cancel/change your approved vacation has been submitted and is awaiting approval.</p>'
			. self::button( self::portal_url(), 'View status' );
		self::send( $employee->work_email, "Cancellation request submitted - {$request->request_code}", $body );
	}

	public static function cancellation_decision( $employee, $request, $approved ) {
		$outcome = $approved ? 'approved' : 'not approved';
		$body    = "<p>Hi {$employee->first_name},</p>"
			. "<p>Your cancellation request for {$request->request_code} was <strong>{$outcome}</strong>.</p>"
			. self::button( self::portal_url(), 'View my dashboard' );
		self::send( $employee->work_email, "Cancellation request update - {$request->request_code}", $body );
	}

	public static function upcoming_vacation_reminder( $employee, $request ) {
		$body = "<p>Hi {$employee->first_name},</p>"
			. '<p>A reminder that your approved vacation begins soon:</p>'
			. self::detail_table(
				array( 'Dates' => self::format_date( $request->start_date ) . ' - ' . self::format_date( $request->end_date ) )
			);
		self::send( $employee->work_email, "Upcoming vacation reminder - {$request->request_code}", $body );
	}

	public static function carry_forward_expiry_reminder( $employee, $days_expiring, $expiry_date ) {
		$body = "<p>Hi {$employee->first_name},</p>"
			. "<p>You have <strong>{$days_expiring} day(s)</strong> of carried-forward vacation that will expire on "
			. self::format_date( $expiry_date ) . ' if unused.</p>'
			. self::button( self::portal_url(), 'Submit a vacation request' );
		self::send( $employee->work_email, "Your carried-forward vacation is expiring soon", $body );
	}

	private static function status_label( $status ) {
		$labels = array(
			'draft'              => 'Draft',
			'submitted'          => 'Submitted',
			'validation_failed'  => 'Validation Failed',
			'pending_team_lead'  => 'Pending Team Lead Approval',
			'pending_department' => 'Pending Department Approval',
			'pending_hr'         => 'Pending HR Approval',
			'more_info_required' => 'More Information Required',
			'approved'           => 'Approved',
			'rejected'           => 'Rejected',
			'cancellation_requested' => 'Cancellation Requested',
			'cancellation_pending'   => 'Cancellation Pending Approval',
			'cancelled'          => 'Cancelled',
			'withdrawn'          => 'Withdrawn',
			'expired'            => 'Expired',
			'system_error'       => 'System Error',
		);
		return isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );
	}

	private static function detail_table( $rows ) {
		$html = '<table style="width:100%;border-collapse:collapse;margin:14px 0;">';
		foreach ( $rows as $label => $value ) {
			if ( '' === trim( (string) $value ) ) {
				continue;
			}
			$html .= '<tr>'
				. '<td style="padding:6px 8px;color:#666;border-bottom:1px solid #eee;white-space:nowrap;"><strong>' . esc_html( $label ) . '</strong></td>'
				. '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' . $value . '</td>'
				. '</tr>';
		}
		$html .= '</table>';
		return $html;
	}
}
