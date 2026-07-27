<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central place for table names so every class references the same names.
 */
class VT_DB {

	public static function employees() {
		global $wpdb;
		return $wpdb->prefix . 'vt_employees';
	}

	public static function departments() {
		global $wpdb;
		return $wpdb->prefix . 'vt_departments';
	}

	public static function requests() {
		global $wpdb;
		return $wpdb->prefix . 'vt_requests';
	}

	public static function balances() {
		global $wpdb;
		return $wpdb->prefix . 'vt_balances';
	}

	public static function holidays() {
		global $wpdb;
		return $wpdb->prefix . 'vt_holidays';
	}

	public static function blackout_periods() {
		global $wpdb;
		return $wpdb->prefix . 'vt_blackout_periods';
	}

	public static function delegations() {
		global $wpdb;
		return $wpdb->prefix . 'vt_delegations';
	}

	public static function adjustments() {
		global $wpdb;
		return $wpdb->prefix . 'vt_balance_adjustments';
	}

	public static function audit_log() {
		global $wpdb;
		return $wpdb->prefix . 'vt_audit_log';
	}

	public static function settings() {
		global $wpdb;
		return $wpdb->prefix . 'vt_settings';
	}

	public static function error_log() {
		global $wpdb;
		return $wpdb->prefix . 'vt_error_log';
	}
}
