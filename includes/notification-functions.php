<?php

/**
 * Item Limit functions
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * @param $notification_id
 * @param $notification
 */
function paytium_add_notification( $notification_id, $notification ) {

	if (get_option('paytium_notifications')) {

		$paytium_notifications = unserialize(get_option('paytium_notifications'));
		$paytium_notifications[$notification_id] = $notification;

		update_option('paytium_notifications', serialize($paytium_notifications));
	}
	else {

		$paytium_notifications = array($notification_id => $notification);
		update_option('paytium_notifications', serialize($paytium_notifications));
	}
}

/**
 *
 *  Check for certain paytium notifications with status open based on slug
 *
 * @param $slug
 * @return bool
 */
function paytium_check_notifications( $slug ) {

	if ( get_option( 'paytium_notifications' ) ) {

		$paytium_notifications = unserialize( get_option( 'paytium_notifications' ) );

		foreach ( $paytium_notifications as $notification ) {

			if ( $notification['status'] == 'open' && $slug == $notification['slug'] ) {
				return true;
			}
		}

	}

	return false;

}

/**
 * Display paytium notifications with status open
 */
function paytium_display_notifications() {

	if ( ! Paytium()->viewing_this_plugin() ) {
		return false;
	}

	if (get_option('paytium_notifications')) {

		$paytium_notifications = unserialize(get_option('paytium_notifications'));

		foreach ($paytium_notifications as $notification) {

			if ($notification['status'] == 'open') {
				echo '<div class="notice notice-warning is-dismissible paytium-notice" data-id="'.$notification['id'].'"><p>'.
					 __( $notification['message'], 'paytium' ) .
					 '</p></div>';
			}
		}
	}
}

add_action('admin_notices', 'paytium_display_notifications');


/**
 * Paytium notices dismiss.
 */
function paytium_notice_dismiss() {

	check_ajax_referer( 'paytium-ajax-nonce', 'nonce' );

	$paytium_notice_id = isset($_POST['id']) ? $_POST['id'] : false;
	$paytium_notifications = get_option('paytium_notifications') ? unserialize(get_option('paytium_notifications')) : false;

	if (current_user_can('administrator') && $paytium_notice_id && $paytium_notifications) {

		$paytium_notifications[$paytium_notice_id]['user_id'] = get_current_user_id(); // log user ID which dismissed the notice
		$paytium_notifications[$paytium_notice_id]['status'] = 'closed';
		update_option('paytium_notifications', serialize($paytium_notifications));

		echo 'success';
		wp_die();
	}
	else {
		echo 'error';
		wp_die();
	}
}
add_action('wp_ajax_paytium_notice_dismiss', 'paytium_notice_dismiss');