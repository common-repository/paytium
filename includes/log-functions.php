<?php

/**
 * Paytium log functions
 *
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Log when any mode enabled for first time
 *
 */
function enabled_any_mode_log( $option_name, $new_value ) {

	$new_value = $new_value == '1' ? 'live' : 'test';
	$user      = wp_get_current_user()->data;
	$message   = 'User "' . $user->user_login . ' (id: ' . $user->ID . ')" enabled "' . $new_value . '" mode';
	paytium_logger( __( $message, 'paytium' ),__FILE__,__LINE__ );

}

add_action( 'add_option_paytium_enable_live_key', 'enabled_any_mode_for_first_time_log', 10, 2 );


/**
 * Log when user switching live/test mode
 *
 */
function switched_mode_log( $old_value, $new_value ) {

	$old_value = $old_value == '1' ? 'live' : 'test';
	$new_value = $new_value == '1' ? 'live' : 'test';
	$user      = wp_get_current_user()->data;
	$message   = 'Switched from "' . $old_value . '" to "' . $new_value . '" mode by user "' . $user->user_login . ' (id: ' . $user->ID . ')"';
	paytium_logger( __( $message, 'paytium' ),__FILE__,__LINE__ );

}

add_action( 'update_option_paytium_enable_live_key', 'switched_mode_log', 10, 2 );


/**
 * Log when user switching payment status in admin panel
 *
 */
function switched_payment_status_log( $payment_post_id, $old_status, $new_status ) {

	$user    = wp_get_current_user()->data;
	$message = $payment_post_id . ' - Status updated from "' . $old_status . '" to "' . $new_status . '" by user "' . $user->user_login . ' (id: ' . $user->ID . ')"';
	paytium_logger( __( $message, 'paytium' ),__FILE__,__LINE__ );
}
