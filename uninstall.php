<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @package PT
 * @author  David de Boer <david@davdeb.com>
 */

// If uninstall, not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$general = get_option( 'pt_settings_default' );

if ( empty( $general['uninstall_save_settings'] ) ) {
	delete_option( 'pt_settings_master' );
	delete_option( 'pt_settings_default' );
	delete_option( 'pt_settings_keys' );
	delete_option( 'pt_show_admin_notice_setup_wizard' );
	delete_option( 'pt_show_admin_notice_newsletter' );
	delete_option( 'pt_show_admin_notice_extensions' );
	delete_option( 'pt_has_run' );
	delete_option( 'pt_version' );
	delete_option( 'pt_upgrade_has_run' );
	delete_option( 'pt_settings_licenses' );
	delete_option( 'pt_licenses' );
}
