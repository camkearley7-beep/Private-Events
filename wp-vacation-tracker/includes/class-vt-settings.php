<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple key/value settings store (System Settings list in the blueprint).
 * Cached per-request in a static array to avoid repeat queries.
 */
class VT_Settings {

	private static $cache = array();

	public static function get( $key, $default = '' ) {
		if ( array_key_exists( $key, self::$cache ) ) {
			return self::$cache[ $key ];
		}
		global $wpdb;
		$value = $wpdb->get_var(
			$wpdb->prepare( "SELECT setting_value FROM " . VT_DB::settings() . " WHERE setting_key = %s", $key )
		);
		if ( null === $value ) {
			self::$cache[ $key ] = $default;
			return $default;
		}
		self::$cache[ $key ] = $value;
		return $value;
	}

	public static function get_bool( $key, $default = false ) {
		$value = self::get( $key, $default ? 'yes' : 'no' );
		return in_array( strtolower( (string) $value ), array( 'yes', '1', 'true' ), true );
	}

	public static function get_number( $key, $default = 0 ) {
		return (float) self::get( $key, $default );
	}

	public static function set( $key, $value, $data_type = 'text', $description = '' ) {
		global $wpdb;
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT setting_key FROM " . VT_DB::settings() . " WHERE setting_key = %s", $key ) );

		if ( $existing ) {
			$wpdb->update(
				VT_DB::settings(),
				array( 'setting_value' => $value ),
				array( 'setting_key' => $key ),
				array( '%s' ),
				array( '%s' )
			);
		} else {
			$wpdb->insert(
				VT_DB::settings(),
				array(
					'setting_key'   => $key,
					'setting_value' => $value,
					'data_type'     => $data_type,
					'description'   => $description,
				),
				array( '%s', '%s', '%s', '%s' )
			);
		}
		self::$cache[ $key ] = $value;
	}

	public static function set_if_missing( $key, $value, $data_type = 'text', $description = '' ) {
		global $wpdb;
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT setting_key FROM " . VT_DB::settings() . " WHERE setting_key = %s", $key ) );
		if ( ! $existing ) {
			self::set( $key, $value, $data_type, $description );
		}
	}

	public static function get_all() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM " . VT_DB::settings() . " ORDER BY setting_key ASC" );
	}
}
