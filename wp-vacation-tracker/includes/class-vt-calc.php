<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Date/day chargeable-vacation calculation (blueprint Section 7).
 */
class VT_Calc {

	/**
	 * Calculate chargeable working days and hours for a request.
	 *
	 * @param object $employee        Row from vt_employees.
	 * @param string $start_date      Y-m-d.
	 * @param string $end_date        Y-m-d.
	 * @param string $start_duration  full|am|pm
	 * @param string $end_duration    full|am|pm
	 * @return array { days: float, hours: float }
	 */
	public static function calculate_chargeable_days( $employee, $start_date, $end_date, $start_duration = 'full', $end_duration = 'full' ) {
		$workdays = array_map( 'intval', explode( ',', $employee->standard_workdays ) ); // ISO 1=Mon..7=Sun
		$holidays = self::get_holiday_dates( $employee->jurisdiction );

		$start = new DateTime( $start_date );
		$end   = new DateTime( $end_date );

		if ( $start > $end ) {
			return array(
				'days'  => 0,
				'hours' => 0,
			);
		}

		$same_day  = ( $start->format( 'Y-m-d' ) === $end->format( 'Y-m-d' ) );
		$total     = 0.0;
		$cursor    = clone $start;
		$interval  = new DateInterval( 'P1D' );
		$end_incl  = clone $end;
		$end_incl->add( $interval );
		$period = new DatePeriod( $cursor, $interval, $end_incl );

		foreach ( $period as $date ) {
			$iso_dow = (int) $date->format( 'N' ); // 1 (Mon) - 7 (Sun)
			if ( ! in_array( $iso_dow, $workdays, true ) ) {
				continue; // not a workday for this employee
			}
			$date_str = $date->format( 'Y-m-d' );
			if ( in_array( $date_str, $holidays, true ) ) {
				continue; // company holiday, excluded
			}

			$day_value = 1.0;

			if ( $same_day && ( 'full' !== $start_duration || 'full' !== $end_duration ) ) {
				$day_value = 0.5;
			} elseif ( $date_str === $start->format( 'Y-m-d' ) && 'full' !== $start_duration ) {
				$day_value = 0.5;
			} elseif ( $date_str === $end->format( 'Y-m-d' ) && 'full' !== $end_duration ) {
				$day_value = 0.5;
			}

			$total += $day_value;
		}

		$hours = $total * (float) $employee->hours_per_workday;

		return array(
			'days'  => round( $total, 2 ),
			'hours' => round( $hours, 2 ),
		);
	}

	/**
	 * Split a chargeable-day calculation across a calendar-year boundary,
	 * returning per-year day totals so cross-year requests can be deducted
	 * from two separate Balance records.
	 */
	public static function calculate_cross_year_split( $employee, $start_date, $end_date, $start_duration, $end_duration ) {
		$start_year = (int) substr( $start_date, 0, 4 );
		$end_year   = (int) substr( $end_date, 0, 4 );

		if ( $start_year === $end_year ) {
			$calc = self::calculate_chargeable_days( $employee, $start_date, $end_date, $start_duration, $end_duration );
			return array( $start_year => $calc );
		}

		$year_end_date   = $start_year . '-12-31';
		$year_start_date = $end_year . '-01-01';

		$portion1 = self::calculate_chargeable_days( $employee, $start_date, $year_end_date, $start_duration, 'full' );
		$portion2 = self::calculate_chargeable_days( $employee, $year_start_date, $end_date, 'full', $end_duration );

		return array(
			$start_year => $portion1,
			$end_year   => $portion2,
		);
	}

	private static function get_holiday_dates( $jurisdiction ) {
		global $wpdb;
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT holiday_date FROM " . VT_DB::holidays() . "
				 WHERE active = 1 AND excluded_from_calc = 1 AND (jurisdiction = %s OR jurisdiction = 'all')",
				$jurisdiction ? $jurisdiction : 'all'
			)
		);
		return $rows ? $rows : array();
	}

	/**
	 * Count business days (Mon-Fri, ignoring holidays) between two datetimes -
	 * used for reminder/escalation timing, not vacation balance math.
	 */
	public static function business_days_since( $since_datetime ) {
		$since = new DateTime( $since_datetime );
		$now   = new DateTime( current_time( 'mysql' ) );
		if ( $now <= $since ) {
			return 0;
		}
		$count  = 0;
		$cursor = clone $since;
		while ( $cursor < $now ) {
			$cursor->add( new DateInterval( 'P1D' ) );
			$dow = (int) $cursor->format( 'N' );
			if ( $dow < 6 ) {
				$count++;
			}
		}
		return $count;
	}

	public static function current_vacation_year() {
		return (int) current_time( 'Y' );
	}
}
