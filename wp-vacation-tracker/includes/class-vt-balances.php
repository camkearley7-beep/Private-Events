<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Balance storage and recalculation (blueprint Section 6).
 * Balances are STORED and updated by explicit, idempotent operations here -
 * never written directly by the front end or by a raw list/table edit.
 */
class VT_Balances {

	public static function get_employee( $employee_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . VT_DB::employees() . " WHERE employee_id = %d", $employee_id ) );
	}

	/**
	 * Fetch (or lazily create) the balance row for an employee/year.
	 */
	public static function get_or_create( $employee_id, $year ) {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM " . VT_DB::balances() . " WHERE employee_id = %d AND vacation_year = %d", $employee_id, $year )
		);
		if ( $row ) {
			return $row;
		}

		$employee = self::get_employee( $employee_id );
		if ( ! $employee ) {
			return null;
		}

		$expiry_md = VT_Settings::get( 'carry_forward_expiry_month_day', '06-30' );

		$wpdb->insert(
			VT_DB::balances(),
			array(
				'employee_id'         => $employee_id,
				'vacation_year'       => $year,
				'annual_entitlement'  => $employee->vacation_entitlement,
				'carry_forward'       => $employee->carry_forward,
				'manual_credits'      => 0,
				'manual_deductions'   => 0,
				'approved_days_used'  => 0,
				'pending_days'        => 0,
				'remaining_days'      => (float) $employee->vacation_entitlement + (float) $employee->carry_forward,
				'carry_forward_expiry' => $year . '-' . $expiry_md,
				'is_closed'           => 0,
				'last_recalculated'   => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%d', '%s' )
		);

		VT_Audit::log( 'Balance', $employee_id . '-' . $year, 'Created', null, array( 'year' => $year ), 'System' );

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM " . VT_DB::balances() . " WHERE employee_id = %d AND vacation_year = %d", $employee_id, $year )
		);
	}

	private static function recalc_and_save( $balance_row ) {
		global $wpdb;
		$remaining = (float) $balance_row->annual_entitlement
			+ (float) $balance_row->carry_forward
			+ (float) $balance_row->manual_credits
			- (float) $balance_row->manual_deductions
			- (float) $balance_row->approved_days_used;

		$wpdb->update(
			VT_DB::balances(),
			array(
				'remaining_days'    => $remaining,
				'last_recalculated' => current_time( 'mysql' ),
			),
			array( 'balance_id' => $balance_row->balance_id ),
			array( '%f', '%s' ),
			array( '%d' )
		);

		return $remaining;
	}

	/** Add a request's days to "pending" - guarded so it only ever applies once per request. */
	public static function add_pending( $employee_id, $year, $days ) {
		global $wpdb;
		$balance = self::get_or_create( $employee_id, $year );
		if ( ! $balance || $balance->is_closed ) {
			return false;
		}
		$wpdb->update(
			VT_DB::balances(),
			array( 'pending_days' => (float) $balance->pending_days + (float) $days ),
			array( 'balance_id' => $balance->balance_id ),
			array( '%f' ),
			array( '%d' )
		);
		return true;
	}

	/** Remove a request's days from "pending" once it leaves a pending stage (approved/rejected/withdrawn/cancelled/expired). */
	public static function remove_pending( $employee_id, $year, $days ) {
		global $wpdb;
		$balance = self::get_or_create( $employee_id, $year );
		if ( ! $balance ) {
			return false;
		}
		$new_pending = max( 0, (float) $balance->pending_days - (float) $days );
		$wpdb->update(
			VT_DB::balances(),
			array( 'pending_days' => $new_pending ),
			array( 'balance_id' => $balance->balance_id ),
			array( '%f' ),
			array( '%d' )
		);
		return true;
	}

	/** Deduct approved days from the balance. Idempotency is enforced by the caller checking request->balance_applied first. */
	public static function apply_approved_deduction( $employee_id, $year, $days ) {
		global $wpdb;
		$balance = self::get_or_create( $employee_id, $year );
		if ( ! $balance || $balance->is_closed ) {
			VT_Audit::log_error( 'balance-update', "Attempted deduction on closed/missing year {$year} for employee {$employee_id}" );
			return false;
		}
		$previous = clone $balance;
		$wpdb->update(
			VT_DB::balances(),
			array( 'approved_days_used' => (float) $balance->approved_days_used + (float) $days ),
			array( 'balance_id' => $balance->balance_id ),
			array( '%f' ),
			array( '%d' )
		);
		$balance = self::get_or_create( $employee_id, $year );
		$remaining = self::recalc_and_save( $balance );

		VT_Audit::log(
			'Balance',
			$employee_id . '-' . $year,
			'Approved-Deduction',
			array( 'approved_days_used' => $previous->approved_days_used ),
			array(
				'approved_days_used' => $previous->approved_days_used + $days,
				'remaining_days'     => $remaining,
			),
			'System'
		);
		return true;
	}

	/** Reverse a previously-applied approved deduction (cancellation of an approved request). */
	public static function reverse_approved_deduction( $employee_id, $year, $days ) {
		global $wpdb;
		$balance = self::get_or_create( $employee_id, $year );
		if ( ! $balance ) {
			return false;
		}
		if ( $balance->is_closed ) {
			VT_Audit::log( 'Balance', $employee_id . '-' . $year, 'Reversal-Deferred-Closed-Year', null, array( 'days' => $days ), 'System',
				'Year is closed; booked as a Balance Adjustment instead.' );
			VT_Requests_Helper_Adjustment( $employee_id, $year, $days );
			return true;
		}
		$previous = clone $balance;
		$new_used = max( 0, (float) $balance->approved_days_used - (float) $days );
		$wpdb->update(
			VT_DB::balances(),
			array( 'approved_days_used' => $new_used ),
			array( 'balance_id' => $balance->balance_id ),
			array( '%f' ),
			array( '%d' )
		);
		$balance   = self::get_or_create( $employee_id, $year );
		$remaining = self::recalc_and_save( $balance );

		VT_Audit::log(
			'Balance',
			$employee_id . '-' . $year,
			'Cancellation-Reversal',
			array( 'approved_days_used' => $previous->approved_days_used ),
			array(
				'approved_days_used' => $new_used,
				'remaining_days'     => $remaining,
			),
			'System'
		);
		return true;
	}

	public static function apply_manual_adjustment( $employee_id, $year, $type, $amount, $reason, $admin_wp_user_id, $notes = '' ) {
		global $wpdb;
		$balance = self::get_or_create( $employee_id, $year );
		if ( ! $balance ) {
			return false;
		}

		$wpdb->insert(
			VT_DB::adjustments(),
			array(
				'employee_id'      => $employee_id,
				'vacation_year'    => $year,
				'adjustment_type'  => $type,
				'amount'           => $amount,
				'reason'           => $reason,
				'admin_wp_user_id' => $admin_wp_user_id,
				'created_at'       => current_time( 'mysql' ),
				'notes'            => $notes,
			),
			array( '%d', '%d', '%s', '%f', '%s', '%d', '%s', '%s' )
		);

		$field = ( 'credit' === $type ) ? 'manual_credits' : 'manual_deductions';
		$wpdb->update(
			VT_DB::balances(),
			array( $field => (float) $balance->{$field} + abs( (float) $amount ) ),
			array( 'balance_id' => $balance->balance_id ),
			array( '%f' ),
			array( '%d' )
		);
		$balance   = self::get_or_create( $employee_id, $year );
		$remaining = self::recalc_and_save( $balance );

		VT_Audit::log(
			'Balance Adjustment',
			$employee_id . '-' . $year,
			'Manual-' . ucfirst( $type ),
			null,
			array(
				'amount'         => $amount,
				'reason'         => $reason,
				'remaining_days' => $remaining,
			),
			get_userdata( $admin_wp_user_id ) ? get_userdata( $admin_wp_user_id )->user_login : 'HR Admin'
		);

		return true;
	}

	/**
	 * Nightly reconciliation safety net: recompute pending/approved totals from the
	 * requests table itself and correct the stored balance if it has drifted.
	 */
	public static function reconcile_all() {
		global $wpdb;
		$year = VT_Calc::current_vacation_year();
		$balances = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM " . VT_DB::balances() . " WHERE vacation_year = %d AND is_closed = 0", $year )
		);

		foreach ( $balances as $balance ) {
			$approved = (float) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COALESCE(SUM(total_days),0) FROM " . VT_DB::requests() . "
					 WHERE employee_id = %d AND status = 'approved' AND YEAR(start_date) = %d",
					$balance->employee_id,
					$year
				)
			);
			$pending = (float) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COALESCE(SUM(total_days),0) FROM " . VT_DB::requests() . "
					 WHERE employee_id = %d AND YEAR(start_date) = %d
					 AND status IN ('submitted','pending_team_lead','pending_department','pending_hr','more_info_required')",
					$balance->employee_id,
					$year
				)
			);

			if ( abs( $approved - (float) $balance->approved_days_used ) > 0.01 || abs( $pending - (float) $balance->pending_days ) > 0.01 ) {
				$previous = clone $balance;
				$wpdb->update(
					VT_DB::balances(),
					array(
						'approved_days_used' => $approved,
						'pending_days'       => $pending,
					),
					array( 'balance_id' => $balance->balance_id ),
					array( '%f', '%f' ),
					array( '%d' )
				);
				$fresh     = self::get_or_create( $balance->employee_id, $year );
				$remaining = self::recalc_and_save( $fresh );

				VT_Audit::log(
					'Balance',
					$balance->employee_id . '-' . $year,
					'Discrepancy-Corrected',
					array(
						'approved_days_used' => $previous->approved_days_used,
						'pending_days'       => $previous->pending_days,
					),
					array(
						'approved_days_used' => $approved,
						'pending_days'       => $pending,
						'remaining_days'     => $remaining,
					),
					'System: Nightly Reconciliation'
				);
			}
		}
	}

	/**
	 * Annual rollover. $commit=false runs a dry-run report only (no writes).
	 * Safe to re-run: skips any employee who already has a balance for $to_year.
	 */
	public static function run_rollover( $to_year, $commit = false ) {
		global $wpdb;
		$from_year        = $to_year - 1;
		$max_carry        = VT_Settings::get_number( 'max_carry_forward_days', 5 );
		$expiry_md        = VT_Settings::get( 'carry_forward_expiry_month_day', '06-30' );
		$employees        = $wpdb->get_results( "SELECT * FROM " . VT_DB::employees() . " WHERE active = 1" );
		$report           = array();

		foreach ( $employees as $employee ) {
			$existing = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM " . VT_DB::balances() . " WHERE employee_id = %d AND vacation_year = %d", $employee->employee_id, $to_year )
			);
			if ( $existing ) {
				$report[] = array(
					'employee' => $employee,
					'skipped'  => true,
					'reason'   => 'Already rolled over',
				);
				continue;
			}

			$prior = self::get_or_create( $employee->employee_id, $from_year );
			$unused = max( 0, (float) $prior->remaining_days );
			$carry_forward = min( $unused, $max_carry );
			$forfeited     = max( 0, $unused - $max_carry );

			$report[] = array(
				'employee'      => $employee,
				'skipped'       => false,
				'prior_remaining' => $unused,
				'carry_forward' => $carry_forward,
				'forfeited'     => $forfeited,
				'new_entitlement' => $employee->vacation_entitlement,
			);

			if ( $commit ) {
				$wpdb->insert(
					VT_DB::balances(),
					array(
						'employee_id'          => $employee->employee_id,
						'vacation_year'        => $to_year,
						'annual_entitlement'   => $employee->vacation_entitlement,
						'carry_forward'        => $carry_forward,
						'manual_credits'       => 0,
						'manual_deductions'    => 0,
						'approved_days_used'   => 0,
						'pending_days'         => 0,
						'remaining_days'       => (float) $employee->vacation_entitlement + $carry_forward,
						'carry_forward_expiry' => $to_year . '-' . $expiry_md,
						'is_closed'            => 0,
						'last_recalculated'    => current_time( 'mysql' ),
					),
					array( '%d', '%d', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%d', '%s' )
				);

				$wpdb->update(
					VT_DB::balances(),
					array( 'is_closed' => 1 ),
					array( 'balance_id' => $prior->balance_id ),
					array( '%d' ),
					array( '%d' )
				);

				VT_Audit::log(
					'Rollover',
					$employee->employee_id . '-' . $to_year,
					'Rolled-Over',
					array( 'from_year' => $from_year, 'remaining' => $unused ),
					array( 'carry_forward' => $carry_forward, 'forfeited' => $forfeited ),
					'System: Annual Rollover'
				);
			}
		}

		return $report;
	}
}

/**
 * Small helper used when a cancellation reversal targets an already-closed
 * year - books the reversal as a Balance Adjustment against the CURRENT year
 * instead, since a closed year's figures must never change in place.
 */
function VT_Requests_Helper_Adjustment( $employee_id, $original_year, $days ) {
	$current_year = VT_Calc::current_vacation_year();
	VT_Balances::apply_manual_adjustment(
		$employee_id,
		$current_year,
		'credit',
		$days,
		"Reversal of {$original_year} approved vacation (year closed) - VT-Requests",
		0
	);
}
