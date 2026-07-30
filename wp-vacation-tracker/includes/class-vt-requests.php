<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Vacation request state machine and approval-routing engine
 * (blueprint Sections 8-9). The Status/Stage fields are only ever written
 * by the methods in this class (never directly by a form field), which is
 * what keeps the state machine the single path for every transition.
 */
class VT_Requests {

	const PENDING_STATUSES = array( 'submitted', 'pending_team_lead', 'pending_department', 'pending_hr', 'more_info_required' );

	public static function get( $request_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . VT_DB::requests() . " WHERE request_id = %d", $request_id ) );
	}

	public static function get_employee_by_wp_user( $wp_user_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . VT_DB::employees() . " WHERE wp_user_id = %d", $wp_user_id ) );
	}

	private static function flags( $request ) {
		$flags = json_decode( $request->flags, true );
		return is_array( $flags ) ? $flags : array();
	}

	/**
	 * Create (or update) a draft request.
	 */
	public static function save_draft( $employee_id, $data, $request_id = 0 ) {
		global $wpdb;
		$now = current_time( 'mysql' );

		$fields = array(
			'employee_id'        => $employee_id,
			'leave_type'         => sanitize_text_field( $data['leave_type'] ),
			'start_date'         => sanitize_text_field( $data['start_date'] ),
			'end_date'           => sanitize_text_field( $data['end_date'] ),
			'start_duration'     => sanitize_text_field( $data['start_duration'] ),
			'end_duration'       => sanitize_text_field( $data['end_duration'] ),
			'employee_comments'  => sanitize_textarea_field( $data['comments'] ),
			'updated_at'         => $now,
		);

		if ( $request_id ) {
			$wpdb->update( VT_DB::requests(), $fields, array( 'request_id' => $request_id, 'employee_id' => $employee_id ) );
			return $request_id;
		}

		$fields['status']       = 'draft';
		$fields['request_code'] = 'VR-' . current_time( 'Y' ) . '-' . strtoupper( wp_generate_password( 6, false ) );
		$fields['created_by']   = get_current_user_id();
		$fields['created_at']   = $now;

		$wpdb->insert( VT_DB::requests(), $fields );
		return $wpdb->insert_id;
	}

	/**
	 * Submit (validate + route) a request. Returns array( 'ok' => bool, 'message' => string, 'request_id' => int ).
	 */
	public static function submit( $request_id, $acting_admin_wp_user_id = 0 ) {
		global $wpdb;
		$request = self::get( $request_id );
		if ( ! $request ) {
			return array( 'ok' => false, 'message' => 'Request not found.' );
		}
		$employee = VT_Balances::get_employee( $request->employee_id );
		if ( ! $employee || ! $employee->active ) {
			VT_Audit::log_error( 'submit-request', "Employee record missing/inactive for request {$request->request_code}" );
			return array( 'ok' => false, 'message' => 'Employee record could not be found. HR has been notified.' );
		}

		// 1. Duplicate / overlap check.
		$overlap = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT request_id FROM " . VT_DB::requests() . "
				 WHERE employee_id = %d AND request_id != %d
				 AND status NOT IN ('draft','rejected','cancelled','withdrawn','expired','validation_failed')
				 AND start_date <= %s AND end_date >= %s",
				$employee->employee_id,
				$request_id,
				$request->end_date,
				$request->start_date
			)
		);
		if ( $overlap ) {
			self::mark_validation_failed( $request, $employee, 'You already have a request that overlaps these dates.' );
			return array( 'ok' => false, 'message' => 'You already have a request for overlapping dates.' );
		}

		// 2. Calculate chargeable days (per-year split for cross-year requests).
		$splits = VT_Calc::calculate_cross_year_split( $employee, $request->start_date, $request->end_date, $request->start_duration, $request->end_duration );
		$total_days  = array_sum( wp_list_pluck( $splits, 'days' ) );
		$total_hours = array_sum( wp_list_pluck( $splits, 'hours' ) );

		if ( $total_days <= 0 ) {
			self::mark_validation_failed( $request, $employee, 'The selected dates contain no chargeable working days.' );
			return array( 'ok' => false, 'message' => 'The selected dates contain no chargeable working days (weekends/holidays only).' );
		}

		// 3. Balance check per affected year.
		$over_balance = false;
		foreach ( $splits as $year => $calc ) {
			$balance = VT_Balances::get_or_create( $employee->employee_id, $year );
			if ( $balance && $calc['days'] > (float) $balance->remaining_days ) {
				$over_balance = true;
			}
		}
		if ( $over_balance && ! VT_Settings::get_bool( 'allow_over_balance_requests', true ) ) {
			self::mark_validation_failed( $request, $employee, 'This request exceeds your available vacation balance.' );
			return array( 'ok' => false, 'message' => 'This request exceeds your available balance and over-balance requests are not permitted. Please adjust the dates.' );
		}

		// 4. Blackout period check.
		$blackout = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM " . VT_DB::blackout_periods() . "
				 WHERE active = 1 AND start_date <= %s AND end_date >= %s
				 AND ( department_id IS NULL OR department_id = %d )",
				$request->end_date,
				$request->start_date,
				$employee->department_id
			)
		);
		$blackout_block = $blackout && 'block' === $blackout->mode;
		$blackout_warn  = $blackout && 'warn' === $blackout->mode;

		// 5. Long request check.
		$long_request = $total_days >= VT_Settings::get_number( 'long_request_threshold_days', 5 );

		// 6. Staffing conflict check.
		$conflict = false;
		if ( VT_Settings::get_bool( 'staffing_conflict_check_enabled', true ) && $employee->department_id ) {
			$dept = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . VT_DB::departments() . " WHERE department_id = %d", $employee->department_id ) );
			if ( $dept && $dept->max_away ) {
				$away_count = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM " . VT_DB::requests() . " r
						 JOIN " . VT_DB::employees() . " e ON e.employee_id = r.employee_id
						 WHERE e.department_id = %d AND r.status = 'approved'
						 AND r.start_date <= %s AND r.end_date >= %s",
						$employee->department_id,
						$request->end_date,
						$request->start_date
					)
				);
				if ( ( $away_count + 1 ) > (int) $dept->max_away ) {
					$conflict = true;
				}
			}
		}

		// Build the stage sequence.
		$is_own_team_lead = (bool) $wpdb->get_var(
			$wpdb->prepare( "SELECT employee_id FROM " . VT_DB::employees() . " WHERE primary_team_lead_id = %d LIMIT 1", $employee->employee_id )
		);

		$stages = array();
		if ( $blackout_block ) {
			$stages = array( 'hr' );
		} elseif ( $is_own_team_lead ) {
			$stages[] = 'department';
		} else {
			$stages[] = 'team_lead';
		}
		if ( ( $long_request || $conflict ) && ! in_array( 'department', $stages, true ) ) {
			$stages[] = 'department';
		}
		if ( ( $over_balance || $blackout_warn ) && ! in_array( 'hr', $stages, true ) ) {
			$stages[] = 'hr';
		}

		$initial_stage = $stages[0];
		$remaining     = array_slice( $stages, 1 );

		$resolution = self::resolve_approver_for_stage( $employee, $initial_stage, $blackout );
		if ( ! $resolution['approver'] ) {
			self::mark_validation_failed( $request, $employee, 'No approver is configured for this request. HR has been notified to fix this.' );
			VT_Audit::log_error( 'routing', "No approver resolvable for employee {$employee->employee_id}, stage {$initial_stage}" );
			return array( 'ok' => false, 'message' => 'No approver is configured yet. HR has been notified.' );
		}

		$flags = array(
			'over_balance'   => $over_balance,
			'blackout'       => $blackout ? $blackout->mode : null,
			'long_request'   => $long_request,
			'conflict'       => $conflict,
			'remaining'      => $remaining,
			'splits'         => wp_list_pluck( $splits, 'days' ),
			'acting_for'     => $resolution['acting_for'],
			'reminder1_sent' => false,
			'reminder2_sent' => false,
			'escalated'      => false,
		);

		$now = current_time( 'mysql' );
		$wpdb->update(
			VT_DB::requests(),
			array(
				'total_days'          => $total_days,
				'total_hours'         => $total_hours,
				'status'              => 'pending_' . $initial_stage,
				'current_stage'       => $initial_stage,
				'stage_entered_at'    => $now,
				'assigned_approver_id' => $resolution['approver']->employee_id,
				'date_submitted'      => $request->date_submitted ? $request->date_submitted : $now,
				'flags'               => wp_json_encode( $flags ),
				'created_by'          => $acting_admin_wp_user_id ? $acting_admin_wp_user_id : $request->created_by,
				'updated_at'          => $now,
			),
			array( 'request_id' => $request_id )
		);

		// Add to pending balance per affected year.
		foreach ( $splits as $year => $calc ) {
			VT_Balances::add_pending( $employee->employee_id, $year, $calc['days'] );
		}
		$wpdb->update( VT_DB::requests(), array( 'pending_applied' => 1 ), array( 'request_id' => $request_id ) );

		VT_Audit::log( 'Request', $request->request_code, 'Submitted', null, array( 'status' => 'pending_' . $initial_stage, 'total_days' => $total_days ), null );

		$request  = self::get( $request_id );
		$balance0 = VT_Balances::get_or_create( $employee->employee_id, (int) substr( $request->start_date, 0, 4 ) );
		VT_Notifications::submission_confirmation( $employee, $request, $balance0 );

		$warnings = array();
		if ( $blackout_warn ) {
			$warnings[] = 'Warning: these dates fall within a blackout period (' . esc_html( $blackout->name ) . ').';
		}
		if ( $conflict ) {
			$warnings[] = 'Warning: approving this would exceed the department\'s maximum simultaneous absences.';
		}
		if ( $over_balance ) {
			$warnings[] = 'Warning: this request exceeds the employee\'s current balance.';
		}
		VT_Notifications::approval_request( $resolution['approver'], $employee, $request, $balance0, $warnings );

		return array( 'ok' => true, 'message' => 'Request submitted.', 'request_id' => $request_id );
	}

	private static function mark_validation_failed( $request, $employee, $reason ) {
		global $wpdb;
		$wpdb->update(
			VT_DB::requests(),
			array( 'status' => 'validation_failed', 'updated_at' => current_time( 'mysql' ) ),
			array( 'request_id' => $request->request_id )
		);
		VT_Audit::log( 'Request', $request->request_code, 'Validation-Failed', null, array( 'reason' => $reason ), null );
		VT_Notifications::validation_failed( $employee, $request, $reason );
	}

	/**
	 * Resolve who should act at a given stage, substituting an active delegate if one exists.
	 */
	private static function resolve_approver_for_stage( $employee, $stage, $blackout = null ) {
		global $wpdb;
		$candidate_id = null;

		if ( 'team_lead' === $stage ) {
			if ( ! empty( $employee->is_executive ) && VT_Settings::get( 'executive_approver_employee_id' ) ) {
				$candidate_id = (int) VT_Settings::get( 'executive_approver_employee_id' );
			} else {
				$candidate_id = $employee->primary_team_lead_id;
			}
		} elseif ( 'department' === $stage ) {
			$dept = $employee->department_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . VT_DB::departments() . " WHERE department_id = %d", $employee->department_id ) ) : null;
			$candidate_id = $dept ? $dept->manager_employee_id : null;
		} elseif ( 'hr' === $stage ) {
			if ( $blackout && $blackout->exception_approver_id ) {
				$candidate_id = $blackout->exception_approver_id;
			} elseif ( $employee->hr_administrator_id ) {
				$candidate_id = $employee->hr_administrator_id;
			} else {
				$candidate_id = self::get_default_hr_employee_id();
			}
		}

		if ( ! $candidate_id ) {
			return array( 'approver' => null, 'acting_for' => null );
		}

		$today = current_time( 'Y-m-d' );
		$delegation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM " . VT_DB::delegations() . "
				 WHERE original_approver_id = %d AND active = 1 AND start_date <= %s AND end_date >= %s",
				$candidate_id,
				$today,
				$today
			)
		);

		$final_id   = $delegation ? $delegation->delegate_approver_id : $candidate_id;
		$acting_for = $delegation ? $candidate_id : null;
		$approver   = VT_Balances::get_employee( $final_id );

		return array( 'approver' => $approver, 'acting_for' => $acting_for );
	}

	private static function get_default_hr_employee_id() {
		global $wpdb;
		$hr_users = get_users( array( 'role' => 'vt_hr_admin', 'fields' => 'ID' ) );
		foreach ( $hr_users as $user_id ) {
			$emp = self::get_employee_by_wp_user( $user_id );
			if ( $emp ) {
				return $emp->employee_id;
			}
		}
		return null;
	}

	/**
	 * Approver decision: 'approve' | 'reject' | 'more_info'.
	 */
	public static function decide( $request_id, $decision, $comments, $acting_wp_user_id ) {
		global $wpdb;
		$request = self::get( $request_id );
		if ( ! $request || ! in_array( $request->status, array( 'pending_team_lead', 'pending_department', 'pending_hr' ), true ) ) {
			return array( 'ok' => false, 'message' => 'This request is not awaiting your decision.' );
		}
		if ( ! self::can_act_as_approver( $request, $acting_wp_user_id ) ) {
			return array( 'ok' => false, 'message' => 'You are not authorized to decide this request.' );
		}

		$employee = VT_Balances::get_employee( $request->employee_id );
		$flags    = self::flags( $request );
		$now      = current_time( 'mysql' );

		if ( 'more_info' === $decision ) {
			$wpdb->update(
				VT_DB::requests(),
				array( 'status' => 'more_info_required', 'approver_comments' => sanitize_textarea_field( $comments ), 'updated_at' => $now ),
				array( 'request_id' => $request_id )
			);
			VT_Audit::log( 'Request', $request->request_code, 'More-Info-Requested', null, array( 'comments' => $comments ), null );
			VT_Notifications::more_info_required( $employee, self::get( $request_id ) );
			return array( 'ok' => true, 'message' => 'Requested more information from the employee.' );
		}

		if ( 'reject' === $decision ) {
			$wpdb->update(
				VT_DB::requests(),
				array(
					'status'             => 'rejected',
					'decision'           => 'rejected',
					'approval_date'      => $now,
					'approver_comments'  => sanitize_textarea_field( $comments ),
					'updated_at'         => $now,
				),
				array( 'request_id' => $request_id )
			);
			self::release_pending( $request, $flags );
			VT_Audit::log( 'Request', $request->request_code, 'Rejected', null, array( 'comments' => $comments ), null );
			$balance = VT_Balances::get_or_create( $employee->employee_id, (int) substr( $request->start_date, 0, 4 ) );
			VT_Notifications::rejection_notice( $employee, self::get( $request_id ), $balance );
			return array( 'ok' => true, 'message' => 'Request rejected.' );
		}

		// Approve.
		$remaining_stages = isset( $flags['remaining'] ) ? $flags['remaining'] : array();

		if ( ! empty( $remaining_stages ) ) {
			$next_stage = array_shift( $remaining_stages );
			$flags['remaining'] = $remaining_stages;
			$resolution = self::resolve_approver_for_stage( $employee, $next_stage );
			if ( ! $resolution['approver'] ) {
				VT_Audit::log_error( 'routing', "No approver resolvable at escalation stage {$next_stage} for request {$request->request_code}" );
				$resolution['approver'] = VT_Balances::get_employee( self::get_default_hr_employee_id() );
			}
			$flags['acting_for'] = $resolution['acting_for'];

			$wpdb->update(
				VT_DB::requests(),
				array(
					'status'               => 'pending_' . $next_stage,
					'current_stage'        => $next_stage,
					'stage_entered_at'     => $now,
					'assigned_approver_id' => $resolution['approver'] ? $resolution['approver']->employee_id : null,
					'approver_comments'    => sanitize_textarea_field( $comments ),
					'flags'                => wp_json_encode( $flags ),
					'updated_at'           => $now,
				),
				array( 'request_id' => $request_id )
			);
			VT_Audit::log( 'Request', $request->request_code, 'Stage-Approved', array( 'stage' => $request->current_stage ), array( 'next_stage' => $next_stage ), null );

			if ( $resolution['approver'] ) {
				$balance = VT_Balances::get_or_create( $employee->employee_id, (int) substr( $request->start_date, 0, 4 ) );
				VT_Notifications::approval_request( $resolution['approver'], $employee, self::get( $request_id ), $balance );
			}
			return array( 'ok' => true, 'message' => 'Approved and routed to the next required approver.' );
		}

		// Final approval.
		$wpdb->update(
			VT_DB::requests(),
			array(
				'status'            => 'approved',
				'decision'          => 'approved',
				'approval_date'     => $now,
				'approver_comments' => sanitize_textarea_field( $comments ),
				'updated_at'        => $now,
			),
			array( 'request_id' => $request_id )
		);
		self::release_pending( $request, $flags );

		if ( ! $request->balance_applied ) {
			$splits = isset( $flags['splits'] ) ? $flags['splits'] : array();
			foreach ( $splits as $year => $days ) {
				VT_Balances::apply_approved_deduction( $employee->employee_id, (int) $year, $days );
			}
			$wpdb->update( VT_DB::requests(), array( 'balance_applied' => 1 ), array( 'request_id' => $request_id ) );
		}

		VT_Audit::log( 'Request', $request->request_code, 'Approved-Final', null, array( 'approver_comments' => $comments ), null );
		$balance = VT_Balances::get_or_create( $employee->employee_id, (int) substr( $request->start_date, 0, 4 ) );
		VT_Notifications::approval_confirmation( $employee, self::get( $request_id ), $balance );

		return array( 'ok' => true, 'message' => 'Request approved.' );
	}

	private static function release_pending( $request, $flags ) {
		if ( empty( $request->pending_applied ) ) {
			return;
		}
		$splits = isset( $flags['splits'] ) ? $flags['splits'] : array( (int) substr( $request->start_date, 0, 4 ) => $request->total_days );
		foreach ( $splits as $year => $days ) {
			VT_Balances::remove_pending( $request->employee_id, (int) $year, $days );
		}
		global $wpdb;
		$wpdb->update( VT_DB::requests(), array( 'pending_applied' => 0 ), array( 'request_id' => $request->request_id ) );
	}

	public static function can_act_as_approver( $request, $wp_user_id ) {
		$user = get_userdata( $wp_user_id );
		if ( $user && in_array( 'vt_hr_admin', (array) $user->roles, true ) ) {
			return true; // HR override authority.
		}
		if ( user_can( $wp_user_id, 'manage_options' ) ) {
			return true;
		}
		$acting_employee = self::get_employee_by_wp_user( $wp_user_id );
		return $acting_employee && (int) $acting_employee->employee_id === (int) $request->assigned_approver_id;
	}

	/** Employee withdraws a request that has not yet received a decision. */
	public static function withdraw( $request_id, $employee_id ) {
		global $wpdb;
		$request = self::get( $request_id );
		if ( ! $request || (int) $request->employee_id !== (int) $employee_id || ! in_array( $request->status, self::PENDING_STATUSES, true ) ) {
			return array( 'ok' => false, 'message' => 'This request can no longer be withdrawn.' );
		}
		$flags = self::flags( $request );
		$wpdb->update( VT_DB::requests(), array( 'status' => 'withdrawn', 'updated_at' => current_time( 'mysql' ) ), array( 'request_id' => $request_id ) );
		self::release_pending( $request, $flags );
		VT_Audit::log( 'Request', $request->request_code, 'Withdrawn', null, null, null );
		return array( 'ok' => true, 'message' => 'Request withdrawn.' );
	}

	/** Employee requests cancellation/change of an already-approved request. */
	public static function request_cancellation( $request_id, $employee_id ) {
		global $wpdb;
		$request = self::get( $request_id );
		if ( ! $request || (int) $request->employee_id !== (int) $employee_id || 'approved' !== $request->status ) {
			return array( 'ok' => false, 'message' => 'Only an approved request can be cancelled this way.' );
		}
		$employee = VT_Balances::get_employee( $employee_id );
		$after_start = ( current_time( 'Y-m-d' ) > $request->start_date );
		$approver_id = $request->assigned_approver_id;

		if ( $after_start && VT_Settings::get_bool( 'cancellation_after_start_needs_hr', true ) ) {
			$approver_id = self::get_default_hr_employee_id();
		}

		$wpdb->update(
			VT_DB::requests(),
			array(
				'status'               => 'cancellation_pending',
				'cancellation_status'  => 'cancellation_requested',
				'assigned_approver_id' => $approver_id,
				'stage_entered_at'     => current_time( 'mysql' ),
				'updated_at'           => current_time( 'mysql' ),
			),
			array( 'request_id' => $request_id )
		);
		VT_Audit::log( 'Request', $request->request_code, 'Cancellation-Requested', null, null, null );

		VT_Notifications::cancellation_requested( $employee, $request );
		$approver = VT_Balances::get_employee( $approver_id );
		if ( $approver ) {
			VT_Notifications::cancellation_request_to_approver( $approver, $employee, $request );
		}
		return array( 'ok' => true, 'message' => 'Cancellation request submitted.' );
	}

	public static function decide_cancellation( $request_id, $approved, $acting_wp_user_id ) {
		global $wpdb;
		$request = self::get( $request_id );
		if ( ! $request || 'cancellation_pending' !== $request->status ) {
			return array( 'ok' => false, 'message' => 'This request is not awaiting a cancellation decision.' );
		}
		if ( ! self::can_act_as_approver( $request, $acting_wp_user_id ) ) {
			return array( 'ok' => false, 'message' => 'You are not authorized to decide this cancellation.' );
		}
		$employee = VT_Balances::get_employee( $request->employee_id );
		$flags    = self::flags( $request );

		if ( $approved ) {
			$wpdb->update(
				VT_DB::requests(),
				array( 'status' => 'cancelled', 'cancellation_status' => 'cancelled', 'updated_at' => current_time( 'mysql' ) ),
				array( 'request_id' => $request_id )
			);
			$splits = isset( $flags['splits'] ) ? $flags['splits'] : array( (int) substr( $request->start_date, 0, 4 ) => $request->total_days );
			foreach ( $splits as $year => $days ) {
				VT_Balances::reverse_approved_deduction( $employee->employee_id, (int) $year, $days );
			}
			VT_Audit::log( 'Request', $request->request_code, 'Cancellation-Approved', null, null, null );
		} else {
			$wpdb->update(
				VT_DB::requests(),
				array( 'status' => 'approved', 'cancellation_status' => 'none', 'updated_at' => current_time( 'mysql' ) ),
				array( 'request_id' => $request_id )
			);
			VT_Audit::log( 'Request', $request->request_code, 'Cancellation-Rejected', null, null, null );
		}
		VT_Notifications::cancellation_decision( $employee, self::get( $request_id ), $approved );
		return array( 'ok' => true, 'message' => $approved ? 'Cancellation approved.' : 'Cancellation request denied; the vacation remains approved.' );
	}

	public static function get_history( $employee_id, $limit = 50 ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM " . VT_DB::requests() . " WHERE employee_id = %d AND status != 'draft' ORDER BY created_at DESC LIMIT %d", $employee_id, $limit )
		);
	}

	public static function get_pending_for_employee( $employee_id ) {
		global $wpdb;
		$statuses = "'" . implode( "','", self::PENDING_STATUSES ) . "'";
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM " . VT_DB::requests() . " WHERE employee_id = %d AND status IN ($statuses)", $employee_id )
		);
	}

	public static function get_upcoming_approved( $employee_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM " . VT_DB::requests() . " WHERE employee_id = %d AND status = 'approved' AND end_date >= %s ORDER BY start_date ASC",
				$employee_id,
				current_time( 'Y-m-d' )
			)
		);
	}

	public static function get_pending_for_approver( $approver_employee_id ) {
		global $wpdb;
		$statuses = "'" . implode( "','", array( 'pending_team_lead', 'pending_department', 'pending_hr', 'cancellation_pending' ) ) . "'";
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM " . VT_DB::requests() . " WHERE assigned_approver_id = %d AND status IN ($statuses) ORDER BY stage_entered_at ASC", $approver_employee_id )
		);
	}

	/** Privacy-safe calendar: name + department + dates only, no comments/balance. */
	public static function get_calendar_entries( $department_id = null, $from = null, $to = null ) {
		global $wpdb;
		$from = $from ? $from : current_time( 'Y-m-d' );
		$to   = $to ? $to : date( 'Y-m-d', strtotime( $from . ' +60 days' ) );

		$sql = "SELECT r.start_date, r.end_date, r.start_duration, r.end_duration, e.first_name, e.last_name, e.department_id
				FROM " . VT_DB::requests() . " r
				JOIN " . VT_DB::employees() . " e ON e.employee_id = r.employee_id
				WHERE r.status = 'approved' AND r.start_date <= %s AND r.end_date >= %s";
		$params = array( $to, $from );
		if ( $department_id ) {
			$sql .= " AND e.department_id = %d";
			$params[] = $department_id;
		}
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}
}
