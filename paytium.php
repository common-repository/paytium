<?php

/**
 * Paytium
 *
 * @package     PT
 * @author      David de Boer <david@davdeb.com>
 * @license     GPL-2.0+
 * @link        https://www.paytium.nl
 * @copyright   2015-2019 David de Boer
 * @copyright   Paytium is based on Stripe Checkout by Phil Derksen and Stripe Checkout Companion by Kyle M. Brown
 * @copyright   2014-2015 Phil Derksen for Stripe Checkout
 * @copyright   2014-2015 Kyle M. Brown for Stripe Checkout Companion
 *
 * @wordpress-plugin
 * Plugin Name: Paytium
 * Plugin URI: https://www.paytium.nl
 * Description: Paytium, making payments in WordPress even more awesome!
 * Version: 4.4.11
 * Author: David de Boer
 * Author URI: https://www.paytium.nl
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: paytium
 * Domain Path: /languages/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );


if ( class_exists( 'Paytium' ) ) {

	deactivate_plugins( plugin_basename( __FILE__ ) );

} else {


	if ( ! defined( 'PT_MAIN_FILE' ) ) {
		define( 'PT_MAIN_FILE', __FILE__ );
	}

	if ( ! defined( 'PT_PATH' ) ) {
		define( 'PT_PATH', plugin_dir_path( __FILE__ ) );
	}

	if ( ! defined( 'PT_URL' ) ) {
		define( 'PT_URL', plugins_url( '', __FILE__ ) . '/' );
	}

	if ( ! defined( 'PT_VERSION' ) ) {
		define( 'PT_VERSION', '4.4.11' );
	}

	if ( ! defined( 'PT_NAME' ) ) {
		define( 'PT_NAME', 'Paytium' );
	}

	if ( ! defined( 'PT_PACKAGE' ) ) {
		define( 'PT_PACKAGE', 'paytium' );
	}

	if ( ! defined( 'PT_WEBSITE_URL' ) ) {
		define( 'PT_WEBSITE_URL', 'https://www.paytium.nl/' );
	}

	/**
	 * Registration & activation hook
	 */
	function install_paytium() {

		// Add value to indicate that we should show admin notice for setup wizard.
		update_option( 'pt_show_admin_notice_setup_wizard', 1 );

		// Add value to indicate that we should show admin notice for newsletter.
		update_option( 'pt_show_admin_notice_newsletter', 1 );

		// Add value to indicate that we should show admin notice for extensions.
		update_option( 'pt_show_admin_notice_extensions', 1 );

		// Other options
		update_option( 'paytium_enable_remember', 1 );
		update_option( 'paytium_uninstall_save_settings', 1 );
		update_option( 'paytium_always_enqueue', 1 );

		if ( ! function_exists( 'curl_version' ) ) {
			wp_die( sprintf( __( 'You must have the cURL extension enabled in order to run %s. Please enable cURL and try again. <a href="%s">Return to Plugins</a>.', 'paytium' ),
				PT_NAME, get_admin_url( '', 'plugins.php' ) ) );
		}

		// Remove old log file format
		if ( file_exists( plugin_dir_path( __FILE__ ) . 'logs.txt' ) ) {
			unlink( plugin_dir_path( __FILE__ ) . 'logs.txt' );
		}

	}

	register_activation_hook( PT_MAIN_FILE, 'install_paytium' );

	/**
	 * Load plugin text domain (for translation files)
	 */
	load_plugin_textdomain(
		'paytium',
		null,
		dirname( plugin_basename( PT_MAIN_FILE ) ) . '/languages/'
	);

	/**
	 * Get Paytium class.
	 */
	if ( ! class_exists( 'Paytium' ) ) {
		require_once( PT_PATH . 'class-paytium.php' );
	}

	/**
	 * @return Paytium
	 */
	function Paytium() {

		$paytium = Paytium::get_instance();

		require_once( PT_PATH . 'includes/class-shortcode-tracker.php' );
		Paytium_Shortcode_Tracker::get_instance();

		return $paytium;

	}

	Paytium();
}

