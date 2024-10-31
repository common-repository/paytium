<?php

/**
 * Webhook URL functions
 *
 * @since   1.5.0
 *
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Webhook request.
 *
 * When the current request is a Mollie webhook request, process accordingly and bail early.
 *
 * @since 1.0.0
 *
 * @param  mixed $request WP request.
 *
 * @return mixed          WP request when the current request isn't a PT request.
 */
function pt_payment_update_webhook( $request ) {

	if ( isset( $_GET['pt-webhook'] ) ) {

		global $pt_mollie;

		$mollie_payment_id = isset($_POST['id']) ? preg_replace('/[^\w]/', '', $_POST['id']) : '';

		try {

			paytium_logger( $mollie_payment_id . ' - ' . 'Webhook called.',__FILE__,__LINE__ );

			$payment = pt_get_payment_by_payment_id( $mollie_payment_id );

			$mode    = ( isset( $payment->mode ) && $payment->mode == 'test' ) ? 'test' : 'live';
			pt_set_paytium_key( $mode );

			$mollie = $pt_mollie->payments->get( $mollie_payment_id );

			$status = pt_get_new_payment_status($mollie);

			//
			// START UNKNOWN PAYMENT PROCESSING - maybe it's a renewal payment for a subscription?
			//

			if ( $payment == null ) {

				// Is there a subscription ID?
				if ( $mollie->subscriptionId == null ) {
					paytium_logger( $mollie->id . ' - ' . 'Webhook: No known payment, no subscriptionId!',__FILE__,__LINE__ );
					pt_set_http_response_code_and_exit( '400' );
				}

				$first_payment = pt_get_payment_by_subscription_id( $mollie->subscriptionId );

				if ( $first_payment == null ) {
					paytium_logger( $mollie->id . ' - ' . 'Webhook: No payment or subscription found.',__FILE__,__LINE__ );
					pt_set_http_response_code_and_exit( '400' );
				}

				paytium_logger( $mollie->id . ' - ' . 'Webhook: Start unknown payment processing.',__FILE__,__LINE__ );

				$pt_subscription_id = pt_get_subscription_id_by_mollie_subscription_id( $mollie->subscriptionId );

				$WP_Http = new WP_Http();
				$args = array(
					'method' => 'GET',
					'headers' => array(
						'Authorization' => 'Bearer '.pt_get_api_key(),
					)
				);

				$result = $WP_Http->request( 'https://api.mollie.com/v2/customers/'.$mollie->customerId.'/subscriptions/'.$mollie->subscriptionId.'', $args );
				$result = json_decode($result['body'],true);

				// Importing old data to pt_subscription
				if ($pt_subscription_id == null) {
					$pt_subscription_id = pt_create_subscription();

					$author_id = get_post($first_payment->ID)->post_author;

					$arg = array(
						'ID' => $pt_subscription_id,
						'post_author' => $author_id,
					);
					wp_update_post( $arg );

					// Get all payments by subscription ID (now subscription post ID, BC for Mollie subscription ID)
					$payment_ids = pt_get_all_payments_by_subscription_id($mollie->subscriptionId);
					$payments = array();
					foreach ($payment_ids as $id) {
						$payments[] = (int)$id->post_id;

						pt_update_payment_meta( $id->post_id, array (
							'subscription_id'             => $pt_subscription_id,
							'mollie_subscription_id'     => $mollie->subscriptionId,
						) );

						delete_post_meta($id->post_id, '_subscription');
						delete_post_meta($id->post_id, '_subscription_interval');
						delete_post_meta($id->post_id, '_subscription_times');
						delete_post_meta($id->post_id, '_subscription_payment_status');
						delete_post_meta($id->post_id, '_subscription_status');
						delete_post_meta($id->post_id, '_subscription_start_date');

					}

					pt_update_payment_meta( $pt_subscription_id, array (
						'id'             => $first_payment->subscription_id,
						'subscription_start_date'     => $first_payment->subscription_start_date,
						'subscription_interval'       => $first_payment->subscription_interval,
						'subscription_times'          => $first_payment->subscription_times,
						'subscription_status'         => $result['status'],
						'payments'                    => serialize($payments),
					) );

					if ( isset($first_payment->subscription_first_payment) ) {
						pt_update_payment_meta( $pt_subscription_id, array (
							'subscription_first_payment'     => pt_user_amount_to_float($first_payment->subscription_first_payment),
							'subscription_recurring_payment' => pt_user_amount_to_float($first_payment->subscription_recurring_payment),
						) );
					}
					else {
						pt_update_payment_meta( $pt_subscription_id, array (
							'subscription_recurring_payment' => pt_user_amount_to_float($first_payment->amount),
						) );
					}
				}

				$customer = $pt_mollie->customers->get( $mollie->customerId );

				$author_id = get_post($first_payment->id)->post_author;

				$old_payment_post_id = pt_create_payment( array (
					'post_author'                => $author_id,
					'order_status'                => 'new',
					'amount'                      => $mollie->amount->value,
					'currency'                    => $mollie->amount->currency,
					'status'                      => '',
					'payment_id'                  => $mollie->id,
					'payment_key'                 => null,
					'subscription_id'             => $pt_subscription_id,
					'mollie_subscription_id'      => $mollie->subscriptionId,
					'method'                      => $mollie->method,
					'description'                 => $mollie->description,
					'pt-customer-id'              => $mollie->customerId,
					'pt-customer-email'           => $customer->email,
					'pt-customer-name'            => $customer->name,
					'mode'                        => $mollie->mode,
					'meta'                        => array ()
				) );

				$this_payment = pt_get_payment($old_payment_post_id);
				$this_payment->set_status( $status );

				$subscription_payments = unserialize(get_post_meta((int)$pt_subscription_id, '_payments', true));
				$subscription_payments[] = $old_payment_post_id;

				$update_subscription = array();
				$update_subscription['payments'] = serialize($subscription_payments);

				if ($result['status'] == 'completed') {
					$update_subscription['subscription_status'] = $result['status'];
				}

				// Update the current status of the subscription at Mollie in Paytium
				pt_update_payment_meta( $pt_subscription_id, $update_subscription );

				// Copy post meta details (field information, items etc) to renewal payment
				$meta = get_post_meta( $first_payment->id, null, true );
				$meta = pt_copy_field_data( $meta );

				// Only when there is a first payment, copy to subscription and renewal payment
				if ( get_post_meta( $pt_subscription_id, '_subscription_first_payment', true ) !== '' ) {

					foreach ( $meta as $key => $value ) {

						// Remove old meta information
						if ( preg_match( '/item-\d+-/', $key ) ) {
							unset( $meta[ $key ] );
						}

						if ( preg_match( '/item-recurring-payment-\d+-/', $key ) ) {
							$new_key          = preg_replace( '/-recurring-payment-/', '-', $key );
							$meta[ $new_key ] = $value;
						}

						if ( preg_match( '/_item-recurring-payment-(.*?)-/', $key ) ) {
							unset( $meta[ $key ] );
						}
					}
				}

				if (get_post_meta($pt_subscription_id, '_subscription_updated', true)) {
					$meta['_item-1-amount'] = get_post_meta($pt_subscription_id, '_item-1-amount', true);
					$meta['_item-1-value'] = get_post_meta($pt_subscription_id, '_item-1-value', true);
					$meta['_item-1-tax-amount'] = get_post_meta($pt_subscription_id, '_item-1-tax-amount', true);
					$meta['_item-1-total-amount'] = get_post_meta($pt_subscription_id, '_item-1-total-amount', true);
					$meta['_discount_amount'] = get_post_meta($pt_subscription_id, '_discount_amount', true);
				}

				foreach ( $meta as $meta_key => $meta_value ) {
					update_post_meta( $old_payment_post_id, $meta_key, $meta_value );
					update_post_meta( $pt_subscription_id, $meta_key, $meta_value );
				}

				// David - Paytium 1.4.1
				// Get latest payment details with the updated payment status
				$payment_latest = pt_get_payment_by_payment_id( $mollie->id );
				// Add hook after webhook processing of subscription renewal payments
				do_action( 'paytium_webhook_subscription_renewal_payment', $payment_latest );

				paytium_logger( $mollie->id . ' - ' . 'Webhook: Completed unknown payment processing successfully.',__FILE__,__LINE__ );
				pt_set_http_response_code_and_exit( '200' );
			}

			//
			// END UNKNOWN PAYMENT PROCESSING - maybe it's a renewal payment for a subscription?
			//

			//
			// START REGULAR PAYMENT PROCESSING - Update status and payment method for regular payments.
			//

			$payment->set_status( $status );
			$payment->set_payment_method( $mollie->method );

			paytium_logger( $payment->id.'/'.$mollie->id.': new status - '.$status, __FILE__,__LINE__);
			paytium_logger( $payment->id.'/'.$mollie->id.': ', __FILE__,__LINE__);

			if($payment->status == 'paid' && !$payment->subscription_id) {
				update_user_meta( get_post($payment->id)->post_author, '_last_paid_date', date('Y-m-d H:i:s'));
			}
			//
			// END REGULAR PAYMENT PROCESSING - Update status and payment method for regular payments.
			//

			//
			// START SUBSCRIPTION PROCESSING - Try to process or create a subscription if all conditions are met
			//

			try {
				if ( $payment->subscription_id != '' ) {

					// Charge back renewal payment
					if ( $mollie->subscriptionId != null && $status == 'charged_back' ) {

						paytium_logger( $mollie->id . '/' . $mollie->subscriptionId . ' - ' . 'Webhook: renewal payment charged back',__FILE__,__LINE__ );

						$notification_counter = get_option( 'paytium_notification_counter' ) ? (int) get_option( 'paytium_notification_counter' ) + 1 : 1;

						$payment_link      = '<a href="post.php?post=' . $payment->id . '&action=edit" target="_blank">' . __( 'payment ', 'paytium' ) . $payment->id . '</a>';

						$charge_back_notification = array (
							'id'      => $notification_counter,
							'user_id' => '',
							'status'  => 'open',
							'slug'    => 'chargeback',
							'message' => '[PAYTIUM] A chargeback has been received for subscription renewal payment ' . $payment_link . '. ' .
							             'You might want to investigate this and possibly create a credit invoice.'
						);

						paytium_add_notification( $notification_counter, $charge_back_notification );

						paytium_logger( $mollie->id . '/' . $mollie->subscriptionId . ' - ' . 'Charge back notification added',__FILE__,__LINE__ );
						update_option( 'paytium_notification_counter', $notification_counter );
					}

					// If payment is not paid, do not process subscription,
					// even is there is a valid mandate.
					// That's because Paytium uses the first amount and start date as a workaround
					// to get a quick payment for the first installment of the subscription.
					// So no paid first payment, means no subscription!

					// Check that payment doesn't already belong to a subscription (at Mollie), and is paid
					if ( $mollie->subscriptionId == null && $status == 'paid' ) {

						// Check if the customer has a valid mandate
						$valid_mandate = pt_does_customer_have_valid_mandate( $payment->customer_id );

						// Deviate from Mollie instructions (process mandate with status pending or valid)
						// Because in Paytium, customers always place a first payment, to get a valid mandate
						// Otherwise they don't get a subscription at all.
						// Paytium does not accept pending mandates or subscriptions.
						if ( $valid_mandate == false ) {
							throw new Mollie\Api\Exceptions\ApiException ( 'Mandate was invalid, subscription not created.' );
						}

						paytium_logger( $mollie->id . ' - ' . 'Webhook: subscription processing: valid mandate: ' . $valid_mandate . ', interval: ' .
							$payment->subscription_interval . ', times: ' . $payment->subscription_times,__FILE__,__LINE__ );

						$webhook_url = add_query_arg( 'pt-webhook', 'paytium', home_url( '/' ) );

						$subscription_recurring_payment = get_post_meta( $payment->subscription_id, '_subscription_recurring_payment', true );

						$create_subscription_data = array (
							"amount"        => ['currency' => $payment->currency, 'value' => strval(number_format($subscription_recurring_payment,2))], // mollie api v2
							"interval"    => get_post_meta( $payment->subscription_id, '_subscription_interval', true ),
							"startDate"   => get_post_meta( $payment->subscription_id, '_subscription_start_date', true ),
							"description" => $payment->description,
							"webhookUrl"  => $webhook_url
						);

						if (get_post_meta( $payment->subscription_id, '_subscription_times', true )) {
							$create_subscription_data["times"] = get_post_meta( $payment->subscription_id, '_subscription_times', true );
						}

						// Create the subscription at Mollie
						$subscription = $pt_mollie->subscriptions->createForId( $payment->customer_id, $create_subscription_data );

						paytium_logger( $mollie->id . '/' . $subscription->id . ' - ' . 'Webhook: new subscription created, ID via webhook: ' . $subscription->id,__FILE__,__LINE__ );

						// Set $subscription_webhook to 1 if the webhook is registered at Mollie
						$subscription_webhook = 0;
						if ( $subscription->webhookUrl != null ) {
							$subscription_webhook = 1;
						}

						// Save new subscription details to Payment post meta
						$new_subscription_details = ( array (
							'mollie_subscription_id' => $subscription->id,
							'subscription_webhook'   => $subscription_webhook,
							'subscription_status'    => $subscription->status
						) );
						pt_update_payment_meta( $payment->subscription_id, $new_subscription_details );

						$update_payment_details = ( array (
							'mollie_subscription_id' => $subscription->id,
						) );
						pt_update_payment_meta( $payment->id, $update_payment_details );


						//
						// START - Copy post meta details (field information, items etc) to renewal payment
						//

						$subscription_meta = get_post_meta( $payment->id, null, true );
						$subscription_meta = pt_copy_field_data( $subscription_meta );

						// Only when there is a first payment, copy to new subscription
						if ( get_post_meta( $payment->subscription_id, '_subscription_first_payment', true ) !== '' ) {

							foreach ( $subscription_meta as $key => $value ) {

								// Remove old meta information
								if ( preg_match( '/item-\d+-/', $key ) ) {
									unset( $subscription_meta[ $key ] );
								}

								if ( preg_match( '/item-recurring-payment-\d+-/', $key ) ) {
									$new_key                       = preg_replace( '/-recurring-payment-/', '-', $key );
									$subscription_meta[ $new_key ] = $value;
								}

								if ( preg_match( '/_item-recurring-payment-(.*?)-/', $key ) ) {
									unset( $subscription_meta[ $key ] );
								}
							}
						}

						foreach ( $subscription_meta as $meta_key => $meta_value ) {
							update_post_meta( $payment->subscription_id, $meta_key, $meta_value );
						}

						//
						// END - Copy post meta details (field information, items etc) to renewal payment
						//

						// David - Paytium 1.4.1
						// Get latest payment details with the updated payment status
						$payment_latest = pt_get_payment_by_payment_id( $mollie->id );
						// Add hook after webhook processing of subscription first payment
						do_action( 'paytium_webhook_subscription_first_payment', $payment_latest );


					} elseif ($mollie->subscriptionId == null ) {

						if ( $status == 'cancelled' || $status == 'expired' || $status == 'failed' ) {
							paytium_logger( $mollie->id . '/' . $mollie->subscriptionId . ' - ' . 'Webhook: First payment not paid for (' .
								$mollie->subscriptionId . '), didn\'t create a subscription.',__FILE__,__LINE__ );
							throw new Mollie\Api\Exceptions\ApiException ( 'First payment not paid, didn\'t create a subscription.' );
						}
					}

				}
			}
			catch ( Mollie\Api\Exceptions\ApiException $e ) {

				// Save error for failed subscription to database with other payment details
				$new_subscription_details = ( array (
					'subscription_error' => str_replace( 'Error executing API call (request): ', '', htmlspecialchars( $e->getMessage() )),
					'subscription_status'         => 'cancelled'
				) );
				pt_update_payment_meta( $payment->subscription_id, $new_subscription_details );

				$update_payment_details = ( array (
					'_status' => 'failed',
				) );
				pt_update_payment_meta( $payment->id, $update_payment_details );

				paytium_logger( $payment->id . '/' . $mollie->id . ' - ' . 'Webhook: processing subscription failed: ' . htmlspecialchars( $e->getMessage() ),__FILE__,__LINE__ );
			}

			//
			// END SUBSCRIPTION PROCESSING - Try to create a subscription if all conditions are met
			//

			// David - Paytium 1.4.0
			// Get latest payment details with the updated payment status
			$payment_latest = pt_get_payment_by_payment_id( $mollie->id );

			// Add hook after webhook processing of first payment
			do_action( 'paytium_after_pt_payment_update_webhook', $payment_latest );

			// Finish and tell Mollie it all succeeded.
			pt_set_http_response_code_and_exit( '200' );


		} catch ( Exception $e ) {
			paytium_logger( $mollie_payment_id . ' - Webhook: processing failed: ' . $e->getMessage(),__FILE__,__LINE__ );
			pt_set_http_response_code_and_exit( '400' );
		}

	}

	return $request;

}

add_filter( 'request', 'pt_payment_update_webhook' );

/**
 * In the field data, find the field name and email to use a customer for Mollie Customers API
 *
 * @since 1.2.0
 *
 */
function pt_does_customer_have_valid_mandate( $customer_id ) {

	global $pt_mollie;

	$mandates = (array) $pt_mollie->mandates->listForId( $customer_id );

	$valid_mandate = false;

	foreach ( $mandates as $key => $mandate ) {

		if ( $mandate->status == 'valid' || $mandate->status == 'pending' ) {
			$valid_mandate = true;
			break;

		} else {
			$valid_mandate = false;
		}
	}

	return $valid_mandate;
}

/**
 * Set HTTP status code
 *
 * @param int $status_code
 */
function pt_set_http_response_code_and_exit ($status_code) {
	if (PHP_SAPI !== 'cli' && !headers_sent())
	{
		if (function_exists("http_response_code"))
		{
			http_response_code($status_code);
		}
		else
		{
			header(" ", TRUE, $status_code);
		}
	}
	exit();
}

function pt_get_new_payment_status($mollie) {

	if (!empty($mollie->amountRefunded) && $mollie->amountRefunded->value >= 1) {
		paytium_logger(print_r($mollie->amountRefunded->value,true), __FILE__,__LINE__);
		$status = 'refunded';
	}
	elseif (!empty($mollie->amountChargedBack) && $mollie->amountChargedBack->value >= 1) {
		$status = 'charged_back';
	}
	else $status = $mollie->status;

	return $status;
}