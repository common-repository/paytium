<?php

/**
 * Process payment functions
 *
 * @since   1.5.0
 *
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Function that will process the payment
 *
 * @since 1.0.0
 */
function pt_process_payment() {

	// Enable the below lines if you want to show all collected form data
	// At this point the data is not yet registered by Paytium
//	var_dump_p($_POST);die;
	//exit();

	// Create an array with all details of the payment in Paytium (not Mollie, that comes later)
	$paytium_payment = array ();

	// Get the details submitted by the form
	$paytium_payment['description']           = wp_kses_post( $_POST['pt-description'] );
	$paytium_payment['store_name']            = sanitize_text_field( $_POST['pt-name'] );
	$paytium_payment['pt_paytium_js_enabled'] = ( isset( $_POST['pt-paytium-js-enabled'] ) ? true : false );

	// Get the form load id used for amount validation
	$pt_form_load_id = ( isset( $_POST['pt-form-load'] ) ? sanitize_text_field($_POST['pt-form-load']) : '0');

	// Save bought items
	$item_meta = array ();
	$total     = 0;

	// Check if pt-subscription-interval is set and not empty, then set subscription to 1
	$paytium_payment['subscription']                              = ( isset( $_POST['pt-subscription-interval'] ) && ! empty( $_POST['pt-subscription-interval'] ) ? '1' : '0' );
	$paytium_payment['subscription_interval']                     = ( isset( $_POST['pt-subscription-interval'] ) ? $_POST['pt-subscription-interval'] : '' );
	$paytium_payment['subscription_times']                        = ( isset( $_POST['pt-subscription-times'] ) && (int) trim( $_POST['pt-subscription-times'] ) != 0 ? (int) trim( $_POST['pt-subscription-times'] ) : '' );
	$paytium_payment['subscription_first_payment']                = ( isset( $_POST['pt-subscription-first-payment'] ) ? pt_user_amount_to_float( $_POST['pt-subscription-first-payment'] ) : '' );
	$paytium_payment['subscription_first_payment_tax_percentage'] = ( isset( $_POST['pt-subscription-first-payment-tax-percentage'] ) ? pt_user_amount_to_float( $_POST['pt-subscription-first-payment-tax-percentage'] ) : '' );
	$paytium_payment['subscription_first_payment_label']          = ( isset( $_POST['pt-subscription-first-payment-label'] ) ? $_POST['pt-subscription-first-payment-label'] : $paytium_payment['description'] );
	$paytium_payment['subscription_recurring_payment']            = ( isset( $_POST['pt-subscription-recurring-payment'] ) ? pt_user_amount_to_float( $_POST['pt-subscription-recurring-payment'] ) : '' );

	$zero_tax = 0;

	if ( isset( $_POST['pt_items'] ) && is_array( $_POST['pt_items'] ) ) {

		if ( $paytium_payment['subscription_first_payment'] == '' ) {
			$i = 0;
			foreach ( $_POST['pt_items'] as $k => $item ) {

				$quantity = isset($item['multiplier_id']) ?
					(isset($_POST['pt_form_field']['pt_cf_number_'.$k]) ? (float)$_POST['pt_form_field']['pt_cf_number_'.$k] : 0) :
					(isset($item['quantity'] ) ? (int) $item['quantity'] : 1);

				if (isset($item['add_zero_tax'])) $zero_tax = $item['amount'];

				$tax_percentage = isset($item['tax_percentage']) ? absint( $item['tax_percentage'] ) : 0;
				$amount = isset($item['amount']) ? $item['amount'] : 0;
				$i ++;
				$prefix                                  = 'item-' . absint( $i ) . '-';
				$item_meta[ $prefix . 'amount' ]         = pt_calculate_amount_excluding_tax( (float) $amount, $tax_percentage );
				$item_meta[ $prefix . 'label' ]          = ! empty( $item['label'] ) ? wp_kses_post( $item['label'] ) : $paytium_payment['description'];
				$item_meta[ $prefix . 'value' ]          = ! empty( $item['value'] ) ? wp_kses_post( $item['value'] ) : '';
				$item_meta[ $prefix . 'type' ]           = sanitize_key( $item['type'] );
				$item_meta[ $prefix . 'tax-percentage' ] = $tax_percentage;
				$item_meta[ $prefix . 'tax-amount' ]     = $zero_tax != 0 ? $zero_tax : pt_calculate_tax_amount( (float) $amount, $item_meta[ $prefix . 'tax-percentage' ] );
				if ($zero_tax != 0) {
					$item_meta[ $prefix . 'zero-tax' ]   = true;
				}
				$item_meta[ $prefix . 'total-amount' ]   = (float) str_replace( ',', '.', $amount );
				$item_meta[ $prefix . 'quantity' ]       = $quantity;
				$total                                   += $item_meta[ $prefix . 'total-amount' ];
			}
		}

		if ( $paytium_payment['subscription_first_payment'] !== '' ) {

			$i = 0;

			// First payment item data
			$total = $paytium_payment['subscription_first_payment'];

			$i ++;
			$prefix                                  = 'item-' . absint( $i ) . '-';
			$item_meta[ $prefix . 'amount' ]         = pt_calculate_amount_excluding_tax( (float) $paytium_payment['subscription_first_payment'], absint( $paytium_payment['subscription_first_payment_tax_percentage'] ) );
			$item_meta[ $prefix . 'label' ]          = ! empty( $paytium_payment['subscription_first_payment_label'] ) ? wp_kses_post( $paytium_payment['subscription_first_payment_label'] ) : $paytium_payment['description'];
			$item_meta[ $prefix . 'tax-percentage' ] = absint( $paytium_payment['subscription_first_payment_tax_percentage'] );
			$item_meta[ $prefix . 'tax-amount' ]     = pt_calculate_tax_amount( (float) $paytium_payment['subscription_first_payment'], $item_meta[ $prefix . 'tax-percentage' ] );
			$item_meta[ $prefix . 'total-amount' ]   = (float) str_replace( ',', '.', $paytium_payment['subscription_first_payment'] );
			$item_meta[ $prefix . 'quantity' ]       = isset( $item['quantity'] ) ? (int) $item['quantity'] : 1;

			// Recurring payment item data
			$i = 0;
			foreach ( $_POST['pt_items'] as $k => $item ) {

				$tax_percentage = isset($item['tax_percentage']) ? absint( $item['tax_percentage'] ) : 0;
				$amount = isset($item['amount']) ? $item['amount'] : 0;
				$i ++;
				$prefix                                  = 'item-recurring-payment-' . absint( $i ) . '-';
				$item_meta[ $prefix . 'amount' ]         = pt_calculate_amount_excluding_tax( (float) $amount, $tax_percentage );
				$item_meta[ $prefix . 'label' ]          = ! empty( $item['label'] ) ? wp_kses_post( $item['label'] ) : $paytium_payment['description'];
				$item_meta[ $prefix . 'value' ]          = ! empty( $item['value'] ) ? wp_kses_post( $item['value'] ) : '';
				$item_meta[ $prefix . 'type' ]           = sanitize_key( $item['type'] );
				$item_meta[ $prefix . 'tax-percentage' ] = $tax_percentage;
				$item_meta[ $prefix . 'tax-amount' ]     = pt_calculate_tax_amount( (float) $amount, $item_meta[ $prefix . 'tax-percentage' ] );
				$item_meta[ $prefix . 'total-amount' ]   = (float) str_replace( ',', '.', $amount );
				$item_meta[ $prefix . 'quantity' ]       = isset( $item['quantity'] ) ? (int) $item['quantity'] : 1;
			}
		}

	} else {
		// For BC of custom integrations (EDD) with Paytium versions prior to 2.0
		$total = $_POST['pt-amount'];
	}

	if (isset($_POST['pt-get-parameter']) && !empty($_POST['pt-get-parameter'])) {

		$get_parameters = function_exists('pt_get_parameters') ? pt_get_parameters($_POST['pt-get-parameter']) : false;
	}

	$source_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$source_id = url_to_postid($source_link);

	$paytium_payment_sources = get_option('paytium_payment_sources');
	if (is_serialized($paytium_payment_sources) || is_array($paytium_payment_sources)) {
		$paytium_payment_sources = unserialize($paytium_payment_sources);
		if (is_array($paytium_payment_sources) && !in_array($source_id, $paytium_payment_sources)) {
			$paytium_payment_sources[] = $source_id;
			update_option('paytium_payment_sources',serialize($paytium_payment_sources));
		}
	}
	else {
		update_option('paytium_payment_sources',serialize(array($source_id)));
	}

//	var_dump_p($_POST);die;
	// Check if discount is set, check that it's correct, add amount to $items_meta
	if ( isset( $_POST['pt-discount']['code'] ) && ! empty( $_POST['pt-discount']['code'] ) ) {
		$total_before_discount = $total - $zero_tax;

		$discount_exclude_first_payment = isset($_POST['pt-discount-exclude-first-payment']) && $_POST['pt-discount-exclude-first-payment'] ? true : false;

		$total = pt_paytium_check_discount_code( $pt_form_load_id, $_POST['pt-discount'], $total_before_discount, $discount_exclude_first_payment );

		if ($paytium_payment['subscription_first_payment'] !== '') {
			$paytium_payment['subscription_first_payment'] = $total;
		}

		$discount_type = $_POST['pt-discount']['type'];
		$discount_initial_value = $_POST['pt-discount']['value'];
		$discount_times_to_use = $_POST['pt-discount']['times'];

		if ($discount_exclude_first_payment) {
			$discount_amount = $discount_type == 'percentage' ?
				pt_user_amount_to_float(($paytium_payment['subscription_recurring_payment'] / (1 - $discount_initial_value/100)) - $paytium_payment['subscription_recurring_payment']) :
				pt_user_amount_to_float($discount_initial_value);
		}
		else {
			$discount_amount = $discount_exclude_first_payment ?
				pt_user_amount_to_float($total_before_discount - $paytium_payment['subscription_recurring_payment']) :
				pt_user_amount_to_float($total_before_discount - $total);
		}

		$discount_meta = array(
			'discount_code' => $_POST['pt-discount']['code'],
			'discount_type' => $discount_type,
			'discount_times' => $discount_times_to_use,
			'discount_initial_value' => $discount_initial_value,
			'discount_value' => $discount_type == 'percentage' ? $discount_initial_value.'%' : pt_float_amount_to_currency($discount_initial_value, $_POST['pt-currency']),
			'discount_amount' => $discount_amount,
			'discount_associate_with_email' => isset($_POST['pt-discount-associate-with-email']) && $_POST['pt-discount-associate-with-email'] ? true : false,
			'discount_first_payment' => isset($_POST['pt-discount-first-payment']) && $_POST['pt-discount-first-payment'] ? true : false,
			'discount_exclude_first_payment' => isset($_POST['pt-discount-exclude-first-payment']) && $_POST['pt-discount-exclude-first-payment'] ? true : false,
		);

		$total = $total + $zero_tax;
	}
	elseif ((isset($_POST['pt-discount-checkbox']) && $discount_checkbox = $_POST['pt-discount-checkbox']) ||
		(isset($_POST['pt-quantity-discount']) && $discount_quantity = $_POST['pt-quantity-discount']) ||
		(isset($_POST['pt-amount-discount']) && $discount_amount = $_POST['pt-amount-discount'])) {

		$uid_key = $_POST['pt-discount-uid'];
		$uid = str_replace('pt_discount_uid_', '', $uid_key);
		$pt_id = $_POST['pt-form-id'];
		if (isset($discount_checkbox) && get_transient($uid_key) == 'pt_discount_checkbox_'.$source_id.'_'.$pt_id.'_'. $uid) {
			$key_prefix = 'pt_discount_checkbox_';
		}
		elseif (isset($discount_quantity) && get_transient($uid_key) == 'pt_quantity_discount_'.$source_id.'_'.$pt_id.'_'. $uid) {
			$key_prefix = 'pt_quantity_discount_';
		}
		else {
			$key_prefix = 'pt_amount_discount_';
		}

		if (get_transient($uid_key) == $key_prefix.$source_id.'_'.$pt_id.'_'.$uid) {

			$total_before_discount = $total - $zero_tax;
			$discount_type = $_POST['pt-discount']['type'];
			$discount_value = $_POST['pt-discount']['value'];

			$discount_data = get_transient($key_prefix.$source_id.'_'.$pt_id);
			if (isset($discount_quantity) || isset($discount_amount)) {
				foreach ($discount_data as $discount_datum) {

					if ($discount_value == $discount_datum['discount'] && $discount_type == $discount_datum['type']) {
						$discount_data_value = $discount_datum['discount'];
						$discount_data_type = $discount_datum['type'];
					}
				}
			}
			else {
				$discount_data_value = $discount_data['discount_value'];
				$discount_data_type = $discount_data['discount_type'];
			}


			if ($discount_data && isset($discount_data_value,$discount_data_type) && $discount_data_value == $discount_value && $discount_data_type == $discount_type) {

				$total = $discount_type === 'percentage' ?
					pt_user_amount_to_float($total_before_discount * ((100 - $discount_value) / 100)) :
					pt_user_amount_to_float($total_before_discount - $discount_value);

				$discount_amount = pt_user_amount_to_float($total_before_discount - $total);

				$discount_meta = array(
					'discount_type' => $discount_type,
					'discount_value' => $discount_type == 'percentage' ? $discount_value.'%' : pt_float_amount_to_currency($discount_value, $_POST['pt-currency']),
					'discount_amount' => $discount_amount,
				);

				$total = $total + $zero_tax;
			}
			else {
				// Redirect back to form if field validation fails
				wp_redirect( esc_url( add_query_arg( 'pt-field-validation-failed', 1 ) ) );
				die;
			}
		}
		else {
			// Redirect back to form if field validation fails
			wp_redirect( esc_url( add_query_arg( 'pt-field-validation-failed', 1 ) ) );
			die;
		}
	}

	// Add subscription options to database
	if (isset( $_POST['pt-subscription-interval-options-list'] ) && isset( $_POST['pt-subscription-interval-amounts-list'] )) {

		$interval_options = array_map('trim', explode( ',', $_POST['pt-subscription-interval-options-list'] ));
		$interval_amounts = explode( '/', $_POST['pt-subscription-interval-amounts-list'] );

		if (count($interval_options) == count($interval_amounts)) {
			$combined_list = array_combine($interval_options, $interval_amounts);
			if (isset($combined_list['once'])) {
				unset($combined_list['once']);
			}
			if (isset($combined_list['eenmalig'])) {
				unset($combined_list['eenmalig']);
			}

			$compare_to_amount = isset($total_before_discount) ? $total_before_discount : $total;
			foreach ($combined_list as $key => $value) {
				if ($value <= $compare_to_amount) {
					unset($combined_list[$key]);
				}
			}
			if ($combined_list) {
				$paytium_payment['subscription_options_list'] = json_encode($combined_list);
			}

			if (isset($discount_meta)) {
				$discounted_options_list = array();
				$discount_val = $_POST['pt-discount']['value'];
				foreach ($combined_list as $key => $value) {
					$discounted_options_list[$key] = $_POST['pt-discount']['type'] == 'percentage' ?
						number_format($value * ((100 - $discount_val) / 100),2, '.','') :
						number_format($value - $discount_val,2, '.','');
				}
				if ($discounted_options_list) {
					$paytium_payment['subscription_options_list_discount'] = json_encode($discounted_options_list);
				}
			}

		}
	}

	// Finally set the amount in $paytium_payment
	$paytium_payment['amount'] = pt_user_amount_to_float( $total );

	// Currency
	$paytium_payment['currency'] = isset($_POST['pt-currency']) && $_POST['pt-currency'] ? $_POST['pt-currency'] : get_option('paytium_currency', 'EUR'); // currency feature

	// Create a subscription_start_date for Paytium, as first_amount for Paytium is the full subscription amount.
	// Users can give a preference that needs to be calculated, otherwise use subscription_interval to set it.
	if ( isset( $_POST['pt-subscription-start-date'] ) ) {
		$custom_start_date = date( 'Y-m-d', strtotime( $_POST['pt-subscription-start-date'] ) );
		$now               = date( 'Y-m-d' );

		// Check if data is in the past, if so adjust year
		if ( date( 'Y-m-d', strtotime( $_POST['pt-subscription-start-date'] ) ) < $now ) {
			$custom_start_date = date( 'Y-m-d', strtotime( $_POST['pt-subscription-start-date'] . ' +1 year' ) );
		}

		$paytium_payment['subscription_start_date'] = $custom_start_date;
	} else {
		$paytium_payment['subscription_start_date'] = date( 'Y-m-d', strtotime( $paytium_payment['subscription_interval'] ) );
	}

	// Check if pt-paytium-no-payment is set, if it is, this form doesn't require a payment
	$paytium_payment['no_payment']         = ( isset( $_POST['pt-paytium-no-payment'] ) ? true : false );
	$paytium_payment['no_payment_invoice'] = ( isset( $_POST['pt-paytium-no-payment-invoice'] ) ? true : false );

	// Get current post/page URL and set as URL where customers will be redirected to after payment
	$paytium_payment['pt_redirect_url'] = sanitize_text_field( isset( $_POST['pt_redirect_url'] ) ? $_POST['pt_redirect_url'] : home_url() );

	// Check for this param, without it JS did not process! Check needs to be outside of JS, for when JS is not enabled.
	if ( $paytium_payment['pt_paytium_js_enabled'] == false && ! is_admin() ) {
		wp_redirect( esc_url( add_query_arg( 'pt-js-validation-failed', 1 ) ) );
		die;
	}

	// Add validation for minimum data, now amount. Check needs to be outside of JS, for when JS is not enabled.
	if ( (empty( $paytium_payment['amount'] ) || ($paytium_payment['amount'] <= 0.99 ))  && ( $paytium_payment['no_payment'] !== true ) ) {
		wp_redirect( esc_url( add_query_arg( 'pt-amount-validation-failed', 1 ) ) );
		die;
	}

	// One exception, change redirect URL to admin when going through the setup wizard in admin
	if ( is_admin() ) :
		$paytium_payment['pt_redirect_url'] = add_query_arg( 'step', 'payment-test', admin_url( 'admin.php?page=pt-setup-wizard' ) );
	endif;

	// Get the active Mollie API key
	if ( get_option( 'paytium_admins_test_mode', false ) == 1 && in_array( 'administrator', wp_get_current_user()->roles ) ) {
		pt_set_paytium_key( 'test' );
	} else {
		pt_set_paytium_key();
	}

	$meta = array();

	$meta['source_link'] = $source_link;
	$meta['source_id'] = $source_id;

	$meta = apply_filters( 'pt_meta_values', $meta );

	// Extra server side validation for some fields (postcode, email)
	pt_paytium_validate_form_values( $pt_form_load_id, $meta );

	// We allow a spot to hook in, but the hook in is responsible for all of the code.
	// If the action is non-existent, then we run a default for the button.
	if ( has_action( 'pt_process_payment' ) ) {
		do_action( 'pt_process_payment' );
	} else {

		try {

			// Save payment to WP posts database
			$paytium_payment['post_id'] = pt_create_payment( array (
				'order_status' => 'new',
				'method'       => '',
				'meta'         => $meta,
			) );

			// Add subscription details, if payment is a subscription
			if ( $paytium_payment['subscription'] == 1 ) {

				$paytium_payment['subscription_id'] = pt_create_subscription();
				// Add general subscription details
				pt_update_payment_meta( $paytium_payment['subscription_id'], array (
					'subscription_status'     => 'pending',
					'subscription_interval'   => $paytium_payment['subscription_interval'],
					'subscription_times'      => $paytium_payment['subscription_times'],
					'subscription_start_date' => $paytium_payment['subscription_start_date'],
					'subscription_options_list' => isset($paytium_payment['subscription_options_list']) ? $paytium_payment['subscription_options_list'] : '',
					'subscription_options_list_discount' => isset($paytium_payment['subscription_options_list_discount']) ? $paytium_payment['subscription_options_list_discount'] : '',
					'payments'                => serialize( array ( $paytium_payment['post_id'] ) ),
				) );

				// Add subscription first & recurring payment details if set
				if ( $paytium_payment['subscription_first_payment'] !== '' ) {
					pt_update_payment_meta( $paytium_payment['subscription_id'], array (
						'subscription_first_payment'     => pt_user_amount_to_float( $paytium_payment['subscription_first_payment'] ),
						'subscription_recurring_payment' => pt_user_amount_to_float( $paytium_payment['subscription_recurring_payment'] ),
					) );

					pt_update_payment_meta( $paytium_payment['post_id'], array (
						'subscription_first_payment'     => pt_user_amount_to_float( $paytium_payment['subscription_first_payment'] ),
						'subscription_recurring_payment' => pt_user_amount_to_float( $paytium_payment['subscription_recurring_payment'] ),
					) );

				} else {
					pt_update_payment_meta( $paytium_payment['subscription_id'], array (
						'subscription_recurring_payment' => pt_user_amount_to_float( $paytium_payment['amount'] ),
					) );
				}

				pt_update_payment_meta( $paytium_payment['post_id'], array (
					'subscription_id' => $paytium_payment['subscription_id']
				) );

				if (isset($get_parameters) && !empty($get_parameters)) {
					pt_update_payment_meta($paytium_payment['subscription_id'], $get_parameters);
				}
			}

			// Insert items
			if ( ! empty( $item_meta ) ) {
				pt_update_payment_meta( $paytium_payment['post_id'], $item_meta );
			}

			if (isset($_POST['pt-no-emails'])) {
				pt_update_payment_meta($paytium_payment['post_id'], array( 'pt_no_emails' => $_POST['pt-no-emails'] ));
			}

			// Generate a secure payment key to use in the redirectURL
			$paytium_payment['payment_key'] = substr( sha1( sha1( $paytium_payment['post_id'] ) . $paytium_payment['post_id'] ), 0, 13 );

			// Generate redirect URL with payment key and anchor link to jump to payment message
			$paytium_payment['redirect_url'] = add_query_arg( 'pt-payment', $paytium_payment['payment_key'], ! empty ( $paytium_payment['pt_redirect_url'] ) ? $paytium_payment['pt_redirect_url'] : home_url() ) . "#pt-payment-details";

			// Generate webhook URL
			$paytium_payment['webhook_url']  = add_query_arg( 'pt-webhook', 'paytium', home_url( '/' ) );

			// Convert internal metadata to user readable (move labels to keys etc)
			$paytium_payment['mollie_metadata'] = pt_convert_to_mollie_metadata( $meta );

			// Set customer to none at first
			$paytium_payment['customer_id'] = '';

			// Get customer details (Name, Email) from meta to use Mollie Customer API
			$mollie_customer = pt_get_mollie_customer_data_from_meta( $meta );


			// Only create a Mollie customer when name & email are set.
			if ( ! empty( $mollie_customer['name'] ) && ! empty( $mollie_customer['email'] ) && ( $paytium_payment['no_payment'] == false ) && ( get_option( 'paytium_no_api_keys' ) != 1 ) ) {

				// Decided to always create a Mollie customer, even when payment is not a subscription
				// See Paytium issue #74 for discussion: https://github.com/davdebcom/paytium/issues/74
				$paytium_payment['customer_id'] = paytium_create_new_mollie_customer( $mollie_customer, $paytium_payment['post_id'] );

				// Save Mollie customer details to Payment post meta
				$new_payment_details = ( array (
					'pt-customer-name'  => $mollie_customer['name'],
					'pt-customer-email' => $mollie_customer['email'],
					'pt-customer-id'    => $paytium_payment['customer_id'],
				) );
				pt_update_payment_meta( $paytium_payment['post_id'], $new_payment_details );

				if (isset($paytium_payment['subscription_id'])) {
					pt_update_payment_meta( $paytium_payment['subscription_id'], array('pt-customer-id'  => $paytium_payment['customer_id']));
				}
			} else {
				paytium_logger ( $paytium_payment['post_id'] . ' - ' . 'No Mollie Customer created, no fields with type name & email found or no payment form.',__FILE__,__LINE__);
			}

			// Update payment description with Paytium payment ID and add filter so developers can manipulate it
			$paytium_payment['description'] = wp_unslash( $paytium_payment['description'] . ' ' . $paytium_payment['post_id'] );
			$paytium_payment['description'] = apply_filters( 'paytium_payment_description', $paytium_payment['description'], $paytium_payment, $meta );

			// Set the correct redirect for different scenarios

			// --- Form with payment and API keys
			if ( $paytium_payment['no_payment'] == false && get_option( 'paytium_no_api_keys' ) == 0 ) {

				// Extra server side validation for amounts
				if (isset($discount_amount)) {
					pt_paytium_validate_form_amounts( $pt_form_load_id, $paytium_payment['amount'], $discount_amount );  // discount feature
				}
				else pt_paytium_validate_form_amounts( $pt_form_load_id, $paytium_payment['amount'] );

				$redirect = pt_paytium_create_mollie_payment_and_redirect( $paytium_payment );
			}

			// --- Form with payment and without API keys, and current user can manage options
			if ( $paytium_payment['no_payment'] == false && get_option( 'paytium_no_api_keys' ) == 1 && current_user_can( 'manage_options' ) ) {
				$redirect = pt_paytium_no_api_key_payment_and_redirect( $paytium_payment );
			}

			// --- Form without payment
			if ( $paytium_payment['no_payment'] == true ) {
				$redirect = pt_paytium_update_form_submission_and_redirect( $paytium_payment );
			}

			// Add discount meta
			if (isset($discount_meta)) {
				pt_update_payment_meta( $paytium_payment['post_id'], $discount_meta );
			}

			// Add zero tax
			if ($zero_tax > 0) {
				pt_update_payment_meta( $paytium_payment['post_id'], ['zero_tax' => $zero_tax] );
			}

			// Add Query parameters meta
			if (isset($get_parameters) && $get_parameters) {
				pt_update_payment_meta( $paytium_payment['post_id'], $get_parameters );
			}

			// Add a filter here to allow developers to process payment as well
			do_action( 'paytium_after_full_payment_saved', $paytium_payment['post_id'] );

			// Redirect user to Mollie for payment or message for form submissions
			wp_redirect( $redirect, '303' );
			die;

		}
		catch
		( Mollie\Api\Exceptions\ApiException $e ) {

			if ( get_post( $paytium_payment['post_id'] ) != null ) {
				update_post_meta( $paytium_payment['post_id'], '_pt_payment_error', htmlspecialchars( $e->getMessage() ) );
				update_post_meta( $paytium_payment['post_id'], '_status', 'failed' );
			}

			paytium_logger( $paytium_payment['post_id'] . ' - ' . 'Creating payment failed: ' . htmlspecialchars( $e->getMessage() ),__FILE__,__LINE__ );

			if ( strpos( $e->getMessage(), 'No suitable payment methods found' ) !== false ) {
				echo sprintf( __( 'Review %sthis FAQ%s to solve this problem. ', 'paytium' ), '<a href="https://www.paytium.nl/handleiding/veelgestelde-vragen/foutmelding-no-suitable-payment-methods-found/">', '</a>' );
				echo '<br />';
			}

			echo( 'Creating payment failed: ' . htmlspecialchars( $e->getMessage() ) );
		}

		exit;
	}

	return;

}

// We only want to process the payment if form submitted
if ( isset( $_POST['pt-amount'] ) ) {
	// Changed from init to wp_loaded to solve WooCommerce conflict - http://wordpress.stackexchange.com/a/67635
	add_action( 'wp_loaded', 'pt_process_payment' );
}

/**
 * With Paytium payment, create a Mollie payment and redirect user to Mollie
 *
 * @since   1.5.0
 */
function pt_paytium_create_mollie_payment_and_redirect( $paytium_payment ) {

	global $pt_mollie;

	// Note: (first) payments are always required for Paytium, because we use a workaround with first amount & start date.

	// Is this a subscription? Then set Mollie $recurringType to first
	$paytium_payment['recurringType'] = ( $paytium_payment['subscription'] == '1' ? 'first' : null );

	$new_payment_details = array (
		'amount'        => ['currency' => $paytium_payment['currency'], 'value' => strval(number_format($paytium_payment['amount'],2, '.',''))], // currency feature
		'customerId'    => $paytium_payment['customer_id'],
		'sequenceType' => $paytium_payment['recurringType'],
		'description'   => $paytium_payment['description'],
		'redirectUrl'   => $paytium_payment['redirect_url'],
		'webhookUrl'    => $paytium_payment['webhook_url'],
		'metadata'      => array (
			'Store'    => wp_unslash( $paytium_payment['store_name'] ),
			'Order ID' => wp_unslash( $paytium_payment['description'] ),
			'Details'  => $paytium_payment['mollie_metadata'],
		)
	);

	// Paytium 2.1: Removed storing custom field data in Mollie metadata,
	// Because it's limited to 1024 KB, and that would limit the
	// amount of custom fields users of Paytium could add.
	// See: https://www.paytium.nl/handleiding/veelgestelde-vragen/extra-velden-als-metadata-meesturen-naar-mollie/
	$add_mollie_metadata = FALSE;
	$add_mollie_metadata = apply_filters('paytium_add_mollie_metadata', $add_mollie_metadata);

	if ( $add_mollie_metadata == FALSE ) {
		unset( $new_payment_details['metadata']['Details'] );
	}

	// Create payment at Mollie
	$payment = $pt_mollie->payments->create( $new_payment_details );



	// Save new data from Mollie to the Payment post meta
	$update_payment_details = ( array (
		'payment_id'  => $payment->id,
		'mode'        => $payment->mode,
		'amount'      => $payment->amount->value,
		'currency'    => $payment->amount->currency,
		'description' => $payment->description,
		'status'      => $payment->status,
		'payment_key' => $paytium_payment['payment_key']
	) );
    pt_update_payment_meta( $paytium_payment['post_id'], $update_payment_details );

	// Get payment URL (URL at Mollie) where user should be redirect to
	return $payment->getCheckoutUrl();

}

/**
 * With Paytium payment without a payment (regular form), update form submission and redirect to Thank You page
 *
 * @since   1.5.0
 */
function pt_paytium_update_form_submission_and_redirect( $paytium_payment ) {

	// Save new data from Mollie to the Payment post meta
    $no_payment_invoice = $paytium_payment['no_payment_invoice'] === true ? '1' : '';
	$new_payment_details = ( array (
		'payment_id'    => null,
		'mode'          => null,
		'amount'        => '0',
		'description'   => $paytium_payment['description'],
		'status'        => 'open',
		'payment_key'   => $paytium_payment['payment_key'],
		'pt_no_payment' => '1',
        'pt_no_payment_invoice' => $no_payment_invoice,
	) );
	pt_update_payment_meta( $paytium_payment['post_id'], $new_payment_details );

	return $paytium_payment['redirect_url'];
}

/**
 * Create Paytium payment without Mollie API key and redirect to Thank You page
 *
 * @since   2.2.0
 */
function pt_paytium_no_api_key_payment_and_redirect( $paytium_payment ) {

	$new_payment_details = ( array (
		'payment_id'    => null,
		'mode'          => null,
		'amount'        => $paytium_payment['amount'],
		'description'   => $paytium_payment['description'],
		'status'        => 'open',
		'payment_key'   => $paytium_payment['payment_key'],
	) );
	pt_update_payment_meta( $paytium_payment['post_id'], $new_payment_details );

	return $paytium_payment['redirect_url'];
}


/**
 * Collect all field data and combine into one meta array
 *
 * @since 1.0.0
 */
function pt_add_all_field_data_to_meta_array( $meta ) {

	// Loop to get all fields and their labels
	foreach ( $_POST as $key => $value ) {

		$is_field = strstr( $key, 'pt-field-' );

		$is_customer_details = strstr( $key, 'pt-customer-details-' );

		if ( $is_field || $is_customer_details ) {

			// Sanitize all keys
			$key = sanitize_key( $key );

			// Sanitize all value's, even when they are in an array
			if ( ! is_array( $value ) ) {
				$value = sanitize_text_field( $value );
			} else {
				foreach ( $value as $item_key => $item_value ) {
					$value[ $item_key ] = sanitize_text_field( $item_value );
				}
			}

			if ( ( strstr( $key, 'pt-field-email' ) ) && ( strstr( $key, '-label' ) == false ) ) {
				$value = sanitize_email( $value );
			}

			$meta[ $key ] = $value;
		}

        if ($key == 'pt-paytium-user-data') {
            $meta['pt-user-role'] = $value;
        }

        if ($key == 'pt_items') {
            foreach ($value as $items_id => $post_data) {

                if (isset($post_data['item_id'], $post_data['limit'], $post_data['limit-message'])) {

                    $meta['pt-field-item-id-'.$items_id.'_0'] = sanitize_text_field( $post_data['item_id'] );
                    $meta['pt-field-item-'.sanitize_text_field( $post_data['item_id']).'-limit'] = sanitize_key( $post_data['limit'] );
                    $meta['pt-field-item-'.sanitize_text_field( $post_data['item_id']).'-limit-message'] = $post_data['limit-message'];
                    if(isset($post_data['quantity'])) {
	                    $meta['pt-field-item-' . sanitize_text_field($post_data['item_id']) . '-quantity'] = $post_data['quantity'];
                    }
                }
				if (isset($post_data['general_item_id'], $post_data['general_limit'])) {
					$meta['pt-field-general-item-id-'.$items_id.'_0'] = sanitize_text_field( $post_data['general_item_id'] );
					$meta['pt-field-item-'.sanitize_text_field( $post_data['general_item_id']).'-general-limit'] = sanitize_key( $post_data['general_limit'] );
					if (!isset($post_data['limit']) && isset($post_data['item_id'])) {
						$meta['pt-field-item-id-'.$items_id.'_0'] = sanitize_text_field( $post_data['item_id'] );
						if(isset($post_data['quantity'])) {
							$meta['pt-field-item-' . sanitize_text_field($post_data['item_id']) . '-quantity'] = $post_data['quantity'];
						}
					}
				}

                if (isset($post_data['limit_data'], $post_data['limit-message'])) {

                    $limit_data = json_decode(stripcslashes ($post_data['limit_data']),true);

                    if (!empty($limit_data)) {
                        $i = 1;
                        foreach ($limit_data as $id => $array) {
                            $meta['pt-field-item-id-'.$items_id.'_'.$i] = sanitize_text_field( $id );
                            if (isset($array['limit'])) {
								$meta['pt-field-item-'.sanitize_text_field( $id ).'-limit'] = $array['limit'];
							}
                            if (isset($post_data['limit-message']) && $post_data['limit-message'] != 'general') {
								$meta['pt-field-item-'.sanitize_text_field( $id ).'-limit-message'] = $post_data['limit-message'];
							}
	                        if(isset($array['quantity'])) {
		                        $meta['pt-field-item-'.sanitize_text_field( $id ).'-quantity'] = (int)$array['quantity'];
	                        }
	                        if(isset($array['general_item_id'], $array['general_limit'])) {
								$meta['pt-field-general-item-id-'.$items_id.'_'.$i] = $array['general_item_id'];
								$meta['pt-field-item-'.sanitize_text_field( $array['general_item_id'] ).'-general-limit'] = $array['general_limit'];
	                        }
                            $i++;
                        }
                    }
                }

				if (isset($post_data['crowdfunding_id'])) {
					$meta['crowdfunding_id'] = sanitize_text_field( $post_data['crowdfunding_id'] );
				}
			}
        }
	}

	if (isset($_FILES['pt-paytium-uploaded-file'])) {

		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );

		$files_data = array();
		$files = $_FILES["pt-paytium-uploaded-file"];
		foreach ( $files['name'] as $key => $value ) {
			if ( $files['name'][ $key ] ) {
				$file          = array(
					'name' => $files['name'][ $key ],
					'type' => $files['type'][ $key ],
					'tmp_name' => $files['tmp_name'][ $key ],
					'error' => $files['error'][ $key ],
					'size' => $files['size'][ $key ]
				);
				$_FILES        = array( "upload_file" => $file );
				$attachment_id = media_handle_upload( "upload_file", 0 );

				if ( is_wp_error( $attachment_id ) ) {
					paytium_logger('Error code '.$attachment_id->get_error_code().' : '.$attachment_id->get_error_message(),__FILE__,__LINE__);
				}
				else $files_data[$file['name']] =  wp_get_attachment_url($attachment_id);
			}
		}
		if (!empty($files_data)) {
			$meta['pt-uploaded-files'] = serialize($files_data);
		}
	}

	return $meta;

}

add_filter( 'pt_meta_values', 'pt_add_all_field_data_to_meta_array' );


/**
 * Extra server side validation for some fields (postcode, email)
 *
 * @since 3.1.3
 */
function pt_paytium_validate_form_values( $form_load_id, $meta ) {

	$validation_failed = false;

	// Make sure it was possible to set a form load id at all, otherwise skip validation
	if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {

		// Get form data based on form load id
		$form_load_data = get_transient( 'paytium_form_load_' . $form_load_id );

		// Check that transient was found
		if ( $form_load_data !== false ) {

			// Get only the fields
			$fields = $form_load_data['fields'];

			// Loop through all received fields and validate the data
			foreach ( $meta as $key => $value ) {

				// Skip different field data types
				if ( strstr( $key, '-label' ) || strstr( $key, '-user-data' ) || strstr( $key, 'source' ) ) {
					continue;
				}

				// Get the ID of the field
				$key_id = preg_replace( '/[^0-9]/', '', $key );

				// Get the field required status
				$required = isset($fields[ $key_id ]) ? $fields[ $key_id ]['required'] : '';

				// Skip a field if it's not required, and value is empty
				if ( empty( $value ) && empty( $required ) ) {
					continue;
				}

				// Email
				if ( strstr( $key, '-email-' ) && ! preg_match( '/^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,15})+$/', $value ) ) {
					$validation_failed = true;
				}

				// Postcode
				if ( strstr( $key, '-postcode-' ) && ! preg_match( '/^\d{4}\s?\w{2}$/', $value ) ) {
					$validation_failed = true;
				}

				// Date
				if ( strstr( $key, '-date-' ) || strstr( $key, '-birthday-' ) ) {
					$tempDate          = explode( '-', $value );
					$validation_failed = ! checkdate( $tempDate[1], $tempDate[0], substr( $tempDate[2], 0, 4 ) );
				}

			}

		}
	}

	if ( $validation_failed ) {

		// Delete the transient
		delete_transient( 'paytium_form_load_' . $form_load_id );

		// Redirect back to form if field validation fails
		wp_redirect( esc_url( add_query_arg( 'pt-field-validation-failed', 1 ) ) );
		die;
	}

}

/**
 * Extra server side validation for form amounts
 *
 * @since 3.1.3
 */
function pt_paytium_validate_form_amounts( $form_load_id, $amount, $discount_amount = 0 ) {

	// Make sure it was possible to set a form load id at all, otherwise skip validation
	if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {

		// Get all form data based on form load id
		$form_load_data = get_transient( 'paytium_form_load_' . $form_load_id );

		$amounts = array();

		// Sort amounts so lowest is first in the array
		if ( is_array($form_load_data['amounts'])) {
			$amounts = $form_load_data['amounts'];
			sort( $amounts );
		}

		// First: check that transient was found
		// Secondly: make sure total amount is higher then lowest amount
		if ( $form_load_data === false || $amount < $amounts[0] - $discount_amount ) { // discount feature

			// Delete the transient
			delete_transient( 'paytium_form_load_' . $form_load_id );

			// Redirect back to form as $total is lower then lowest amount in the form
			wp_redirect( esc_url( add_query_arg( 'pt-field-validation-failed', 1 ) ) );
			die;

		}
	}
}

/**
 * Extra server side validation for discount code
 *
 * @param $form_load_id
 * @param $discount
 *
 * @since 4.1.1
 */
function pt_paytium_check_discount_code( $form_load_id, $discount, $total, $exclude_first_payment = false  ) {

	$validation_failed      = true;
	$form_load_id           = sanitize_text_field( $form_load_id );
	$user_discount['code']  = sanitize_text_field( $discount['code'] );
	$user_discount['type']  = sanitize_text_field( $discount['type'] );
	$user_discount['value'] = sanitize_text_field( $discount['value'] );
	$user_discount['times'] = sanitize_text_field( $discount['times'] );

	// Make sure it was possible to set a form load id at all, otherwise skip validation
	if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {

		// Get form data based on form load id
		$form_load_data = get_transient( 'paytium_form_load_' . $form_load_id );

		// Check that transient was found
		if ( $form_load_data !== false ) {

			if ($exclude_first_payment) return $total;
			// Get only the fields
			$discounts = $form_load_data['discounts'];

			// Loop through all discounts and check if user entered discount code is found
			foreach ( $discounts as $discount ) {

				if ( $discount === $user_discount ) {
					$validation_failed = false;

					// Do some kind of processing

					if (($discount['type'] === 'percentage' )) {
						$total = pt_user_amount_to_float($total * ((100 - $discount['value']) / 100));
					}

					if (($discount['type'] === 'amount' )) {
						$total = pt_user_amount_to_float($total - $discount['value']);
					}

					return $total;
				}

			}

		}
	}

	if ( $validation_failed ) {

		// Delete the transient
		delete_transient( 'paytium_form_load_' . $form_load_id );

		// Redirect back to form if field validation fails
		wp_redirect( esc_url( add_query_arg( 'pt-field-validation-failed', 1 ) ) );
		die;
	}

}

/**
 * Convert field data in $meta to user-readable array for Mollie metadata
 *
 * @since 1.0.0
 */
function pt_convert_to_mollie_metadata( $meta ) {

	$mollie_metadata = array ();

	$count = 0;
	foreach ( $meta as $key => $value ) {

		// Drop textarea fields from Mollie metadata,
		// they often contain too much text and will hit the metadata size limit
		if ( strstr( $key, '-textarea-' ) ) {
			continue;
		}

		// Add fields to Mollie metadata
		if ( strstr( $key, '-label' ) ) {
			// Update key/label for fields with user defined label
			$field_key                 = str_replace( '-label', '', $key );
			$mollie_metadata[ $value ] = isset( $meta[ $field_key ] ) ? $meta[ $field_key ] : '';
		}

		// Add customer details fields to Mollie metadata
		if ( strstr( $key, 'pt-customer-details-' ) ) {
			$customer_details_key                     = ucfirst( str_replace( 'house_number', 'House number', str_replace( '-', ' ', str_replace( 'pt-customer-details-', '', $key ) ) ) );
			$mollie_metadata[ $customer_details_key ] = $value;
		}

		$count += 1;
		if ( $count == 40 ) {
			$mollie_metadata['Warning 1/3'] = 'Not all information is shown here.';
			$mollie_metadata['Warning 2/3'] = 'Mollie limits this to 1024KB.';
			$mollie_metadata['Warning 3/3'] = 'View the rest in Paytium in WordPress.';
			break;
		}
	}

	return $mollie_metadata;

}

/**
 * In the field data, find the field name and email to use a customer for Mollie Customers API
 *
 * @since 1.2.0
 *
 * @param   array $meta All fields from the Paytium form
 *
 * @return  array   $mollie_customer    Array that contains name and email for customer
 *
 */
function pt_get_mollie_customer_data_from_meta( $meta ) {

	$mollie_customer = array ();

	foreach ( $meta as $key => $value ) {

		// Skip all labels
		if ( strpos( $key, '-label' ) !== false ) {
			continue;
		}

		// If data contains only a name field/meta
		if ( strpos( $key, 'pt-field-name-' ) !== false ) {
			$mollie_customer['name'] = $value;
			break;
		}

		// If data contains first name and last name
		if ( strpos( $key, 'pt-field-firstname-' ) !== false ) {
			$mollie_customer['name'] = $value;
		}

		if ( strpos( $key, 'pt-field-lastname-' ) !== false ) {
			$mollie_customer['name'] .= ' ' . $value;
		}

	}

	foreach ( $meta as $key => $value ) {

		if ( strpos( $key, 'pt-field-email-' ) !== false ) {
			$mollie_customer['email'] = $value;
			break;
		}
	}

	return $mollie_customer;
}

/**
 * Create a new Mollie customer
 *
 * @since 1.4.0
 *
 */
function paytium_create_new_mollie_customer( $mollie_customer, $payment_id ) {

	global $pt_mollie;

	$customer = $pt_mollie->customers->create( array (
		"name"  => wp_unslash( $mollie_customer['name'] ),
		"email" => $mollie_customer['email'],
	) );

	paytium_logger( $payment_id . ' - ' .'New customer created at Mollie for: ' . implode( ', ', $mollie_customer ) . ', ' . $customer->id,__FILE__,__LINE__ );

	return $customer->id;
}

/**
 * Send post meta
 *
 * @since 1.0.0
 */
function pt_cf_checkout_meta( $meta ) {

	if ( isset( $_POST['pt_form_field'] ) ) {
		foreach ( $_POST['pt_form_field'] as $k => $v ) {
			// Drop the default value for paytium_radio and paytium_dropdown
			// I have a superior way to store key and value (see public.js)
			if ( strstr( $k, 'pt_cf_' ) ) {
				continue;
			}

			if ( ! empty( $v ) ) {
				$meta[ $k ] = $v;
			}
		}
	}

	return $meta;

}


add_filter( 'pt_meta_values', 'pt_cf_checkout_meta' );