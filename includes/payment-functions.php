<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Payment functions.
 *
 * This file holds all the payment related functions.
 *
 * @author        Jeroen Sormani
 * @since         1.0.0
 */


/**
 * Create a payment.
 *
 * Create a new payment when someone clicks the pay button.
 *
 * @since 1.0.0
 *
 * @param  array $data List of payment arguments.
 *
 * @return int         Post ID of the created payment.
 */
function pt_create_payment( $data ) {

	// Merge data with basics for new payment
	$data = wp_parse_args( $data, array (
		'payment_id'   => '',
		'mode'         => '',
		'amount'       => '0',
		'currency'	   => '',
		'description'  => '',
		'status'       => 'open',
		'order_status' => 'new',
		'method'       => '',
	) );

	// Filter for developers to hook into
	$data = apply_filters( 'pt_create_payment_arguments', $data );

	$post_id = wp_insert_post( array (
		'post_title'  => '',
		'post_type'   => 'pt_payment',
		'post_status' => 'publish',
	) );

	// Check if post author is set, and include it if it is
	if ( ! empty( $data['post_author'] ) ) {

		$post_id = wp_update_post( array (
			'ID'          => $post_id,
			'post_author' => $data['post_author'],
		) );
	}

	// Insert payment data as post meta
	foreach ( $data as $key => $value ) {

		// Don't store serialized meta information
		if ( $key == 'meta' ) {
			continue;
		};

		add_post_meta( $post_id, '_' . $key, $value );
	}

	$ac_subscribed = 0;
	$mc_subscribed = 0;
	$ml_subscribed = 0;
	foreach ( $data['meta'] as $key => $value ) {
		if (strpos($key,'activecampaign') && strpos($key, '-label') == false && strpos($key, '-list') == false
			&& strpos($key, '-after') == false && strpos($key, '-tags') == false ) {
			$ac_subscribed = (int)$value;
		}
		if (strpos($key,'mailchimp') && strpos($key, '-label') == false && strpos($key, '-list') == false
			&& strpos($key, '-after') == false && strpos($key, '-tags') == false ) {
			$mc_subscribed = (int)$value;
		}
		if (strpos($key,'mailerlite') && strpos($key, '-label') == false && strpos($key, '-group') == false
			&& strpos($key, '-after') == false && strpos($key, '-tags') == false ) {
			$ml_subscribed = (int)$value;
		}
	}

	$prev_meta_val = '';
	// Store all fields as post meta
	foreach ( $data['meta'] as $key => $value ) {

		// Make sure a checkbox with multiple selected values is stored as a string
		// See: https://github.com/davdebcom/paytium/issues/199
		if ( ( strpos( $key, '-checkbox-' ) ) && ( strpos( $key, '-label' ) == false )
            && ( strpos( $key, '-limit' ) == false ) ) {
			$value = implode( ', ', (array)maybe_unserialize( $value ) );
		}

		// Add Marketing custom fields values
		if ( (($ac_subscribed && strpos($key,'ac-custom-field')) ||
				($mc_subscribed && strpos($key,'mc-custom-field')) ||
				($ml_subscribed && strpos($key,'ml-custom-field')) )
			&& strpos($key, '-label') == false && !empty($prev_meta_val) ) {
			$value = json_encode([$value => $prev_meta_val]);
		}
		if ( (!$ac_subscribed && strpos($key,'ac-custom-field')) ||
			(!$mc_subscribed && strpos($key,'mc-custom-field')) ||
			(!$ml_subscribed && strpos($key,'ml-custom-field')) ) {
			continue;
		}

		$prev_meta_val = $value;
		add_post_meta( $post_id, '_' . $key, $value );
	}

	return $post_id;

}


/**
 * Create a subscription.
 *
 */
function pt_create_subscription() {

	$subscription_id = wp_insert_post( array (
		'post_title'  => '',
		'post_type'   => 'pt_subscription',
		'post_status' => 'publish',
	) );

	return $subscription_id;

}


/**
 * Update a payment.
 *
 * Update a new payment after payment sent to Mollie API.
 *
 * @since 1.0.0
 *
 * @param  array $meta List of payment arguments.
 *
 * @return int         true
 */
function pt_update_payment_meta( $post_id, $meta ) {

	if ( ! get_post( $post_id ) || ! is_array( $meta ) || empty( $meta ) ) {
		return false;
	}

	foreach ( $meta as $meta_key => $meta_value ) {
		update_post_meta( $post_id, '_' . $meta_key, $meta_value );
	}

	return true;

}


/**
 * Get payment.
 *
 * Get a single PT_Payment object.
 *
 * @since 1.0.0
 *
 * @param  int $post_id Post ID of the payment to get.
 *
 * @return PT_Payment|bool          PT_Payment object when its a valid post ID, or false when not found.
 */
function pt_get_payment( $post_id ) {

	$payment = new PT_Payment( $post_id );

	if ( empty( $payment ) ) :
		$payment = false;
	endif;

	return apply_filters( 'pt_get_payment', $payment, $post_id );

}


/**
 * Get payment by payment ID.
 *
 * Get a single PT_Payment object based on the payment ID provided by Mollie.
 *
 * @since 1.0.0
 *
 * @param  string $payment_id Payment ID to get the pt_payment form.
 *
 * @return mixed              PT_Payment object when its a valid post ID, or false when not found.
 */
function pt_get_payment_by_payment_id( $payment_id ) {

	global $wpdb;

	$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value = %s AND meta_key = '_payment_id'", $payment_id ) );

	// TODO: If payment not found in database, check if it belongs to a previous subscription based on subscriptionId

	if ( $post_id == null ) {
		return null;
	}

	// TODO: what if no corresponding payment is found at all? But, received payment_id from Mollie right? Should always be there?

	return pt_get_payment( $post_id );

}

/**
 * Get payment by payment key.
 *
 * Get a single PT_Payment object based on the payment key (generated by Paytium, used in redirectURL).
 *
 * @since 1.0.0
 *
 * @param  string $payment_key Key to get the pt_payment form.
 *
 * @return PT_Payment               PT_Payment object when its a valid post ID, or false when not found.
 */
function pt_get_payment_by_payment_key( $payment_key ) {

	global $wpdb;

	$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value = %s AND meta_key = '_payment_key'", $payment_key ) );

	return pt_get_payment( $post_id );

}

/**
 * Try getting payment by subscription ID.
 *
 * Get a single PT_Payment object based on the subscription ID if provided by Mollie.
 *
 * @since 1.3.0
 *
 * @param  string $subscription_id Subscription ID to get the pt_payment information
 *
 * @return mixed                    PT_Payment object when its a valid post ID, or false when not found.
 */
function pt_get_payment_by_subscription_id( $subscription_id ) {

	global $wpdb;

	if ( $subscription_id == null ) {
		return null;
	}

	// Find the first payment in database (search old to new) with the subscription ID
	$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value = %s AND meta_key = '_mollie_subscription_id'", $subscription_id ) );

	if (get_post_meta($post_id,'_payments',true)) {
		$post_id = pt_get_first_payment_id($post_id);
	}

	if ( $post_id == null ) {
		// Find the first payment in database (search old to new) with the subscription ID
		$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value = %s AND meta_key = '_subscription_id'", $subscription_id ) );

		if ( $post_id == null ) {
			return null;
		}
	}

	return pt_get_payment( $post_id );

}

/**
 * Try getting payment ID by subscription ID.
 *
 * Get a single payment ID based on the subscription ID if provided by Mollie.
 *
 * @since 3.0.0
 *
 * @param  string $subscription_id Subscription ID to get the payment ID
 *
 * @return string $post_id
 */
function pt_get_payment_id_by_subscription_id( $subscription_id ) {

	global $wpdb;

	if ( $subscription_id == null ) {
		return null;
	}

	// Find the first payment in database (search old to new) with the subscription ID
	$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value = %s AND meta_key = '_mollie_subscription_id'", $subscription_id ) );

	if (get_post_meta($post_id,'_payments',true)) {
		$post_id = pt_get_first_payment_id($post_id);
	}

	if ( $post_id == null ) {
		// Find the first payment in database (search old to new) with the subscription ID
		$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value = %s AND meta_key = '_subscription_id'", $subscription_id ) );

		if ( $post_id == null ) {
			return null;
		}
	}

	return $post_id;

}

/**
 * Get all payments by subscription ID (now subscription post ID, BC for Mollie subscription ID)
 *
 */
function pt_get_all_payments_by_subscription_id( $subscription_id ) {

	global $wpdb;

	if ( $subscription_id == null ) {
		return null;
	}

	$post_id = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value = %s AND meta_key = '_subscription_id'", $subscription_id ) );

	if ( $post_id == null ) {
		return null;
	}

	return $post_id;

}


/**
 * Try getting subscription by Mollie subscription ID.
 */

function pt_get_subscription_id_by_mollie_subscription_id( $mollie_subscription_id ) {

	global $wpdb;

	if ( $mollie_subscription_id == null ) {
		return null;
	}

	$args = array (
		'posts_per_page' => 1,
		'post_type'      => 'pt_subscription',
		'fields'         => 'ids',

		'meta_query'     => array (
			'relation'      => 'OR',
			array (
				'key'     => '_id',
				'value'   => $mollie_subscription_id,
				'compare' => '='
			),
			array (
				'key'     => '_mollie_subscription_id',
				'value'   => $mollie_subscription_id,
				'compare' => '='
			),

		)
	);

	$subscription = new WP_Query( $args );

	if ( array_key_exists( '0', $subscription->posts ) ) {
		$subscription_id = $subscription->posts[0];
	} else {
		return null;
	}

	return $subscription_id;

}

/**
 * Get payment statuses.
 *
 * Get a list of the available payment statuses.
 *
 * @since 1.0.0
 *
 * @return array List of payment statuses.
 */
function pt_get_payment_statuses() {

	$statuses = apply_filters( 'pt_payment_statuses', array (
		'open'         => __( 'Open', 'paytium' ),
		'cancelled'    => __( 'Cancelled', 'paytium' ),
		'expired'      => __( 'Expired', 'paytium' ),
		'failed'       => __( 'Failed', 'paytium' ),
		'paid'         => __( 'Paid', 'paytium' ),
		'refunded'     => __( 'Refunded', 'paytium' ),
		'charged_back' => __( 'Chargeback', 'paytium' ),
	) );

	return $statuses;

}

function pt_get_payment_sources() {

	$paytium_payment_sources = get_option('paytium_payment_sources');
	if ($paytium_payment_sources) {

		$paytium_payment_sources = unserialize($paytium_payment_sources);
		$payment_sources = array();

		if ( is_array($paytium_payment_sources)) {
			foreach ( $paytium_payment_sources as $payment_source ) {
				$payment_sources[ $payment_source ] = get_the_title( $payment_source );
			}
		}

		return $payment_sources;
	}
	else return false;
}


/**
 * Get order statuses.
 *
 * Get a list of the available order statuses.
 * An order status is different then a payment status.
 *
 * @since 1.0.0
 *
 * @return array List of payment statuses.
 */
function pt_get_order_statuses() {

	$statuses = apply_filters( 'pt_order_statuses', array (
		'new'        => __( 'New', 'paytium' ),
		'processing' => __( 'Processing', 'paytium' ),
		'completed'  => __( 'Completed', 'paytium' ),
	) );

	return $statuses;

}


/**
 * Get payment methods.
 *
 * Get a list of the available payment methods in a slug => name pair.
 *
 * @since 1.0.0
 *
 * @return array List of payment methods.
 */
function pt_get_payment_methods() {

	$payment_methods = apply_filters( 'pt_payment_methods', array (
		'ideal'             => __( 'iDEAL', 'paytium' ),
		'creditcard'        => __( 'Credit Card', 'paytium' ),
		'sofort'            => __( 'SOFORT', 'paytium' ),
		'mistercash'        => __( 'Bancontact', 'paytium' ),
		'banktransfer'      => __( 'Bank transfer', 'paytium' ),
		'directdebit'       => __( 'SEPA Direct Debit', 'paytium' ),
		'belfius'           => __( 'Belfius', 'paytium' ),
		'paypal'            => __( 'PayPal', 'paytium' ),
		'bitcoin'           => __( 'Bitcoin', 'paytium' ),
		'podiumcadeaukaart' => __( 'PODIUM Cadeaukaart', 'paytium' ),
		'paysafecard'       => __( 'paysafecard', 'paytium' ),
		'kbc'               => __( 'KBC/CBC Payment Button', 'paytium' ),
		'inghomepay'        => __( 'ING Home\'Pay', 'paytium' ),
		'giftcard'          => __( 'Cadeaukaarten', 'paytium' ),
		'eps'               => __( 'EPS', 'paytium' ),
		'giropay'           => __( 'Giropay', 'paytium' )
	) );

	return $payment_methods;

}

/**
 * Get customer ID by customer email.
 *
 * Get a single PT_Payment object based on the payment ID provided by Mollie.
 *
 * @since 1.0.0
 *
 * @param  string $payment_id Payment ID to get the pt_payment form.
 *
 * @return mixed              PT_Payment object when its a valid post ID, or false when not found.
 */
function pt_get_customer_by_email_in_paytium( $customer_email ) {

	global $wpdb;

	// If post_id not found, return
	$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_pt-customer-email' AND meta_value = %s ORDER BY post_id DESC LIMIT 1 ", $customer_email ) );

	// If post_id found, search for the customer ID
	$customer_id = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = %s AND meta_key = '_pt-customer-id' ORDER BY post_id DESC LIMIT 1", $post_id ) );


	return $customer_id;

}

/**
 * Copy field data from postmeta of one payment to an array $field_data
 *
 * @since 1.3.0
 *
 * @return array
 */

function pt_copy_field_data( $meta ) {

	$field_data = array ();

	if ( empty( $meta ) ) {
		return $field_data;
	}

	foreach ( $meta as $key => $value ) {

		// Add fields to custom data
		// Note: Every field has only one label, but two postmeta items in DB
		if ( strstr( $key, 'pt-field' ) || strstr( $key, 'source_' ) || strstr( $key, 'item' ) || strstr( $key, 'subscription_first' )
			|| strstr( $key, 'subscription_recurring' ) || strstr( $key, 'discount_' ) ) { // discount feature
			// Update key/label for fields with user defined label
			$field_data[ $key ] = $value[0];
		}

	}

	return $field_data;

}


/**
 * WP Ajax function to cancel a Mollie subscription
 *
 * @since 1.4.0
 *
 * @return mixed
 */

function pt_cancel_subscription() {

	check_ajax_referer( 'paytium-ajax-nonce', 'nonce' );

	global $pt_mollie;

	try {

		global $wpdb;

		$payment_id = isset( $_REQUEST['payment_id'] ) ? sanitize_text_field($_REQUEST['payment_id']) : '';
		$subscription_id = isset( $_REQUEST['subscription_id'] ) ? sanitize_text_field($_REQUEST['subscription_id']) : '';
		$customer_id     = isset( $_REQUEST['customer_id'] ) ? sanitize_text_field($_REQUEST['customer_id']) : '';

		$post_query = $wpdb->prepare(
			"
						SELECT post_id
						FROM {$wpdb->prefix}postmeta
						WHERE meta_key
						LIKE '_mollie_subscription_id' AND meta_value = '%s'
						", $subscription_id
		);
		$post_query = $wpdb->get_results($post_query,ARRAY_N);
		$pt_subscription_id = isset($post_query[0]) ? $post_query[0][0] : 0;

		if (!current_user_can('administrator') && get_post_field ('post_author', $pt_subscription_id) != get_current_user_id()) {

			paytium_logger($payment_id . '/' . $subscription_id . '/' . $customer_id . ' - ' . 'Subscription error: User (ID: '.
				get_current_user_id().') was trying to cancel subscription '.$pt_subscription_id.' ('.$subscription_id.')  without permission',__FILE__,__LINE__);

			wp_send_json_error();
		}

		$payment = pt_get_payment( $payment_id );
		pt_set_paytium_key( $payment->mode );

		$subscription = $pt_mollie->subscriptions->cancelForId( $customer_id,$subscription_id);

		// Update subscription in database
		$new_details = array (
			'subscription_cancelled_date' => preg_replace( '/T.*/', '', $subscription->cancelledDatetime ),
			'subscription_status'         => 'cancelled',
		);
		$sub_id = $payment->mollie_subscription_id != '' ? $payment->subscription_id : $payment->id;
		pt_update_payment_meta( $sub_id, $new_details );

		require_once('class-pt-payment.php');
		require_once('class-pt-item.php');
		do_action('paytium_cancel_subscription_email', (int)$sub_id);

		paytium_logger( $payment_id . '/' . $subscription_id . '/' . $customer_id . ' - ' . 'Subscription canceled by admin from Paytium.',__FILE__,__LINE__ );

		// Send response details to javascript for AJAX processing
		$response = array (
			'success' => true,
			'status' => ucfirst( $subscription->status ),
			'time' => preg_replace( '/T.*/', '', $subscription->cancelledDatetime )
		);
		wp_send_json( $response );

	}
	catch ( Mollie\Api\Exceptions\ApiException $e ) {
        paytium_logger( $payment_id . '/' . $subscription_id . '/' . $customer_id . ' - ' . 'Subscription error: ' . htmlspecialchars( $e->getMessage() ),__FILE__,__LINE__ );
		wp_send_json_error();
	}

	wp_die();
}

add_action( 'wp_ajax_pt_cancel_subscription', 'pt_cancel_subscription' );

/**
 * Get subscription first payment ID by subscription ID
 *
 * @param $subscription_id
 *
 * @return bool|PT_Payment
 */
function pt_get_first_payment_id($subscription_id) {


	$payments = unserialize(get_post_meta($subscription_id, '_payments',true));

	if ($payments) {
		$first_payment_id = $payments[0];
	}

	else {

		global $wpdb;
		$prepare = $wpdb->prepare( "SELECT post_id FROM " . $wpdb->prefix . "postmeta WHERE meta_key = '_subscription_id' AND meta_value = '%s'",$subscription_id);
		$first_payment_id = $wpdb->get_var( $prepare );
	}

	return $first_payment_id;
}

/**
 * Get all payments by customer ID
 *
 * @since 4.2.0
 *
 * @param $customer_id
 * @return null|array
 */
function pt_get_all_payments_by_customer_id( $customer_id ) { // my profile

	global $wpdb;

	if ( $customer_id == null ) {
		return null;
	}

	$post_id = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_author = %s AND post_type='pt_payment' ORDER BY id DESC", $customer_id ) );

	if ( $post_id == null ) {
		return null;
	}

	return $post_id;
}

/**
 * Get all subscriptions by customer ID
 *
 * @since 4.2.0
 *
 * @param $customer_id
 * @return null|array
 */
function pt_get_all_subscriptions_by_customer_id( $customer_id ) {

	global $wpdb;

	if ( $customer_id == null ) {
		return null;
	}

	$post_id = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_author = %s AND post_type='pt_subscription' ORDER BY id DESC", $customer_id ) );

	if ( $post_id == null ) {
		return null;
	}

	return $post_id;
}