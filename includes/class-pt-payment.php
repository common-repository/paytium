<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Payment class.
 *
 * Payment class is a API for a single payment in Paytium.
 *
 * @class          PT_Payment
 * @version        1.0.0
 * @author         Jeroen Sormani
 */
class PT_Payment {

	/**
	 * @since 1.0.0
	 * @var int Payment
	 */
	public $id;

	/**
	 * @since 1.0.0
	 * @var int Payment amount in cents.
	 */
	public $payment_amount;

	/**
	 * @since 4.3.0
	 * @var string Payment currency.
	 */
	public $currency;

	/**
	 * @since 1.0.0
	 * @var string  Payment status slug.
	 */
	public $status;

	/**
	 * @since 1.0.0
	 * @var string Order status.
	 */
	public $order_status;

	/**
	 * @since 1.0.0
	 * @var string  Payment date.
	 */
	public $payment_date;

	/**
	 * @since 1.0.3
	 * @var string  Mollie transaction ID.
	 */
	public $transaction_id;

	/**
	 * @since 1.0.0
	 * @var string Payment method.
	 */
	public $payment_method;

	/**
	 * @since 1.0.0
	 * @var string  Payment description
	 */
	public $description;

	/**
	 * @since 1.3.0
	 * @var string  Subscription
	 */
	public $subscription;

	/**
	 * @since 1.3.0
	 * @var string  Subscription
	 */
	public $subscription_id;

	/**
	 * @since 2.2.0
	 * @var string  Subscription
	 */
	public $mollie_subscription_id;

	/**
	 * @since 1.3.0
	 * @var string  Subscription interval
	 */
	public $subscription_interval;

	/**
	 * @since 1.3.0
	 * @var string  Subscription times
	 */
	public $subscription_times;

	/**
	 * @since 2.1.0
	 * @var string  Subscription first amount
	 */
	public $subscription_first_payment;

	/**
	 * @since 2.1.0
	 * @var string  Subscription recurring amount
	 */
	public $subscription_recurring_payment;

	/**
	 * @since 1.4.0
	 * @var string  Subscription start date
	 */
	public $subscription_start_date;

	/**
	 * @since 1.4.0
	 * @var string  Subscription payment status
	 */
	public $subscription_payment_status;

	/**
	 * @since 1.4.0
	 * @var string  Subscription webhook
	 */
	public $subscription_webhook;

	/**
	 * @since 1.4.0
	 * @var string  Subscription error
	 */
	public $subscription_error;

	/**
	 * @since 2.0.0
	 * @var string  Subscription status
	 */
	public $subscription_status;

	/**
	 * @since 2.0.0
	 * @var string  Subscription cancelled date
	 */
	public $subscription_cancelled_date;

	/**
	 * @since 1.3.0
	 * @var string  Customer ID
	 */
	public $customer_id;

	/**
	 * @since 1.3.0
	 * @var string  mode
	 */
	public $mode;

	/**
	 * @since 1.5.0
	 * @var string  no_payment
	 */
	public $no_payment;

	/**
	 * @since 1.1.0
	 * @var string  Field data
	 */
	public $field_data = array();


	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id pt_payment Post ID.
	 */
	public function __construct( $post_id ) {

		$this->id = absint( $post_id );

		if ( 'pt_payment' != get_post_type( $this->id ) ) {
			return false;
		}

		$this->populate();
		return null;

	}


	/**
	 * Populate payment.
	 *
	 * Populate the payment class with the related data.
	 *
	 * @since 1.0.0
	 */
	public function populate() {

		$meta = get_post_meta( $this->id, null, true );

		$this->payment_amount = isset( $meta['_amount'] ) ? reset( $meta['_amount'] ) : '';
		$this->currency       = isset( $meta['_currency'] ) ? reset( $meta['_currency'] ) : 'EUR';
		$this->status         = isset( $meta['_status'] ) ? reset( $meta['_status'] ) : '';
		$this->order_status   = isset( $meta['_order_status'] ) ? reset( $meta['_order_status'] ) : '';
		// Mollie transaction ID is called "payment_id" in DB, that's not correct, its the transaction ID
		$this->transaction_id = isset( $meta['_payment_id'] ) ? reset( $meta['_payment_id'] ) : '';
		$this->payment_date   = get_post_field( 'post_date', $this->id );
		$this->payment_method = isset( $meta['_method'] ) ? reset( $meta['_method'] ) : '';
		$this->description    = isset( $meta['_description'] ) ? reset( $meta['_description'] ) : '';

		$this->subscription                   = isset( $meta['_subscription'] ) ? reset( $meta['_subscription'] ) : ''; // here for BC
		$this->subscription_id                = isset( $meta['_subscription_id'] ) ? reset( $meta['_subscription_id'] ) : '';
		$this->mollie_subscription_id         = isset( $meta['_mollie_subscription_id'] ) ? reset( $meta['_mollie_subscription_id'] ) : '';
		if ($this->subscription == '' && $this->subscription_id != '') {

			$this->subscription_interval          = get_post_meta($this->subscription_id, '_subscription_interval', true );
			$this->subscription_times             = get_post_meta($this->subscription_id, '_subscription_times', true ) ? get_post_meta($this->subscription_id, '_subscription_times', true ) : 'Unlimited';
			$this->subscription_first_payment     = get_post_meta($this->subscription_id, '_subscription_first_payment', true );
			$this->subscription_recurring_payment = get_post_meta($this->subscription_id, '_subscription_recurring_payment', true );
			$this->subscription_start_date        = get_post_meta($this->subscription_id, '_subscription_start_date', true );
			$this->subscription_status            = get_post_meta($this->subscription_id, '_subscription_status', true );
			$this->subscription_cancelled_date    = get_post_meta($this->subscription_id, '_subscription_cancelled_date', true );
			$this->subscription_webhook           = get_post_meta($this->subscription_id, '_subscription_webhook', true );
			$this->subscription_error             = get_post_meta($this->subscription_id, '_subscription_error', true );
			$payments = unserialize(get_post_meta($this->subscription_id, '_payments', true));
			if ($payments && in_array($this->id, $payments)) {
				$this->subscription_payment_status    = $payments[0] == $this->id ? 'initial' : 'renewal';
			}
		}
		else {
			$this->subscription_interval          = isset( $meta['_subscription_interval'] ) ? reset( $meta['_subscription_interval'] ) : '';
			$this->subscription_times             = isset( $meta['_subscription_times'] ) ? reset( $meta['_subscription_times'] ) : 'Unlimited';
			$this->subscription_first_payment     = isset( $meta['_subscription_first_payment'] ) ? reset( $meta['_subscription_first_payment'] ) : '';
			$this->subscription_recurring_payment = isset( $meta['_subscription_recurring_payment'] ) ? reset( $meta['_subscription_recurring_payment'] ) : '';
			$this->subscription_start_date        = isset( $meta['_subscription_start_date'] ) ? reset( $meta['_subscription_start_date'] ) : '';
			$this->subscription_payment_status    = isset( $meta['_subscription_payment_status'] ) ? reset( $meta['_subscription_payment_status'] ) : '';
			$this->subscription_status            = isset( $meta['_subscription_status'] ) ? reset( $meta['_subscription_status'] ) : '';
			$this->subscription_cancelled_date    = isset( $meta['_subscription_cancelled_date'] ) ? reset( $meta['_subscription_cancelled_date'] ) : '';
			$this->subscription_webhook           = isset( $meta['_subscription_webhook'] ) ? reset( $meta['_subscription_webhook'] ) : '';
			$this->subscription_error             = isset( $meta['_subscription_error'] ) ? reset( $meta['_subscription_error'] ) : '';
		}

		// Fix for Paytium 3.0.0-3.0.12, incorrect subscription id stored with renewal payments
		$this->update_subscription_id_for_renewal_payments();

		// Fix for Paytium 3.0.0-3.0.12, item information was not copied from first payments to renewal payments
        $this->copy_first_payment_items_to_renewal_payment( $meta );

		// In Paytium 3.2.0 the format of subscriptions with a first payment is changed, this function makes that
        // backwards compatible for existing subscriptions and their payments
		$this->update_subscription_and_payments_to_new_format( $meta );

		$this->customer_id = isset( $meta['_pt-customer-id'] ) ? reset( $meta['_pt-customer-id'] ) : '';
		$this->mode        = isset( $meta['_mode'] ) ? reset( $meta['_mode'] ) : '';

		$this->no_payment = isset( $meta['_pt_no_payment'] ) ? reset( $meta['_pt_no_payment'] ) : '';

		$this->field_data = $meta;

	}


	/**
	 * Get the payment amount.
	 *
	 * Get the payment amount in a nice format with decimals, without currency symbol.
	 *
	 * @since 1.0.0
	 *
	 * @return float Payment amount.
	 */
	public function get_amount() {
		return $this->payment_amount;
	}


	/**
	 * Get tax total.
	 *
	 * @since 1.5.0
	 *
	 * @param bool $without_discount
	 * @return float
	 */
	public function get_tax_total($without_discount = false) {
		$tax_total = 0;

		foreach ( $this->get_items() as $key => $item ) {
			$tax_total += $item->get_tax_amount($key+1);
		}

		if ($this->is_discount() && !$without_discount) {
			return $this->discount_tax_calculate($this->get_tax_total(true))['tax_total'];
		}

		return $tax_total;
	}

	/**
	 * Get tax total.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function get_taxes_per_percentage() {
		$taxes = array ();

		foreach ( $this->get_items() as $item ) {
			if ( ! isset( $taxes[ $item->get_tax_percentage() ] ) ) {
				$taxes[ $item->get_tax_percentage() ] = 0;
			}
			$taxes[ $item->get_tax_percentage() ] += $this->is_discount() ? $item->get_tax_amount() - $this->discount_tax_calculate($item->get_tax_amount())['tax_discount'] : $item->get_tax_amount();
		}

		return $taxes;
	}


	/**
	 * Get total amount.
	 *
	 * @return int|mixed
	 * @since 1.5.0
	 *
	 */
	public function get_total() {
		$total = 0;

		foreach ( $this->get_items() as $item ) {
			$total += $item->get_total_amount();
		}

		if ($this->is_discount()) {
		    $total = $total - $this->get_discount_amount();
        }

		return $total;
	}


	/**
	 * Get purchased items.
	 *
	 * Get the items the customer has purchased. Returns PT_Item objects.
	 *
	 * @since 1.5.0
	 *
	 * @return PT_Item[] List of items purchased.
	 */
	public function get_items() {

		$items = array ();

		$i = 1;
		while ( isset( $this->field_data[ '_item-' . $i . '-amount' ] ) ) {
			$item = new PT_Item( $this, $i );
			if ($item->get_amount() != 0 || $item->get_type() == 'multiplier' || $item->get_tax_amount() != 0) {
				$items[] = $item;
            }
			$i++;
		}

		// BC for the old format. Jeroen, 2017
		if ( empty( $items ) ) {
			$item = new PT_Item( $this, 0 );

			if ( $this->id != 0 ) {
				$items[] = $item
					->set_label( $this->field_data['_description'][0] )
					->set_amount( $this->field_data['_amount'][0] )
					->set_tax_percentage( null )
					->set_tax_amount( null )
					->set_total_amount( $this->field_data['_amount'][0] );
			}
		}

		return $items;

	}

	/**
	 * Get purchased items.
	 *
	 * Get the items the customer has purchased. Returns PT_Item objects.
	 *
	 * @return PT_Item[] List of items purchased.
	 * @since 1.5.0
	 *
	 */
	public function get_subscription_items() {

		$items = array ();

		$i = 1;
		foreach ( $this->field_data as $key => $value ) {

			if ( preg_match( '/_item-recurring-payment-(.*?)-/', $key, $match ) == 1 ) {
				$item_key = $match[1];
			}

			if ( strpos( $key, '_item-recurring-payment' ) !== false ) {
				$items[ $item_key ][ $key ] = $value[0];
				$i ++;
			}
		}

		return $items;

	}


	/**
	 * Get payment status.
	 *
	 * Get the pretty payment status name.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed
	 */
	public function get_status() {

		$statuses = pt_get_payment_statuses();
		$status   = isset( $statuses[ $this->status ] ) ? $statuses[ $this->status ] : $this->status;

		return apply_filters( 'pt_payment_get_status', $status, $this->id );

	}


	/**
	 * Set payment status.
	 *
	 * Set the payment status and update the DB value.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $status New payment status slug.
	 *
	 * @return bool|string         False when the new status is invalid, the new status otherwise.
	 */
	public function set_status( $status ) {

		$status = $status == 'canceled' ? 'cancelled' : $status;
		$old_status = $this->get_status();
		$statuses = pt_get_payment_statuses();

		if ( isset( $statuses[ $status ] ) ) {
			$this->status = $status;
		} else {
			return false;
		}

		update_post_meta( $this->id, '_status', $this->status );

		do_action( 'pt_payment_after_set_status', $old_status, $status, $this );

		// Add a filter here to allow developers to process payment status update as well
		do_action( 'paytium_update_payment_status_from_admin', $this->id );

		return $this->get_status();

	}

	/**
	 * Hook to be called when payment is updated from WordPress admin
	 *
	 * @since 1.4.0
	 */
	public function update_status_from_admin( $payment_id ) {

		// Add a filter here to allow developers to process payment changes from admin as well
		do_action( 'paytium_after_update_payment_from_admin', $payment_id );

		return;

	}


	/**
	 * Get order status.
	 *
	 * Get the order status.
	 *
	 * @since 1.0.0
	 *
	 * @return string Order status.
	 */
	public function get_order_status() {

		$statuses = pt_get_order_statuses();
		$status   = isset( $statuses[ $this->order_status ] ) ? $statuses[ $this->order_status ] : $this->order_status;

		return apply_filters( 'pt_payment_get_order_status', $status, $this->id );

	}


	/**
	 * Set order status.
	 *
	 * Set the order status.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $status New order status.
	 *
	 * @return string         New order status
	 */
	public function set_order_status( $status ) {

		$statuses = pt_get_order_statuses();

		if ( isset( $statuses[ $status ] ) ) {
			$this->order_status = $status;
		} else {
			return false;
		}

		update_post_meta( $this->id, '_order_status', $this->order_status );

		return $this->get_order_status();

	}


	/**
	 * Get payment date.
	 *
	 * Get the formatted payment date.
	 *
	 * @since 1.0.0
	 *
	 * @return string Formatted payment date.
	 */
	public function get_payment_date() {

		$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$date_format = apply_filters( 'pt_payment_date_format', $date_format, $this->id );

		return date_i18n( $date_format, strtotime( $this->payment_date ) );

	}

	/**
	 * Get payment id (Mollie transaction ID).
	 *
	 * Get the Mollie transaction ID.
	 *
	 * @since 1.0.3
	 *
	 * @return string Mollie transaction ID.
	 */
	public function get_transaction_id() {

		$this->transaction_id = ! empty( $this->transaction_id ) ? $this->transaction_id : '-';

		return $this->transaction_id;

	}


	/**
	 * Get payment method.
	 *
	 * Get the used payment method for this payment.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_payment_method() {
		return $this->payment_method;
	}


	/**
	 * Set payment method.
	 *
	 * Set the used method for the payment
	 *
	 * @since 1.0.0
	 *
	 * @param  string $payment_method Used payment method.
	 *
	 * @return string                 Used payment method.
	 */
	public function set_payment_method( $payment_method ) {

		$payment_methods = pt_get_payment_methods();
		if ( isset( $payment_methods[ $payment_method ] ) ) {
			$this->payment_method = $payment_method;
		} else {
			return false;
		}

		update_post_meta( $this->id, '_method', $payment_method );

		return $this->get_payment_method();

	}


	/**
	 * Get description.
	 *
	 * Get the description of the payment. This should be something related to the product title for example.
	 *
	 * @since 1.0.0
	 *
	 * @return string Payment description.
	 */
	public function get_description() {

		return apply_filters( 'pt_payment_get_description', $this->description, $this->id );

	}

	/**
	 * Get subscription first payment
	 * @since 2.1.0
	 * @return string Subscription first payment.
	 */
	public function get_subscription_first_payment() {

		return $this->subscription_first_payment;

	}

	/**
	 * Get subscription recurring payment
	 * @since 2.1.0
	 * @return string Subscription recurring payment.
	 */
	public function get_subscription_recurring_payment() {

		return $this->subscription_recurring_payment;

	}

	/**
	 * Get all field data from fields in html format
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public function get_field_data_html() {

		$html = '';

		$field_data = array ();

		foreach ( (array) $this->field_data as $key => $value ) {
			if ( strpos( $key, '_item' ) !== false ) continue; // Skip items

			// Add fields to custom data
			// Note: Every field has only one label, but two postmeta items in DB
			if ( strstr( $key, '-label' ) ) {
				// Update key/label for fields with user defined label
				$field_key               = ucfirst( str_replace( '-label', '', $key ) );
				$field_data[] = isset( $this->field_data[ $field_key ] ) ? array('label' => $value[0], 'value' => $this->field_data[ $field_key ]) : array();
			}

			// Add customer details fields to custom data
			// If I merge customer details with fields, this can be removed (no users have this live?)
			if ( strstr( $key, 'pt-customer-details-' ) ) {
				$customer_details_key                = ucfirst( str_replace( 'housenumber', 'House number', str_replace( 'pt-customer-details-', '', str_replace( '_', '', $key ) ) ) );
				$field_data[] = array('label' =>  $customer_details_key, 'value' => $value);
			}

			// Add customer details fields to custom data
			// Old format until June 2016
			if ( strstr( $key, '_customer_details_' ) ) {
				$customer_details_key                = ucfirst( str_replace( 'housenumber', 'House number', str_replace( '_customer_details_', '', str_replace( '_customer_details_fields_', '', $key ) ) ) );
				$field_data[] = array('label' =>  $customer_details_key, 'value' => $value);
			}

		}

		foreach ( (array) $field_data as $row ) {

			ob_start();

			if (isset($row['label'],$row['value'])) :
			?><div class='option-group'>

				<label for='claimer'><?php _e( $row['label'], 'paytium' ); ?>:</label>
				<span class="option-value"><?php echo isset($row['value']) && is_array($row['value']) ? esc_html(reset( $row['value'] )) : '' ?></span>

			</div><?php
            endif;

			$html .= ob_get_contents();
			ob_end_clean();
		}

		return $html;

	}

	/**
	 * Get all field data from fields in raw format
	 *
	 * @since 1.4.0
	 *
	 * @return array
	 */

	public function get_field_data_raw() {

		$field_data = array ();

		// Add the post/payment ID to the payment data array
		$field_data['payment-id'] = array ( $this->id );

		// Add the post/payment date to the payment data array
		$field_data['payment-date'] = array ( date_i18n( get_option( 'date_format' ), strtotime( $this->payment_date ) ) );

		foreach ( (array) $this->field_data as $key => $value ) {

		    $value[0] = esc_html($value[0]);

			// Remove prefixing underscores
			$clean_key = ltrim( $key, '_' );

			//
			// Add custom tags - create a description without the payment ID at the end
			//
			if ( $key === '_description' ) {

				$field_data['description-without-id'][] = preg_replace( '/ [0-9]*$/', '', $value[0] );

			}

			//
			// Add invoice key tag
			//
			if (  $key === '_invoice_key' ) {
				$field_data[ 'invoice-link' ] = array(get_site_url() . '?paytium-invoice=' . $value[0] );
			}

			//
			// Rename a few elements so they are more logical or users
			//
			if ( $key === '_status' ) {
				$clean_key                = 'payment-status';
				$this->field_data[ $key ] = array ( strtolower( $this->get_status() ) );
			}

			//
			// Add percentage symbol to tax percentages
			//
			if ( strpos($key, '-percentage') ) {
				$this->field_data[ $key ][0] = $value[0] . '%';
			}

			//
			// Convert format for subscription amounts
			//
			if ( $key === '_subscription_first_payment' ) {
				$this->field_data[ $key ][0] = pt_float_amount_to_currency( $value[0], $this->currency );
			}
			if ( $key === '_subscription_recurring_payment' ) {
				$this->field_data[ $key ][0] = pt_float_amount_to_currency( $value[0], $this->currency );
			}

			// Convert these clean keys (key is the existing, value being the cleaned version)
			$clean_keys = array(
				'_payment_id' => 'transaction-id',
				'_mode' => 'payment-mode',
				'_amount' => 'payment-amount',
				'_method' => 'payment-method',
				'_pt-field-amount' => 'payment-options-selected',
				'_pt-field-amount-label' => 'payment-options-label',
			);
			foreach ( $clean_keys as $k => $v ) {
				if ( $key === $k ) {
					$clean_key = $v;
				}
			}

			// Remove these elements, not needed for users/in emails
			$exclude_meta = array(
				'_is_admin_test',
				'_edit_lock',
				'_edit_last',
				'_invoice_key',
				'_payment_key',
				'_paytium_emails_last_status',
				'mode',
				'_subscription_webhook',
				'_pt_emails_last_status',
				'_subscription_payment_status', // TODO: Make pretty version of this status/value and show in emails
				'_subscription_error', // TODO: show this if value is not empty (so actually error found)?
				'_pt-field-item',
				'_pt_no_emails',
				'_pt_emails_last_status',
				'_pt_emails_last_order_status',
			);
			if ( in_array( $key, $exclude_meta ) ) {
				continue;
			}

			// Exclude keys that have one of these partial strings
			$exclude_partials = array(
				'pt_cf_',
				'pt-field-edd',
				'_pt_mailchimp_error',
				'_pt-field-mailchimp-',
				'_pt-field-mailpoet-',
				'_pt-field-activecampaign-',
				'_pt-field-item',
				'_pt_no_emails',
				'_pt_emails_last_status',
				'_pt_emails_last_order_status',
				'_pt_no_payment',
				'_pt-field-form-emails',
				'_item-recurring-payment',
			);
			$continue = false;
			foreach ( $exclude_partials as $v ) {
				if ( strpos( $key, $v ) !== false ) {
					$continue = true;
				}
			}
			if ( $continue ) continue;

			// TODO: Add subscription_renewal which should convert to Yes/No
            // TODO: Translate payment_status tag to pretty/translated names?

			//
			// Add all data to the field data array
			//
			$field_data[ str_replace( '_', '-', $clean_key  ) ] = $this->field_data[ $key ];

		}

		// Add additional tags
		$tax_total_split = '';
		foreach ( $this->get_taxes_per_percentage() as $percentage => $amount ) {
			if ( ! empty( $tax_total_split ) ) {
				$tax_total_split .= '<br />';
			}

			$tax_total_split .= pt_float_amount_to_currency( esc_html( $amount ), $this->currency ) . ' <small class="muted">(' . floatval( esc_html( $percentage ) ) . '%)</small>';
		}

		ob_start();
		$payment = $this;
		require ( PT_PATH . 'admin/views/meta-boxes/payment-items.php' );
		$items_table = ob_get_clean();

		$items_table = apply_filters( 'paytium_items_table_emails_invoices', $items_table );

		$payment_data = array(
			'items-table' => array( $items_table ),
			'extended-items-table' => array( $items_table ),
			'total' => array( pt_float_amount_to_currency( $this->get_total(), $this->currency ) ),
			'total-excl-tax' => array( pt_float_amount_to_currency( $this->get_total() - $this->get_tax_total(), $this->currency ) ),
			'tax-total' => array( pt_float_amount_to_currency( $this->get_tax_total(), $this->currency ) ),
			'tax-total-split' => array( $tax_total_split ),
		);

		//
		// Convert item amounts to correct currency format (can't do this earlier)
		//
		foreach ( (array) $this->field_data as $key => $value ) {

			if ( strpos( $key, 'amount' ) ) {
				$this->field_data[ $key ][0] = pt_float_amount_to_currency( $value[0], $this->currency );
			}
		}

		$field_data = $field_data + $payment_data;

        // Check if it's subscription renewal payment and copy custom data from initial payment
		if ($this->subscription_payment_status == 'renewal') {
			$first_payment_id = pt_get_first_payment_id($this->subscription_id);

			$first_payment_meta = get_post_meta($first_payment_id);

			foreach ($first_payment_meta as $key => $value) {
				$clean_key = ltrim( $key, '_' );
			    if (strstr( $key, '_pt-field-' ) || $key == '_source_link' || $key == '_source_id') {

			        if ($key == '_source_link' || $key == '_source_id') {
						$clean_key = str_replace( "_", "-", $clean_key);
                    }
					$field_data[$clean_key] = $value;
                }
            }
        }

		foreach ( $field_data as $key => $value ) {

			if ( strpos( $key, 'items-table' ) === false && strpos( $key, 'tax-total-split' ) === false ) {
				$field_data[ $key ][0] = esc_html( $value[0] );
			}

		}

		return $field_data;

	}

	/**
	 * Get all customer emails
	 *
	 * @since 1.4.0
	 *
	 * @return array
	 */

	public function get_field_data_customer_emails() {

		$field_data = array ();
		foreach ( (array) $this->field_data as $key => $value ) {
			if ( (strpos( $key, '-label' ) === false && strstr( $key, 'pt-field-email' ) )
                 || ( strstr( $key, 'pt-customer-email' ) ) ) {
				$field_data[] = $value[0];
			}
		}

		return $field_data;

	}

	/**
	 * Get payment form emails (specific emails that should be sent for specific form)
	 *
	 * @since 1.6.0
	 *
	 * @return array
	 */

	public function get_payment_form_emails() {

		$form_emails = array ();

		foreach ( (array) $this->field_data as $key => $value ) {

			if ( strstr( $key, '-label' ) ) {
				continue;
			}

			if ( strstr( $key, 'pt-field-form-emails' ) ) {
				$form_emails = explode( ',', $value[0] );
			}

		}

		return $form_emails;

	}


	/**************************************************************
	 * Get different custom field data
	 *************************************************************/

	/**
	 * Get custom field data.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function get_custom_field_data() {

		$custom_field_data = array();
		foreach ( (array) $this->field_data as $k => $v ) {
			if ( strpos( $k, '-label' ) !== false ) continue;
			if ( strpos( $k, '_pt-field' ) !== 0 ) continue;
			if ( strpos( $k, '_pt-field-mailchimp' ) !== false ) continue;
			if ( strpos( $k, '_pt-field-mailpoet' ) !== false ) continue;
			if ( strpos( $k, '_pt-field-activecampaign' ) !== false ) continue;
			if ( strpos( $k, '_pt-field-amount' ) !== false ) continue;
			if ( strpos( $k, '_pt-field-item' ) !== false ) continue;
			if ( strpos( $k, '_pt_no_emails' ) !== false ) continue;
			if ( strpos( $k, '_pt_emails_last_status' ) !== false ) continue;
			if ( strpos( $k, '_pt_emails_last_order_status' ) !== false ) continue;
			if ( strpos( $k, '-user-data' ) !== false ) continue;

			$key = ltrim( $k, '_' );
			$label = isset( $this->field_data[ $k . '-label' ] ) ? $this->field_data[ $k . '-label' ] : array();
			$custom_field_data[ $key ] = array(
				'label' => reset( $label ),
				'value' => reset( $v ),
			);

		}

		return $custom_field_data;

	}

	/**
	 * Get item data.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function get_item_data() {

		$item_data = array();
		foreach ( (array) $this->field_data as $k => $v ) {
			if ( strpos( $k, '_item' ) !== 0 ) continue;
			if ( strpos( $k, '-type' ) == TRUE ) continue;

			// Remove prefixing underscores
			$clean_key = ltrim( $k, '_' );

			// Remove item-*- prefix
			if (preg_match( '/item-\d+-/', $clean_key )) {
			    $label = preg_replace( '/item-\d+-/', '', $clean_key );
			}

			// Replace item-*- prefix with recurring-payment-
			if (preg_match( '/item-recurring-payment-\d+-/', $clean_key )){
				$label = preg_replace( '/item-recurring-payment-\d+-/', 'recurring-payment-', $clean_key );
            }

			$value = reset( $v );

			$item_data[ $clean_key ] = array(
				'label' => $label,
				'value' => $value,
			);

		}

		return $item_data;

	}

	/**
     * Get item field data.
     *
	 * @param string $data_type
	 *
	 * @return array
	 */
	public function get_item_field_data( $data_type = '' ) {

		$item_data = array();
		foreach ( (array) $this->field_data as $k => $v ) {

			if ( strpos( $k, '_item' ) !== 0 ) continue;

			// Remove prefixing underscores
			$clean_key = ltrim( $k, '_' );

			$item_num = explode('-',$clean_key)[1];

			if ( strstr( $k, '-label' ) ) {

				$label = preg_replace( '/item-\d+-/', '', $clean_key );
				$value = reset( $v );

				if (isset($this->field_data['_item-'.$item_num.'-value'])) {
					$label = $value;
					$value = reset($this->field_data['_item-'.$item_num.'-value']);
				}

				// When there is a quantity, value is empty, and we need to use quantity in the CSV export
				if ($data_type == 'items' && isset($this->field_data['_item-'.$item_num.'-quantity'])) {
					if ( isset( $this->field_data['_item-' . $item_num . '-type'] ) && $this->field_data['_item-' . $item_num . '-type'][0] == 'open'  ) {
						$label = $value;
						$value = reset( $this->field_data[ '_item-' . $item_num . '-total-amount' ] );
					} elseif (!isset( $this->field_data['_item-' . $item_num . '-type'] ) || !in_array($this->field_data['_item-' . $item_num . '-type'][0],['radio','checkbox','select','dropdown'])) {
					    $label = $value;
					    $value = reset( $this->field_data[ '_item-' . $item_num . '-quantity' ] );
					}
				}

				$item_data[ $clean_key ] = array(
					'label' => $label,
					'value' => $value,
				);

				if (isset($this->field_data['_item-'.$item_num.'-quantity']) &&
                    (isset( $this->field_data['_item-' . $item_num . '-type'] ) && in_array($this->field_data['_item-' . $item_num . '-type'][0],['radio','checkbox','select','dropdown']))) {
					$clean_key = $clean_key . '-quantity';
					$label = $label . ' quantity';
					$value = reset( $this->field_data[ '_item-' . $item_num . '-quantity' ] );
					$item_data[ $clean_key ] = array(
						'label' => $label,
						'value' => $value,
					);
				}
			}

		}

		return $item_data;

	}

	/**
	 * Fix for Paytium 3.0.0-3.0.12
	 * Item information was not copied from first payments to renewal payments
     *
     * @since 3.0.13
	 */
	public function copy_first_payment_items_to_renewal_payment( $meta ) {

		if ( $this->subscription_payment_status != 'renewal' ) {
			return;
		}

		// Check that renewal payment doesnt already have item information
		foreach ( $meta as $key => $value ) {
			if ( strstr( $key, 'item' ) ) {
			    return;
			}
		}

		$first_payment_id = pt_get_payment_id_by_subscription_id( $this->mollie_subscription_id );

		if ( $first_payment_id == null ) {
			return;
		}

		// Copy post meta details (field information, items etc) to renewal payment
		$first_meta = get_post_meta( $first_payment_id, null, true );
		$first_meta = pt_copy_field_data( $first_meta );

		foreach ( $first_meta as $meta_key => $meta_value ) {
			update_post_meta( $this->id, $meta_key, $meta_value );
		}

		return;

	}

	/**
	 * Fix for Paytium 3.0.0-3.0.12
	 * Incorrect subscription id stored with renewal payment, make a correction
	 *
	 * @since 3.0.13
	 */
	public function update_subscription_id_for_renewal_payments() {

		if ( $this->subscription_payment_status != 'renewal' ) {
			return;
		}

		$pt_subscription_id = pt_get_subscription_id_by_mollie_subscription_id( $this->mollie_subscription_id );

		if ( $this->subscription_id == null || ( (int) $this->subscription_id !== (int) $pt_subscription_id ) ) {

			pt_update_payment_meta( $this->id, array (
				'subscription_id' => $pt_subscription_id,
			) );

			$payments   = unserialize( get_post_meta( $pt_subscription_id, '_payments', true ) );
			$payments[] = (int) $this->id;
			asort( $payments );

			pt_update_payment_meta( $pt_subscription_id, array (
				'payments' => serialize( $payments ),
			) );

			return;

		}
		return;

	}

	/**
	 * In Paytium 3.2.0 the format of subscriptions with a first payment is changed,
	 * this function makes that backwards compatible for existing subscriptions and their payments
	 *
	 * @since 3.2.0
	 */
	public function update_subscription_and_payments_to_new_format( $meta ) {

		// Check that payment/subscription should be updated or not
		$pt_subscription_id = $this->subscription_id;
		$payments           = unserialize( get_post_meta( $pt_subscription_id, '_payments', true ) );
		$first_payment_id   = is_array($payments) ? $payments[0] : $payments;

		if ( $this->status == 'open' && $this->subscription_payment_status != 'renewal' ) {
			return;
		}

		// If this subscription already has item information, stop processing
		if ( get_post_meta( $pt_subscription_id, '_item-1-amount', true ) !== '' ) {
			return;
		}

		// Copy post meta details (field information, items etc) to subscription
		$first_payment_meta = get_post_meta( $first_payment_id, null, true );
		$first_payment_meta = pt_copy_field_data( $first_payment_meta );

		$query_parameters = is_file( PT_PATH . 'features/query-parameters.php' );

		foreach ( $first_payment_meta as $meta_key => $meta_value ) {
			if ( strstr( $meta_key, 'pt-field' ) || strstr( $meta_key, 'source_' ) || strstr( $meta_key, '_item-' )
                || ($query_parameters && strstr( $meta_key, '_get-param-' )) ) {

				update_post_meta( $pt_subscription_id, $meta_key, $meta_value );
			}
		}


		if ( (get_post_meta( $pt_subscription_id, '_subscription_first_payment', true ) === '' ) &&
		     ( get_post_meta( $first_payment_id, '_item-recurring-payment-1-amount', true ) !== '' )) {

			// Start converting item information to new format
			foreach ( $meta as $key => $value ) {

				if ( preg_match( '/item-\d+-/', $key ) ) {

					// Remove old recurring item information from first payment
					if ( preg_match( '/item-\d+-/', $key ) ) {
						delete_post_meta( $first_payment_id, $key, $value[0] );
					}

					// Rename _item-* fields to _item-recurring-* and add to the first payment
					$new_key          = preg_replace( '/item-/', 'item-recurring-payment-', $key );
					$meta[ $new_key ] = $value;
					update_post_meta( $first_payment_id, $new_key, $value[0] );
				}
			}

			// Copy the _subscription_first_payment and _subscription_recurring_payment to the subscription payments
			$subscription_first_payment     = get_post_meta( $pt_subscription_id, '_subscription_first_payment', true );
			$subscription_recurring_payment = get_post_meta( $pt_subscription_id, '_subscription_recurring_payment', true );

			if (!empty($payments)) {
				foreach ( $payments as $payment_id ) {
					update_post_meta( $payment_id, '_subscription_first_payment', $subscription_first_payment );
					update_post_meta( $payment_id, '_subscription_recurring_payment', $subscription_recurring_payment );
				}
            }
		}

		return;

	}

	/**
     * Backward compatibility for field data
     *
	 * @return array
	 */
	public function get_customer_details_field_data() {

		$custom_field_data = array();
		foreach ( (array) $this->field_data as $k => $v ) {
			if ( strpos( $k, '_pt-customer-details-' ) !== 0 ) continue;

			$key = str_replace( '_pt-customer-details-', '', $k );
			$custom_field_data[ $key ] = array(
				'label' => $key,
				'value' => $v[0],
			);

		}

		return $custom_field_data;

	}

	/**
	 * Is discount applied?
	 */
	public function is_discount() { // discount feature

        $no_discount = ($this->subscription_payment_status == 'initial' && get_post_meta($this->id,'_discount_exclude_first_payment',true)) ||
            ($this->subscription_payment_status == 'renewal' && get_post_meta($this->id,'_discount_first_payment',true));

		return get_post_meta($this->id, '_discount_amount',true) && !$no_discount ? true : false;
	}

	/**
	 * Get discount amount
	 */
	public function get_discount_code() {
		return get_post_meta($this->id, '_discount_code',true);
	}

	/**
	 * Get discount amount
	 */
	public function get_discount_amount() {
		return pt_user_amount_to_float(get_post_meta($this->id, '_discount_amount',true));
	}

	/**
	 * Get discount value
	 */
	public function get_discount_value() {
		return get_post_meta($this->id, '_discount_value',true);
	}

	/**
	 * Get zero tax
	 */
	public function get_zero_tax() {
		return get_post_meta($this->id, '_zero_tax',true);
	}

	public function discount_tax_calculate($tax_amount) {

	    $zero_tax = $this->get_zero_tax() ? $this->get_zero_tax() : 0;
	    $discount_tax['tax_discount'] = pt_user_amount_to_float(($tax_amount - $zero_tax) * ($this->get_discount_amount() / ($this->get_total() + $this->get_discount_amount() - $zero_tax)));
	    $discount_tax['tax_total'] = pt_user_amount_to_float($this->get_tax_total(true) - $discount_tax['tax_discount']);
	    return $discount_tax;
    }

}
