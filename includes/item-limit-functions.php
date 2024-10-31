<?php

/**
 * Item Limit functions
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Update items limits option
 *
 * @since 2.2.0
 * @param $payment_post_id
 */

function paytium_limit_data_processing( $payment_post_id ) {

    if ( is_object( $payment_post_id ) ) {
        $payment_post_id = $payment_post_id->id;
    }

	paytium_logger( $payment_post_id . ' - ' . 'Start processing limit data for payment.',__FILE__,__LINE__);

	$payment_field_data = get_post_meta( $payment_post_id, null, true );

    $status = $payment_field_data['_status'][0];
    $last_status = get_post_meta( $payment_post_id, '_pt_limit_last_status', true );

    if ( ( $status != 'paid' ) || ($last_status == 'paid') ) {
        return;
    }

    $current_item_limits = array();

    foreach ($payment_field_data as $key => $value) {
        if (preg_match('/_pt-field-item-id-\d{1,3}$/', $key)
            || preg_match('/_pt-field-item-id-\d{1,3}_\d{1,2}$/', $key)) {

        	$limit = $payment_field_data['_pt-field-item-'.$value[0].'-limit'];

        	if (isset($limit)) {
				$current_item_limits[sanitize_key( $value[0] )] = array(
					'limit' => $limit[0],
					'quantity' => isset($payment_field_data['_pt-field-item-'.$value[0].'-quantity'][0]) ? (int)$payment_field_data['_pt-field-item-'.$value[0].'-quantity'][0] : 1,
				);
			}
        }
        if (preg_match('/_pt-field-general-item-id-\d{1,3}$/', $key)
            || preg_match('/_pt-field-general-item-id-\d{1,3}_\d{1,2}$/', $key)) {

			$item_key = str_replace('-general','', $key);
			$item_id = $payment_field_data[$item_key][0];

			if (!isset($current_item_limits[sanitize_key( $value[0] )])) {

				$current_item_limits[sanitize_key( $value[0] )] = array(
					'limit' => $payment_field_data['_pt-field-item-'.$value[0].'-general-limit'][0],
					'quantity' => isset($payment_field_data['_pt-field-item-'.$item_id.'-quantity'][0]) ? (int)$payment_field_data['_pt-field-item-'.$item_id.'-quantity'][0] : 1,
				);
			}
			else $current_item_limits[sanitize_key( $value[0] )]['quantity'] += isset($payment_field_data['_pt-field-item-'.$item_id.'-quantity'][0]) ? (int)$payment_field_data['_pt-field-item-'.$item_id.'-quantity'][0] : 1;
        }
    }

    paytium_logger(print_r($current_item_limits,true),__FILE__,__LINE__);


    if (empty($current_item_limits)) {
	    paytium_logger( $payment_post_id . ' - ' . 'No limit data found for payment.',__FILE__,__LINE__);
	    return;
    }

    if (get_option('paytium_item_limits')) {

        $paytium_item_limits = unserialize(get_option('paytium_item_limits'));

        foreach ($current_item_limits as $key => $value) {
            $paytium_item_limits[$key] = isset($paytium_item_limits[$key]) ? $paytium_item_limits[$key] + $value['quantity'] : $value['quantity'];
        }

        update_option('paytium_item_limits', serialize($paytium_item_limits));

	    paytium_logger( $payment_post_id . ' - ' . 'Add limit data to existing paytium_item_limits for payment.',__FILE__,__LINE__);
    }
    else {

        foreach ($current_item_limits as $key => $value) {
            $current_item_limits[$key] = $value['quantity'];
        }

        update_option('paytium_item_limits', serialize($current_item_limits));
	    paytium_logger( $payment_post_id . ' - ' . 'Add limit data to new paytium_item_limits for payment.',__FILE__,__LINE__);

    }

    // Prevent multiple Limit data processing
    if ( ! add_post_meta( $payment_post_id, '_pt_limit_last_status', $status, true ) ) {
        update_post_meta( $payment_post_id, '_pt_limit_last_status', $status );
    }
}

add_action( 'paytium_after_update_payment_from_admin', 'paytium_limit_data_processing', 10, 3);
add_action( 'paytium_after_pt_payment_update_webhook', 'paytium_limit_data_processing', 10, 3);


function pt_ajax_check_item_limits() {

	$limit_data = isset($_POST['data']) ? $_POST['data'] : '';
	if (!$limit_data) return;

	$paytium_item_limits = unserialize(get_option('paytium_item_limits'));
	$limit_exceeded = array();

	if (is_array($limit_data)) {
		foreach ( $limit_data as $item_id => $array ) {

			$item_id_lowercase = strtolower( $item_id );
			$quantity          = isset( $array['quantity'] ) ? (int) $array['quantity'] : 1;

			if ( ! empty( $paytium_item_limits[ $item_id_lowercase ] ) ) {
				if ( (int) $paytium_item_limits[ $item_id_lowercase ] + $quantity > (int) $array['limit'] ) {
					$array['items_left']        = (int) $array['limit'] - (int) $paytium_item_limits[ $item_id_lowercase ];
					$limit_exceeded[esc_attr($item_id)] = $array;
				}
			}

		}
	}

	if (!empty($limit_exceeded)) {
		$result = array('error' => true, 'message' => '', 'limit_exceeded' => $limit_exceeded);
	}
	else {
		$result = array('message' => '');
	}

	wp_send_json($result);

}
add_action('wp_ajax_pt_ajax_check_item_limits', 'pt_ajax_check_item_limits');
add_action('wp_ajax_nopriv_pt_ajax_check_item_limits', 'pt_ajax_check_item_limits');
