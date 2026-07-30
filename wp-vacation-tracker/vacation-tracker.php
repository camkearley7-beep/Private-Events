<?php
/**
 * Plugin Name: Vacation Tracker
 * Description: Employee vacation request and tracking system - submission, approval routing, balances, notifications, and a privacy-safe team calendar.
 * Version: 1.0.0
 * Author: Your Organization
 * Text Domain: vacation-tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'VT_VERSION', '1.0.0' );
define( 'VT_PLUGIN_FILE', __FILE__ );
define( 'VT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once VT_PLUGIN_DIR . 'includes/class-vt-db.php';
require_once VT_PLUGIN_DIR . 'includes/class-vt-activator.php';
require_once VT_PLUGIN_DIR . 'includes/class-vt-audit.php';
require_once VT_PLUGIN_DIR . 'includes/class-vt-settings.php';
require_once VT_PLUGIN_DIR . 'includes/class-vt-calc.php';
require_once VT_PLUGIN_DIR . 'includes/class-vt-balances.php';
require_once VT_PLUGIN_DIR . 'includes/class-vt-notifications.php';
require_once VT_PLUGIN_DIR . 'includes/class-vt-requests.php';
require_once VT_PLUGIN_DIR . 'includes/class-vt-cron.php';
require_once VT_PLUGIN_DIR . 'includes/class-vt-ajax.php';
require_once VT_PLUGIN_DIR . 'includes/class-vt-shortcodes.php';
require_once VT_PLUGIN_DIR . 'includes/class-vt-admin.php';

register_activation_hook( VT_PLUGIN_FILE, array( 'VT_Activator', 'activate' ) );
register_deactivation_hook( VT_PLUGIN_FILE, array( 'VT_Cron', 'deactivate' ) );

/**
 * Boot the plugin.
 */
function vt_init_plugin() {
	load_plugin_textdomain( 'vacation-tracker', false, dirname( plugin_basename( VT_PLUGIN_FILE ) ) . '/languages' );

	VT_Ajax::init();
	VT_Shortcodes::init();
	VT_Cron::init();

	if ( is_admin() ) {
		VT_Admin::init();
	}
}
add_action( 'plugins_loaded', 'vt_init_plugin' );

/**
 * Enqueue front-end assets only on pages that actually contain the shortcode.
 */
function vt_enqueue_frontend_assets() {
	global $post;
	if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'vt_app' ) ) {
		wp_enqueue_style( 'vt-frontend', VT_PLUGIN_URL . 'assets/css/vt-style.css', array(), VT_VERSION );
		wp_enqueue_script( 'vt-frontend', VT_PLUGIN_URL . 'assets/js/vt-frontend.js', array(), VT_VERSION, true );
		wp_localize_script(
			'vt-frontend',
			'VT_DATA',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'vt_frontend_nonce' ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'vt_enqueue_frontend_assets' );
