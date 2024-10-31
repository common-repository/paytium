<?php
/**
 * Plugin shortcode functions
 *
 * @package   PT
 * @author    David de Boer <david@davdeb.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $counter, $ptfg_counter,$pt_id, $limit_data, $until_data, $collected_amounts, $collected_fields, $collected_discounts, $form_currency;

$counter             = 1;
$ptfg_counter        = 0;
$pt_id               = 0; // Variable to hold each form's data-pt-id attribute.
$limit_data          = array ();
$until_data          = array ();
$collected_amounts   = array ();
$collected_fields    = array ();
$collected_discounts = array ();
$form_currency		 = array ();


/**
 * Check that public.js is really loaded and otherwise add notification for admin
 */
function paytium_check_javascript_loaded() {

	global $plugin_slug;
	if( !  wp_script_is( $plugin_slug.'-public', 'done' ) ){

		if ( ! paytium_check_notifications( 'javascript')) {
			$notification_counter = get_option( 'paytium_notification_counter' ) ? (int) get_option( 'paytium_notification_counter' ) + 1 : 1;

			$notification = array (
				'id'      => $notification_counter,
				'user_id' => '',
				'status'  => 'open',
				'slug'    => 'javascript',
				'message' => sprintf( __( '[PAYTIUM] Paytium has detected that a required JavaScript file isn\'t always loaded when your users view a form. ' .
				                          'Please view %sJavaScript problemen en/of extra velden worden niet opgeslagen%s to fix this and otherwise ' .
				                          'contact support@paytium.nl for assistance.' ), '<a href="https://www.paytium.nl/handleiding/veelgestelde-vragen/" target="_blank">', '</a>' )
			);

			paytium_add_notification( $notification_counter, $notification );
			update_option( 'paytium_notification_counter', $notification_counter );

		}
	}

}

/**
 * Function to process the [paytium] shortcode
 *
 * @since   1.0.0
 */
function pt_paytium_shortcode( $attr, $content = null ) {

	global $pt_script_options, $limit_data, $until_data, $counter, $pt_id, $form_currency;

	add_action( 'wp_footer', 'paytium_check_javascript_loaded', '999' );

	// Variable to hold each form's data-pt-id attribute.
//	static $pt_id = 0;

	// Increment variable for each iteration.
	$pt_id ++;

	// Determine if message after payment should be shown
	if ( isset( $_GET['pt-payment'] ) ) {
		$attr = pt_normalize_empty_atts($attr);
		if(isset($attr['full_page_return_message'])){
			add_filter( 'the_content', 'pt_show_payment_details', 99);
			return get_the_content();
		}
		else {

			// Check that current page/post is not equal to source page/post,
			// if it isn't - don't replace payment form with message after payment.
			// This allows users to show a new form after a payment redirect.

			// Get pt-payment ID and payment details
			$payment_id = sanitize_key( $_GET['pt-payment'] );
			$payment    = pt_get_payment_by_payment_key( $payment_id );

			// Get current and source ID's
			$current_id = get_the_ID();
			$source_id  = $payment->field_data['_source_id'][0];


			// Check that current page/post is not equal to source page/post
			if ( $current_id !== false && ! empty( $source_id ) ) {

				if ( $current_id == $source_id ) {

					return pt_show_payment_details($content);
				}
			}
		}

	}

	if (!empty($attr)) {
		foreach ($attr as $key => $attribute) {
			$attr[$key] = esc_attr($attribute);
		}
	}

	extract( shortcode_atts( array (
		'name'                      => get_option( 'paytium_name', get_bloginfo( 'title' ) ),
		'description'               => '',
		'amount'                    => '',
		'image_url'                 => get_option( 'paytium_image_url', '' ),
		'customer_details'          => ( ! empty( $pt_options['customer_details'] ) ? 'true' : 'false' ),
		// true or false
		'button_label'              => get_option( 'button_label', __( 'Pay', 'paytium' ) ),
		'button_style'              => get_option( 'button_style', '' ),
		'pt_redirect_url'           => get_option( 'pt_redirect_url', get_permalink() ),
		'prefill_email'             => 'false',
		'id'                        => null,
		'tax'                       => '',
        'limit'                     => '',
        'limit_message'             => '',
        'until'                     => '',
        'until_message'             => __('Payments are no longer possible.','paytium'),
        'item_id'                   => '',
		'crowdfunding_id'			=> '',
		'login'                     => '',
		'currency'					=> '',
	), $attr, 'paytium' ) );

	$currency = is_file( PT_PATH . 'features/currency.php' ) ? ($currency  ? $currency : get_option('paytium_currency', 'EUR')) : 'EUR';
	$form_currency[$pt_id] = $currency;

	// Generate custom form id attribute if one not specified.
	// Rename var for clarity.
	$form_id = $id;
	if ( $form_id === null || $form_id == false ) {
		$form_id = 'pt_checkout_form_' . $pt_id;
	}

	if(empty($until_data[$pt_id])) {
        $until_data[$pt_id]['until_date'] = $until ? $until : '';
        $until_data[$pt_id]['until_message'] = $until_message;
    }

	Paytium_Shortcode_Tracker::set_as_base( 'paytium', $attr );
	Paytium_Shortcode_Tracker::$is_main_shortcode = true;

	$mollie_api_key  = pt_get_api_key();

	// Check if key's are entered at all, otherwise throw error message
	if ( empty( $mollie_api_key ) ) {
		$limit_data = array ();
		if ( ! current_user_can( 'manage_options' ) ) {
			return '<div class="pt-form-alert">' . __( 'Payments are not possible at this moment.', 'paytium' ) . '</div>';
		}
	}

	$html = '';

	if ( $login == TRUE && !is_user_logged_in() ) {
		$args = array (
			'echo'           => false,
			'form_id'        => 'loginform',
			'label_username' => __( 'Username' ),
			'label_password' => __( 'Password' ),
			'label_remember' => __( 'Remember Me' ),
			'label_log_in'   => __( 'Log In' ),
			'id_username'    => 'user_login',
			'id_password'    => 'user_pass',
			'id_remember'    => 'rememberme',
			'id_submit'      => 'wp-submit',
			'remember'       => true,
			'value_username' => null,
			'value_remember' => true
		);
		$html .= wp_login_form( $args );
	}

	// Add Parsley JS form validation attribute here.
	$html .= '<form method="POST" action="" class="pt-checkout-form" id="' . esc_attr( $form_id ) . '" data-pt-id="' . $pt_id . '" data-parsley-validate enctype="multipart/form-data" data-currency="'.$currency.'">';

	// Check if key's are entered at all, otherwise throw error message
	if ( empty( $mollie_api_key ) && current_user_can( 'manage_options' ) ) {
			$html .= '<div class="pt-form-alert">' . __( 'No valid Mollie API key(s) found in WordPress Admin > Paytium > Settings. You can submit this form for testing, but will need to add the Mollie API keys before you can accept real payments!', 'paytium' ) . '</div>';
	}

	// Check if pt-amount-validation-failed is set (from pt_process_payment), and if so show an alert
	if ( ! empty( $_REQUEST['pt-field-validation-failed'] ) ) {
		$html .= '<div class="pt-form-alert">' . __( 'The form did not pass validation.', 'paytium' ) . '</div>';
	}

	// Check if pt-amount-validation-failed is set (from pt_process_payment), and if so show an alert
	if ( ! empty( $_REQUEST['pt-amount-validation-failed'] ) ) {
		$html .= '<div class="pt-form-alert">' . __( 'No (valid) amount entered or amount is too low!', 'paytium' ) . '</div>';
	}

	// Check if pt-validation-failed is set (from pt_process_payment), and if so show an alert
	if ( ! empty( $_REQUEST['pt-js-validation-failed'] ) && current_user_can( 'manage_options' ) ) {
		$html .= '<div class="pt-form-alert">' . sprintf( __( 'Paytium\'s JavaScript isn\'t loaded! <a href="%s">Please read this FAQ.</a>', 'paytium' ), 'https://www.paytium.nl/handleiding/veelgestelde-vragen/#javascript-problemen-met-paytium-of-extra-velden-worden-niet-opgeslagen' ) . '</div>';
	}

	// Check that JavaScript is enabled, otherwise show an alert.
	$html .= '<noscript><div class="pt-form-alert">' . __( 'This form requires JavaScript, enable it in your browser.', 'paytium' ) . '</div></noscript>';

	// Save all of our options to an array so others can run them through a filter if they need to
	// David: Used for the disabled tab in settings, can be removed if I don't re-enable the tab.
	$pt_script_options = array (
		'script' => array (
			'key'              => $mollie_api_key,
			'name'             => html_entity_decode( $name ),
			'description'      => html_entity_decode( $description ),
			'amount'           => $amount,
			'customer-details' => $customer_details,
			'label'            => html_entity_decode( $button_label ),
			'email'            => sanitize_email( pt_prefill_email() ),
		),
		'other'  => array (
			'pt_redirect_url'           => $pt_redirect_url
		),
	);

	// Collect all amounts so we can store them later for server side validation
	pt_paytium_collect_amounts( $amount );

	$html .= do_shortcode( $content );

	if ($until_data[$pt_id]['until_date'] && time() > strtotime($until_data[$pt_id]['until_date']) ) {
        return '<form class="pt-checkout-form"><div class="pt-form-alert">' . $until_data[$pt_id]['until_message'] . '</div></form>';
    }

    // BC - When 'amount' attribute is set at the main shortcode, add a hidden 'label' field
    if (!empty($amount)) {

	    // Collect all amounts so we can store them later for server side validation
	    pt_paytium_collect_amounts( $amount );

	    if (!empty($limit) && !empty($limit_message) && !empty($item_id)) {
            $html .= '<input type="hidden" name="pt_items[' . $counter . '][item_id]" value="' . $item_id . '" data-pt-user-label="Item ID" />';
            $html .= '<input type="hidden" name="pt_items[' . $counter . '][limit]" value="' . $limit . '" data-pt-user-label="Limit" />';
            $html .= '<input type="hidden" name="pt_items[' . $counter . '][limit-message]" value="' . $limit_message . '" data-pt-user-label="Limit Message" />';

            if (get_option('paytium_item_limits')) {

                $paytium_item_limits = unserialize(get_option('paytium_item_limits'));

                if (array_key_exists(sanitize_key($item_id), $paytium_item_limits) && $paytium_item_limits[sanitize_key($item_id)] >= (int)$limit) {

                    $limit_data = array();
                    return $html = '<form class="pt-checkout-form"><div class="pt-form-alert">' . __($limit_message, 'paytium') . '</div></form>';
                }
            }
        }

		if (!empty($crowdfunding_id)) {
			$html .= '<input type="hidden" name="pt_items[' . $counter . '][crowdfunding_id]" value="' . $crowdfunding_id . '" data-pt-user-label="Crowdfunding ID" />';
		}

        $html .= pt_cf_label($attr);
    }

	Paytium_Shortcode_Tracker::$is_main_shortcode = false;

	$pt_script_options = apply_filters( 'pt_modify_script_options', $pt_script_options );

	$name            = $pt_script_options['script']['name'];
	$description     = $pt_script_options['script']['description'];
	$pt_redirect_url = $pt_script_options['other']['pt_redirect_url'];

	$html .= '<input type="hidden" name="pt-name" value="' . esc_attr( $name ) . '" />';
	$html .= '<input type="hidden" name="pt-description" value="' . esc_attr( $description ) . '" />';
	$html .= '<input type="hidden" name="pt-amount" class="pt_amount" value="" />';
	$html .= '<input type="hidden" name="pt-currency" class="pt_currency" value="'.$currency.'" />';
	$html .= '<input type="hidden" name="pt_redirect_url" value="' . esc_attr( ( ! empty( $pt_redirect_url ) ? $pt_redirect_url : get_permalink() ) ) . '" />';

	$form_load_id = pt_paytium_get_form_load_id($pt_id);

	if($form_load_id !== 0) {
		$html .= '<input type="hidden" name="pt-form-load" value="' . esc_attr( $form_load_id ) . '" />';
	}
	$html .= '<input type="hidden" name="pt-form-id" value="' . esc_attr( $pt_id ) . '" />';

	// Add customer details fields if it is enabled
	if ( $customer_details === 'true' ) {
		$html .= '<label for="pt-customer-details-name">Naam:</label><input type="text" name="pt-customer-details-name" class="pt-customer-details-name" value="" />';
		$html .= '<label for="pt-customer-details-street">Straatnaam:</label><input type="text" name="pt-customer-details-street" class="pt-customer-details-street" value="" />';
		$html .= '<label for="pt-customer-details-house_number">Huisnummer:</label><input type="text" name="pt-customer-details-house_number" class="pt-customer-details-house_number" value="" />';
		$html .= '<label for="pt-customer-details-city">Plaatsnaam:</label><input type="text" name="pt-customer-details-city" class="pt-customer-details-city" value="" />';
		$html .= '<label for="pt-customer-details-postcode">Postcode:</label><input type="text" name="pt-customer-details-postcode" class="pt-customer-details-postcode" value="" />';
		$html .= '<label for="pt-customer-details-country">Land:</label><input type="text" name="pt-customer-details-country" class="pt-customer-details-country" value="" />';
	}

	if (is_file( PT_PATH . 'paytium-pro.php' ) || is_file( PT_PATH . 'paytium-premium.php' )) {
		$html .= '<input type="hidden" name="pt-paytium-pro" id="pt-paytium-pro" value="true" />';
	}

	if(!has_shortcode($content,'paytium_button')) {
		// Add a filter here to allow developers to hook into the form
		$filter_html = '';
		$html        .= apply_filters( 'pt_before_payment_button', $filter_html );

		// Payment button defaults to built-in Paytium class "paytium-button-el" unless set to "none".
		$html .= '<button class="pt-payment-btn' . ( $button_style == 'none' ? '' : ' paytium-button-el' ) . '"><span>' . $button_label . '</span></button>';
	}
	$html .= '</form>';

	// Store all form amounts so we can use them later for server side validation
	pt_paytium_store_form_elements($form_load_id);

	$error_count = Paytium_Shortcode_Tracker::get_error_count();

	Paytium_Shortcode_Tracker::reset_error_count();

	if ( $error_count > 0 ) {
        $limit_data = array();
		if ( current_user_can( 'manage_options' ) ) {
			return Paytium_Shortcode_Tracker::print_errors();
		}

		return '';
	}

    if (!empty($limit_data['limits']) && count($limit_data['limits']) == 1 && (isset($limit_data['amount_count']) && $limit_data['amount_count'] == 1)) {

        $message = array_values($limit_data['limits']);
	    $message = $message[0];

	    if ($message !== 0) {
            $html = '<form class="pt-checkout-form"><div class="pt-form-alert">' . __($message, 'paytium') . '</div></form>';
        }
    }
    $limit_data = array();

	return $html;

}

add_shortcode( 'paytium', 'pt_paytium_shortcode' );

/**
 * Function to process [paytium_total] shortcode
 *
 * @since 1.0.0
 */
function pt_paytium_total( $attr ) {

	if(!Paytium_Shortcode_Tracker::$is_main_shortcode) {
		return;
	}

	global $counter, $ptfg_counter, $pt_id, $form_currency;
    $ptfg_counter++;

	$currency = is_file( PT_PATH . 'features/currency.php' ) ? get_paytium_currency_symbol($form_currency[$pt_id]) : '€';

	if (!empty($attr)) {
		foreach ($attr as $key => $attribute) {
			$attr[$key] = esc_attr($attribute);
		}
	}

	$attr = shortcode_atts( array (
		'label' => __( 'Total:', 'paytium' ),
		'minimum' => ''
	), $attr, 'paytium_total' );

	extract( $attr );

	$label = get_option( 'paytium_total_label', $attr['label'] );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_total_' . $counter, 'paytium_total', $attr, false );

	$html = esc_html($label) . ' <span class="pt-total-amount">'.$currency.' -</span>';

	if (isset($minimum) && $minimum != '') {
		$html .= '<input type="hidden" class="pt-field pt-total-minimum" data-parsley-mintotal="' . esc_attr($minimum) . '" data-parsley-errors-container="#pt_total_errors_' . $counter . '" value="">';
		$html .= '<div id="pt_total_errors_' . $counter . '"></div>';
	}

	$args = pt_get_args( '', $attr );
	$counter++;

	return '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-total-amount">' . apply_filters( 'pt_paytium_total', $html, $args ) . '</div>';

}
add_shortcode( 'paytium_total', 'pt_paytium_total' );

/**
 * Shortcode to output a checkbox - [paytium_checkbox]
 *
 * @since 1.0.0
 */
function pt_cf_checkbox( $attr ) {

	if(!Paytium_Shortcode_Tracker::$is_main_shortcode) {
		return;
	}

	global $counter;
    global $ptfg_counter;
    $ptfg_counter++;

	if (!empty($attr)) {
		foreach ($attr as $key => $attribute) {
			$attr[$key] = esc_attr($attribute);
		}
	}

	$attr = shortcode_atts( array (
		'id'       => '',
		'label'    => '',
		'required' => 'false',
		'default'  => 'false'
	), $attr, 'paytium_date' );

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_checkbox_' . $counter, 'paytium_checkbox', $attr, false );

	$id = 'pt_cf_checkbox_' . $counter;

	$html = '<label>';
	$html .= '<input type="checkbox" id="' . esc_attr( $id ) . '" class="pt-cf-checkbox" name="pt_form_field[' . esc_attr( $id ) . ']" ';
	$html .= ( ( $required === 'true' ) ? 'required' : '' ) . ' ' . checked( ( $default === 'true' || $default === 'checked' ) ) . ' value="Yes" data-parsley-errors-container="#pt_cf_checkbox_error_' . $counter . '">';
	$html .= esc_html($label) . '</label>';

	// Hidden field to hold a value to pass to Paytium payment record.
	$html .= '<input type="hidden" id="' . esc_attr( $id ) . '_hidden" class="pt-cf-checkbox-hidden" name="pt_form_field[' . esc_attr( $id ) . ']" value="' . ( ( 'true' === $default || 'checked' === $default ) ? 'Yes' : 'No' ) . '">';
	$html .= '<div id="pt_cf_checkbox_error_' . $counter . '"></div>';

	$args = pt_get_args( $id, $attr, $counter );

	$counter++;

	return '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-checkbox">' . apply_filters( 'pt_paytium_checkbox', $html, $args ) . '</div>';

}
add_shortcode( 'paytium_checkbox', 'pt_cf_checkbox' );


/**
 * Shortcode to output a checkbox for amounts - [paytium_checkbox]
 *
 * @since 2.1.0
 */
function pt_cf_checkbox_new( $attr ) {

	if(!Paytium_Shortcode_Tracker::$is_main_shortcode) {
		return;
	}

    global $counter, $ptfg_counter, $limit_data, $pt_id, $form_currency;
    $ptfg_counter++;

	$currency = is_file( PT_PATH . 'features/currency.php' ) ? get_paytium_currency_symbol($form_currency[$pt_id]) : '€';
	$currency_symbol_after = $currency == 'NOK' || $currency == 'SEK' || $currency == 'fr.';
	
	if (!empty($attr)) {
		foreach ($attr as $key => $attribute) {
			$attr[$key] = esc_attr($attribute);
		}
	}

	$attr = shortcode_atts(array(
		'id'                     => '',
		'label'                  => '',
		'default'                => '',
		'required'               => '',
		'options'                => '',
		'options_are_quantities' => 'false',
		'amounts'                => '',
		'options_are_amounts'    => 'false',
		'tax'                    => '',
		'quantity'               => '',
		'quantity_min'           => '',
		'quantity_max'           => '',
		'quantity_step'       => '',
		'limit'                  => '',
		'limit_message'          => '',
		'item_id'                => '',
		'general_limit'             => '',
		'general_item_id'           => '',
		'show_items_left' => true,
		'show_items_left_after' => 10,
		'show_items_left_only_for_admin' => '',
		'crowdfunding_id'			=> '',
	), $attr, 'paytium_checkbox');

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_checkbox_' . $counter, 'paytium_checkbox', $attr, false );

	$id = 'pt_items[' . absint( $counter ) . ']';

	$options = explode( '/', $options );

	$is_limit = $is_general_limit = false;

    if (!empty($limit) && !empty($limit_message) && !empty($item_id)) {
        $item_ids = explode('/', $item_id);
        $item_limits = explode('/', $limit);
		$show_items_left_after_arr = explode('/', $show_items_left_after);
		$is_limit = true;

        if (count($options) != count($item_ids)) {
			$is_limit = false;
            Paytium_Shortcode_Tracker::update_error_count();

            if (current_user_can('manage_options')) {
                Paytium_Shortcode_Tracker::add_error_message('<h6>' . __('Your number of options and item_ids are not equal.', 'paytium') . '</h6>');
            }

            return '';
        }
        if (count($options) != count($item_limits)) {
			$is_limit = false;
            Paytium_Shortcode_Tracker::update_error_count();

            if (current_user_can('manage_options')) {
                Paytium_Shortcode_Tracker::add_error_message('<h6>' . __('Your number of options and limits are not equal.', 'paytium') . '</h6>');
            }

            return '';
        }
    }

	if (is_file( PT_PATH . 'features/general-limit.php' ) && !empty($general_item_id) && !empty($general_limit)) {

		$general_item_ids = explode('/', $general_item_id);
		$general_limits = explode('/', $general_limit);
		$is_general_limit = true;

		if (count($options) != count($general_item_ids)) {
			$is_general_limit = false;
			Paytium_Shortcode_Tracker::update_error_count();

			if (current_user_can('manage_options')) {
				Paytium_Shortcode_Tracker::add_error_message('<h6>' . __('Your number of options and general_item_ids are not equal.', 'paytium') . '</h6>');
			}

			return '';
		}
		if (count($options) != count($general_limits)) {
			$is_general_limit = false;
			Paytium_Shortcode_Tracker::update_error_count();

			if (current_user_can('manage_options')) {
				Paytium_Shortcode_Tracker::add_error_message('<h6>' . __('Your number of options and general_limits are not equal.', 'paytium') . '</h6>');
			}

			return '';
		}

		if (empty($limit) && !empty($item_id)) {
			$item_ids = explode('/', $item_id);
			if (count($options) != count($item_ids)) {
				$is_general_limit = false;
				Paytium_Shortcode_Tracker::update_error_count();

				if (current_user_can('manage_options')) {
					Paytium_Shortcode_Tracker::add_error_message('<h6>' . __('Your number of options and item_ids are not equal.', 'paytium') . '</h6>');
				}

				return '';
			}
		}
	}

    if (!empty($amounts)) {
        $amounts = explode('/', str_replace(' ', '', $amounts));//

		if ( count( $options ) != count( $amounts ) ) {
			Paytium_Shortcode_Tracker::update_error_count();

			if ( current_user_can( 'manage_options' ) ) {
				Paytium_Shortcode_Tracker::add_error_message( '<h6>' . __( 'Your number of options and amounts are not equal.', 'paytium' ) . '</h6>' );
			}

			return '';
		}
	}

	$quantity_html  = ( ( 'true' == $options_are_quantities ) ? 'data-pt-quantity="true" ' : '' );
	$quantity_class = ( ( 'true' == $options_are_quantities ) ? ' pt-cf-quantity' : '' );
	$amount_class = ( ( ! $amounts == false || $options_are_amounts == 'true' ) ? ' pt-cf-amount' : '' );
	$required = ( ( $attr['required'] ) == 'true' ) ? 'required' : '';
	$has_quantity_class = '';

	$html = ( ! empty( $label ) ? '<label id="pt-cf-checkbox-label">' . esc_html($label) . ':</label>' : '' );

	$html .= '<div class="pt-checkbox-group">';

    $i = $k = $g = 1;
    foreach ($options as $option) {

		$option = trim( $option );
		$value  = $option;

		if ( $options_are_amounts == 'true' ) {
			$amount = $option;
			$option_name = $currency . ' ' . $amount;
			$value = pt_user_amount_to_float( $value );

			// Collect all amounts so we can store them later for server side validation
			pt_paytium_collect_amounts($amount);

		} elseif ( ! empty( $amounts ) ) {
			$amount = $amounts[ $i - 1 ];
			$option_name = $option . ' - '. $currency_symbol_after ? $amount . ' ' . $currency : $currency . ' ' . $amount;
			$value = pt_user_amount_to_float( $amount );
		}

        $html .= '<label title="' . esc_attr($option) . '" >';

        if (isset($item_ids) && isset($item_limits)) {

            $paytium_item_limits = unserialize(get_option('paytium_item_limits'));

            if (get_option('paytium_item_limits') && array_key_exists(sanitize_key($item_ids[$i - 1]), $paytium_item_limits)
                && $paytium_item_limits[sanitize_key($item_ids[$i - 1])] >= (int)$item_limits[$i - 1]) {

            	if (!empty($general_item_ids) && !empty($general_limits) &&
					function_exists('pt_paytium_general_limit_reached') && pt_paytium_general_limit_reached($general_item_ids[$i - 1],$general_limits[$i - 1],$paytium_item_limits)) {
            		$g++;
				}
				else {
					$html .= '<input type="checkbox" name="' . esc_attr($id) . '[amount]" disabled>';
					$html .= '<span class="pt-option-disabled">' . (isset($option_name) ? $option_name : $option) . ' ('.$limit_message.')</span>';
				}
                $k++;
            } else {

            	$general_limit_html_data = isset($general_item_ids,$general_limits) ? ' data-general_item_id="' . $general_item_ids[$i - 1] . '" data-general_limit="' . $general_limits[$i - 1] . '"' : '';

                $html .= '<input type="checkbox" name="' . esc_attr($id) . '[amount]" value="' . $value . '" ' . checked($default, $option, false) .
                    ' class="' . esc_attr($id) . '_' . $i . $quantity_class . $amount_class . ' pt-checkbox-amount" data-pt-price="' . $value . '" data-pt-checkbox-id="' . $i . '"
                    data-parsley-multiple="checkbox-' . $ptfg_counter . '" data-parsley-errors-container=".pt-checkbox-group-errors-' . $ptfg_counter . '"' . $required . '
                    data-parsley-errors-container=".pt-form-group" ' . $quantity_html . ' data-item_id="' . $item_ids[$i - 1] . '" data-limit="' . $item_limits[$i - 1] . '"'.$general_limit_html_data.'>';
                $html .= '<span>' . (isset($option_name) ? $option_name : $option) . '</span>';

	            if ( isset($paytium_item_limits[sanitize_key($item_ids[$i - 1])])) {
		            $items_left = (int)$item_limits[$i - 1] - $paytium_item_limits[sanitize_key($item_ids[$i - 1])];
	            } else {
		            $items_left = null;
	            }

				$show_items_left_after = is_array($show_items_left_after_arr) && isset($show_items_left_after_arr[$i - 1]) ? $show_items_left_after_arr[$i - 1] : 10;
				$show_items_left_only_for_admin = filter_var($show_items_left_only_for_admin, FILTER_VALIDATE_BOOLEAN);

				if ( $show_items_left !== 'false' && isset($items_left) && $items_left < $show_items_left_after &&
					(($show_items_left_only_for_admin && current_user_can('administrator')) || (!$show_items_left_only_for_admin))) {

					$html .= '<p class="pt-items-left">'.sprintf(__('Only %s left!','paytium'), $items_left).'</p>';
				}

				if (!empty($quantity_max) && $quantity_max > $items_left) $quantity_max = $items_left;

            }
        }
        elseif (!empty($general_item_ids) && !empty($general_limits) && empty($item_limits)) {

			$general_limit_html_data = isset($general_item_ids,$general_limits) ? ' data-general_item_id="' . $general_item_ids[$i - 1] . '" data-general_limit="' . $general_limits[$i - 1] . '"' : '';
			$item_id = !empty($item_ids) && isset($item_ids[$i - 1]) ? ' data-item_id="' . $item_ids[$i - 1] : '';

			$general_limit_class = empty($quantity) ? ' general-limit-item' : '';
			$html .= '<input type="checkbox" name="' . esc_attr($id) . '[amount]" value="' . $value . '" ' . checked($default, $option, false) .
				' class="' . esc_attr($id) . '_' . $i . $quantity_class . $amount_class . $general_limit_class . ' pt-checkbox-amount" data-pt-price="' . $value . '" data-pt-checkbox-id="' . $i . '"
                    data-parsley-multiple="checkbox-' . $ptfg_counter . '" data-parsley-errors-container=".pt-checkbox-group-errors-' . $ptfg_counter . '"' . $required . '
                    data-parsley-errors-container=".pt-form-group" ' . $quantity_html . $item_id . '"'.$general_limit_html_data.'>';
			$html .= '<span>' . (isset($option_name) ? $option_name : $option) . '</span>';

			if ( isset($paytium_item_limits[sanitize_key($general_item_ids[$i - 1])])) {
				$items_left = (int)$general_limits[$i - 1] - $paytium_item_limits[sanitize_key($general_item_ids[$i - 1])];
			} else {
				$items_left = $general_limits[$i - 1];
			}
			$general_items_left = $items_left;

			if (!empty($quantity_max) && $quantity_max > $items_left) $quantity_max = $items_left;
		}
        else {
            // Don't use built-in checked() function here for now since we need "checked" in double quotes.
            $html .= '<input type="checkbox" name="' . esc_attr($id) . '[amount]" value="' . $value . '" ' . checked($default, $option, false) .
                ' class="' . esc_attr($id) . '_' . $i . $quantity_class . $amount_class . ' pt-checkbox-amount" data-pt-price="' . $value . '" data-pt-checkbox-id="' . $i . '" data-parsley-multiple="checkbox-' . $ptfg_counter . '" data-parsley-errors-container=".pt-checkbox-group-errors-' . $ptfg_counter . '"' . $required . ' data-parsley-errors-container=".pt-form-group" ' . $quantity_html . '>';
            $html .= '<span>' . (isset($option_name) ? $option_name : $option) . '</span>';

        }


	    if(!empty($quantity)) {

		    $has_quantity_class = 'has-quantity-input';

		    $attributes = '';
		    $attributes .= (!empty($quantity_min) ? 'min="' . $quantity_min . '" ' : '');
		    $attributes .= (!empty($quantity_max) ? 'max="' . $quantity_max . '" ' : '');
		    $attributes .= (!empty($quantity_step) ? 'step="' . $quantity_step . '" ' : '');
		    $default_value = (!empty($quantity_min) ? 'value="' . $quantity_min . '" ' : 'value=1');
			$limit_class = isset($general_items_left) ? ' general-limit-qty' : '';

			$quantity_html = '<div class="paytium-quantity-input">'.
				'<button type="button" class="paytium-spinner decrement">-</button>'.
				'<input type="number" ' . $attributes . ' class="pt-quantity-input'.$limit_class.'" name="' . $id . '[quantity]" ' . $default_value . '  >'.
				'<button type="button" class="paytium-spinner increment">+</button>'.
				'</div>';
			$html .= $quantity_html;
	    }
        $html .= '</label>';
		$i ++;
	}

	$html .= '<div class="pt-checkbox-group-errors-'.$ptfg_counter.'"></div>';
	$html .= '<input type="hidden" name="' . $id . '[amount]" id="' . $id . '[amount][total]" value="" data-pt-price="' . esc_attr( pt_user_amount_to_float($default) ) . '" />';
	$html .= '<input type="hidden" name="' . $id . '[label]" value="' . wp_kses_post( $attr['label'] ) . '" data-pt-original-label="' . wp_kses_post( $attr['label'] ) . '" data-pt-checked-options="[]">';
	$html .= '<input type="hidden" name="' . $id . '[value]" value="">';
	$html .= '<input type="hidden" name="' . $id . '[tax_percentage]" value="' . floatval( $attr['tax'] ) . '">';
	$html .= '<input type="hidden" name="' . $id . '[type]" value="checkbox">';

	if ($is_limit || $is_general_limit) {
		$html .= '<input type="hidden" name="' . $id . '[limit_data]" value="[]"/>';
		if (!$is_limit && $is_general_limit) {
			$html .= '<input type="hidden" name="' . $id . '[limit-message]" value="general" data-pt-user-label="Limit Message" />';
		}
		else {
			$html .= '<input type="hidden" name="' . $id . '[limit-message]" value="' . $limit_message . '" data-pt-user-label="Limit Message" />';
		}
	}

	if (!empty($crowdfunding_id)) {
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][crowdfunding_id]" value="' . $crowdfunding_id . '" data-pt-user-label="Crowdfunding ID" />';
	}

    $html .= '</div>'; //pt-checkbox-group

	$args = pt_get_args( $id, $attr, $counter );

	$counter++; // Increment static counter

    if ($i == $k && $i != $g) {
        $limit_data['limits'][$item_id] = $limit_message;
        if ($options_are_amounts == 'true' || !empty($amounts)) {
            if(isset($limit_data['amount_count'])) {
                $limit_data['amount_count']++;
            }
            else $limit_data['amount_count'] = 1;
        }
        $html = '<div class="pt-form-alert">' . __($limit_message, 'paytium') . '</div>';
    }
	elseif ($i == $g) {
		$html = '';
	}
    return '<div class="pt-form-group pt-form-group-' . $ptfg_counter . ' pt-form-group-checkbox-new '. $has_quantity_class.'">' . apply_filters('pt_paytium_checkbox', $html, $args) . '</div>';

}

/**
 * Shortcode to output a number box - [paytium_number]
 *
 * @since 1.0.0
 *
 * Reviewed, issue #38. I don't like the way it works, I think it's unusable. Keeping the code in for BC, adding a comment, but not going to promote it.
 */
function pt_cf_number( $attr ) {

	if(!Paytium_Shortcode_Tracker::$is_main_shortcode) {
		return;
	}

	global $counter;
    global $ptfg_counter;
    $ptfg_counter++;

	if (!empty($attr)) {
		foreach ($attr as $key => $attribute) {
			$attr[$key] = esc_attr($attribute);
		}
	}

	$attr = shortcode_atts( array (
		'id'                     => '',
		'label'                  => '',
		'required'               => 'false',
		'placeholder'            => '',
		'default'                => '',
		'min'                    => '',
		'max'                    => '',
		'step'                   => '',
		'multiplier_id'          => '',
		'options_are_quantities' => 'false'
	), $attr, 'paytium_number' );

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_number_' . $counter, 'paytium_number', $attr, false );

	// Check for ID and if it doesn't exist then we will make our own
	if ( $id == '' ) {
		$id = 'pt_cf_number_' . $counter;
	}

	$quantity_html  = ( ( 'true' == $options_are_quantities ) ? 'data-pt-quantity="true" data-parsley-min="1" ' : '' );
	$quantity_class = ( ( 'true' == $options_are_quantities ) ? ' pt-cf-quantity' : '' );

	$min  = ( ! empty( $min ) ? 'min="' . $min . '" ' : '' );
	$max  = ( ! empty( $max ) ? 'max="' . $max . '" ' : '' );
	$step = ( ! empty( $step ) ? 'step="' . $step . '" ' : '' );

	$html = ( ! empty( $label ) ? '<label for="' . esc_attr( $id ) . '">' . esc_html($label) . '</label>' : '' );

	$multiplier_class = $multiplier_data = '';

	if (!empty($multiplier_id)) {
		$multiplier_class = " pt_multiplier";
		$multiplier_data = ' data-pt_multiplier_id="'.$multiplier_id.'"';
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][multiplier_id]" value="' . $multiplier_id . '" data-pt-user-label="Multiplier ID" />';
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][label]" value="' . esc_html($label) . '" data-pt-user-label="Multiplier ID" />';
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][type]" value="multiplier" data-pt-user-label="Multiplier ID" />';
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][amount]" value="0" data-pt-user-label="Multiplier ID" />';
	}

	// No Parsley JS number validation yet as HTML5 number type takes care of it.
	$html .= '<input type="number" data-parsley-type="number" class="pt-form-control pt-cf-number' . $quantity_class . $multiplier_class . '" id="' . esc_attr( $id ) . '" name="pt_form_field[' . $id . ']" ';
	$html .= 'placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( $default ) . '" ';
	$html .= $min . $max . $step . ( ( $required === 'true' ) ? 'required' : '' ) . $quantity_html . $multiplier_data . '>';

	$args = pt_get_args( $id, $attr, $counter );

	$counter++;

	return '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-number">' . apply_filters( 'pt_paytium_number', $html, $args ) . '</div>';

}
add_shortcode( 'paytium_number', 'pt_cf_number' );


/**
 * Function to add fields with different types - [paytium_field]
 *
 * @since  1.1.0
 * @author David de Boer
 */
function pt_field( $attributes ) {

	if(!Paytium_Shortcode_Tracker::$is_main_shortcode) {
		return;
	}

	global $counter;
	global $ptfg_counter;
	global $pt_id;
	global $until_data;

	if (!empty($attributes)) {
		foreach ($attributes as $key => $attribute) {
			$attributes[$key] = esc_attr($attribute);
		}
	}

	$attr = shortcode_atts( array (
		'type'                     => '',
		'label'                    => get_option( 'pt_paytium_field', '' ),
		'class'                    => '',
		'placeholder'              => '',
		'maxlength'                => '',
		'minlength'                => '',
		'validation'               => '',
		'amount'                   => '',
		'amounts'                  => '',
		'tax'                      => '',
		'default'                  => '',
		'required'                 => '',
		'options'                  => '',
		'link'                     => '',
		'first_option'             => '',
		'first_option_text'        => '',
		'quantity'                 => '',
		'quantity_min'             => '',
		'quantity_max'             => '',
		'quantity_step'            => '',
		'newsletter'               => '',
		'newsletter_label'         => '',
		'newsletter_list'          => '',
		'newsletter_group'         => '',
		'newsletter_tags'          => '',
		'newsletter_after'         => '',
		'newsletter_hide_checkbox' => '',
		'ac_custom_field'		   => '',
		'mc_custom_field'		   => '',
		'ml_custom_field'  		   => '',
		'user_data'                => '',
		'until'                    => '',
		'until_message'            => __( 'Payments are no longer possible.', 'paytium' ),
		'max_file_size'			   => substr( ini_get( 'upload_max_filesize' ), 0, - 1 ),
		'allowed_file_types'	   => '',
		'max_files'				   => ini_get( 'max_file_uploads' ),
	), $attributes, 'paytium_field' );

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_field_' . $counter, 'paytium_field', $attr, false );

	// Default attributes
	$class       = ( ! empty( $attr['class'] ) ) ? ' ' . esc_attr( $attr['class'] ) : '';
	$placeholder = ( ! empty( $attr['placeholder'] ) ) ? 'placeholder="' . esc_attr( $attr['placeholder'] ) . '"' : '';
	$maxlength   = ( ! empty( $attr['maxlength'] ) ) ? 'maxlength="' . esc_attr( $attr['maxlength'] ) . '"' : '';
	$minlength   = ( ! empty( $attr['minlength'] ) ) ? 'minlength="' . esc_attr( $attr['minlength'] ) . '"' : '';
	$validation  = ( ! empty( $attr['validation'] ) ) ? 'data-parsley-type="' . esc_attr( $attr['validation'] ) . '"' : '';
	$required    = ( ( $attr['required'] ) == 'true' ) ? 'required' : '';
	$user_data   = ( ! empty( $attr['user_data'] ) ) ? $attr['user_data'] : 'false';

    if(empty($until_data[$pt_id]['until_date'])) {
        $until_data[$pt_id]['until_date'] = $until ? $until : '';
        $until_data[$pt_id]['until_message'] = $until_message;
    }

	pt_paytium_collect_fields( $counter, $type, $required );

	$id = 'pt_items[' . absint( $counter ) . ']';

	$html = '';

	switch ( trim( str_replace( ' ', '', strtolower( $attr['type'] ) ) ) ) {

		default:

			// Check that field is a known type (for users making mistakes)
			if (current_user_can( 'manage_options' ) ) {
				$html .= '<div class="pt-form-alert">' . __( 'Unknown field type "'.$attr["type"].'" found. This field will automatically be converted to a field of type "text", but it\'s better to use one of the official types.  <a href="https://www.paytium.nl/handleiding/extra-velden/" target="_blank" rel="nofollow">Read more ></a>', 'paytium' ) . '</div>';
			}

		case 'text':

            $ptfg_counter++;

			$default            = $attr['default'];
			$label              = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Text';
			$paid_field_class   = ( ! empty( $attr['amount'] ) ) ? ' pt-paid-field' : '';
			$has_quantity_class = ( ! empty( $attr['quantity'] ) ) ? ' has-quantity-input' : '';

		// Try to guess the field data type
		$autocomplete = pt_guess_autocomplete( $label );

			$html .= '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-field-text' . $has_quantity_class . $class . '">';
			$html .= '<label for="pt-field-text">' . esc_html($label) . ':</label><input type="text" id="pt-field-text-' . $counter . '" name="pt-field-text-' . $counter . '" autocomplete="' . $autocomplete . '" class="pt-field pt-field-text' . $paid_field_class . '" value="' . $default . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" data-pt-price="' . pt_user_amount_to_float($attr['amount']) . '" ' . $required . ' ' . $placeholder  . ' ' . $maxlength . ' ' . $minlength . ' ' . $validation . '/>';


			if(!empty($quantity)) {
				$attributes = '';
				$attributes .= (!empty($quantity_min) ? 'min="' . $quantity_min . '" ' : '');
				$attributes .= (!empty($quantity_max) ? 'max="' . $quantity_max . '" ' : '');
				$attributes .= (!empty($quantity_step) ? 'step="' . $quantity_step . '" ' : '');
				$default_value = (!empty($quantity_min) ? 'value="' . $quantity_min . '" ' : 'value=1');
				$quantity_html = '<div class="paytium-quantity-input">'.
					'<button type="button" class="paytium-spinner decrement">-</button>'.
					'<input type="number" ' . $attributes . ' class="pt-quantity-input" name="' . $id . '[quantity]" ' . $default_value . '  >'.
					'<button type="button" class="paytium-spinner increment">+</button>'.
					'</div>';
				$html .= $quantity_html;
			}
			if (!empty($attr['amount'])) {
				$html .= '<input type="hidden" name="' . $id . '[amount]" value="' . pt_user_amount_to_float($attr['amount']) . '"/>';
				$html .= '<input type="hidden" name="' . $id . '[tax_percentage]" value="' . floatval($attr['tax']) . '">';
				$html .= '<input type="hidden" name="' . $id . '[type]" value="text">';
			}
			$html .= '</div>'; // pt-form-group

			break;

		case 'hidden':

			$ptfg_counter++;

			$default  = $attr['default'];
			$label    = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Text';
			$paid_field_class = ( ! empty( $attr['amount'] ) ) ? ' pt-paid-field' : '';
			$has_quantity_class = ( ! empty( $attr['quantity'] ) ) ? ' has-quantity-input' : '';

			$html .= '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-field-hidden' . $has_quantity_class . $class . '">';
			$html .= '<input type="hidden" id="pt-field-hidden-' . $counter . '" name="pt-field-hidden-' . $counter . '" class="pt-field pt-field-hidden' . $paid_field_class . '" value="' . $default . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" data-pt-price="' . pt_user_amount_to_float($attr['amount']) . '" />';

			if (!empty($attr['amount'])) {
				$html .= '<input type="hidden" name="' . $id . '[amount]" value="' . pt_user_amount_to_float($attr['amount']) . '"/>';
				$html .= '<input type="hidden" name="' . $id . '[tax_percentage]" value="' . floatval($attr['tax']) . '">';
				$html .= '<input type="hidden" name="' . $id . '[type]" value="text">';
			}
			$html .= '</div>'; // pt-form-group

			break;

		case 'open' :
			$html .= pt_uea_amount( $attributes );
			break;

		case 'label' :
		case 'fixed' :
			$html .= pt_cf_label( $attributes );
			break;

		case 'postcode':

			$ptfg_counter ++;
			$default     = ( ! empty( $attr['default'] ) ) ? $attr['default'] : '';
			$label       = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Postcode';

			// Try to guess the field data type
			$autocomplete = pt_guess_autocomplete( $label );

			$html .= '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-field-postcode' . $class . '">';
			$html .= '<label for="pt-field-postcode">' . esc_html($label) . ':</label><input type="text" id="pt-field-postcode-' . $counter . '" name="pt-field-postcode-' . $counter . '" autocomplete="' . $autocomplete . '" class="pt-field pt-field-postcode" value="' . $default . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" ' . $required . '  ' . $placeholder  . ' ' . $maxlength . ' ' . $minlength . ' ' . $validation .  ' data-parsley-postcode data-parsley-errors-container="#parsley-errors-list-postcode-' . $counter . '" />';
			$html .= '<div id="parsley-errors-list-postcode-' . $counter . '"></div>';
			$html .= '</div>'; // pt-form-group


			break;

		case 'name':
		case 'naam':

			$ptfg_counter ++;
			$default     = ( ! empty( $attr['default'] ) ) ? $attr['default'] : pt_prefill_name();
			$label       = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Full name';

			$html .= '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-field-name' . $class . '">';
			$html .= '<label for="pt-field-name">' . esc_html($label) . ':</label><input type="text" id="pt-field-name-' . $counter . '" name="pt-field-name-' . $counter . '" autocomplete="name" class="pt-field pt-field-name" value="' . $default . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" ' . $required . '  ' . $placeholder  . ' ' . $maxlength . ' ' . $minlength . ' ' . $validation . ' />';
			$html .= pt_prefill_warning( $counter ); // Show a warning to editors and administrators about prefilled fields (so we get less requests about this)
			$html .= '</div>'; // pt-form-group

			break;

		case 'voornaam':
		case 'firstname':

			$ptfg_counter ++;
			$default     = ( ! empty( $attr['default'] ) ) ? $attr['default'] : pt_prefill_first_name();
			$label       = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'First name';

			$html .= '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-field-firstname' . $class . '">';
			$html .= '<label for="pt-field-firstname">' . esc_html($label) . ':</label><input type="text" id="pt-field-firstname-' . $counter . '" name="pt-field-firstname-' . $counter . '" autocomplete="given-name" class="pt-field pt-field-firstname" value="' . $default . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" ' . $required . ' ' . $placeholder .' "/>';
			$html .= pt_prefill_warning( $counter ); // Show a warning to editors and administrators about prefilled fields (so we get less requests about this)
			$html .= '</div>'; // pt-form-group

			break;

		case 'achternaam':
		case 'lastname':

			$default     = ( ! empty( $attr['default'] ) ) ? $attr['default'] : pt_prefill_last_name();
			$label       = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Last name';

            $ptfg_counter++;
			$html .= '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-field-lastname' . $class . '">';
			$html .= '<label for="pt-field-lastname">' . esc_html($label) . ':</label><input type="text" id="pt-field-lastname-' . $counter . '" name="pt-field-lastname-' . $counter . '" autocomplete="family-name" class="pt-field pt-field-lastname" value="' . $default . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" ' . $required . '  ' . $placeholder  . ' ' . $maxlength . ' ' . $minlength . ' ' . $validation . '/>';
			$html .= pt_prefill_warning( $counter ); // Show a warning to editors and administrators about prefilled fields (so we get less requests about this)
			$html .= '</div>'; // pt-form-group

			break;

		case 'email':
		case 'e-mail':
		case 'emailadres':
		case 'emailaddress':

			$default  = ( ! empty( $attr['default'] ) ) ? $attr['default'] : pt_prefill_email();
			$label    = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Email';
			$paid_field_class = ( ! empty( $attr['amount'] ) ) ? ' pt-paid-field' : '';
			$has_quantity_class = ( ! empty( $attr['quantity'] ) ) ? ' has-quantity-input' : '';

			$newsletter       = $attr['newsletter'];
			$newsletter_label = $attr['newsletter_label'];

			$newsletter_list     = '';
			$newsletter_segments = '';

			// If List is set in shortcode
			if ( ! empty( $attr['newsletter_list'] ) ) {
				$newsletter_list = $attr['newsletter_list'];
			}

			// If Group is set in shortcode
			if ( ! empty( $attr['newsletter_group'] ) ) {
				$newsletter_segments = $attr['newsletter_group'];
			}

			// If Tags are set in shortcode
			if ( ! empty( $attr['newsletter_tags'] ) ) {
				if ($newsletter == 'mailchimp') {
					$newsletter_segments = [];
					if (!empty($attr['newsletter_group'])) {
						$newsletter_segments['group'] = esc_attr($attr['newsletter_group']);
					}
					$newsletter_segments['tags'] = esc_attr($attr['newsletter_tags']);
				}
				else $newsletter_segments = $attr['newsletter_tags'];
			}

			// Option names defined newsletter
			$default_list_id          = 'paytium_' . $newsletter . '_default_list_id';
			$default_group_id         = 'paytium_' . $newsletter . '_default_group_id';
			$default_tags             = 'paytium_' . $newsletter . '_default_tags';
			$after_successful_payment = 'paytium_' . $newsletter . '_after_successful_payment';
			$hide_checkbox            = 'paytium_' . $newsletter . '_hide_checkbox';

			// If List is NOT set in shortcode
			if ( empty( $attr['newsletter_list'] ) ) {
				$newsletter_list = get_option( $default_list_id );
			}

			// If Group is NOT set in shortcode
			if ( empty( $attr['newsletter_group'] ) && ($newsletter == 'mailchimp' || $newsletter == 'mailerlite') ) {
				$newsletter_segments = get_option( $default_group_id );
			}

			// If Tags are NOT set in shortcode
			if ( empty( $attr['newsletter_tags'] ) && $newsletter == 'activecampaign' ) {
				$newsletter_segments = get_option( $default_tags );
			}

			// Check for 'subscribe after payment' in shortcode (and convert to integer), otherwise get global option
			if ( ! empty( $attr['newsletter_after'] ) ) {
				$newsletter_after = $attr['newsletter_after'];
				$newsletter_after = ( $newsletter_after === 'true' ) ? 1 : 0; // Convert $newsletter_after to integer for later comparisons
			} else {
				$newsletter_after = get_option( $after_successful_payment );
			}

			// Check for hide checkbox in shortcode (and convert to integer), otherwise get global option
			if ( ! empty( $attr['newsletter_hide_checkbox'] ) ) {
				$newsletter_hide_checkbox = $attr['newsletter_hide_checkbox'];
				$newsletter_hide_checkbox = ( $newsletter_hide_checkbox === 'true' ) ? 1 : 0; // Convert $newsletter_after to integer for later comparisons
			} else {
				$newsletter_hide_checkbox = get_option( $hide_checkbox );
			}

            $ptfg_counter++;
			$html .= '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-field-email' . $has_quantity_class . $class . '">';
			$html .= '<label for="pt-field-email">' . esc_html($label) . ':</label>';
			$html .= '<input type="email" id="pt-field-email-' . $counter . '" name="pt-field-email-' . $counter . '" autocomplete="email" class="pt-field pt-field-email' . $paid_field_class . '" value="' . $default . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" data-pt-price="' . pt_user_amount_to_float($attr['amount']) . '" ' . $required . '  ' . $placeholder  . ' ' . $maxlength . ' ' . $minlength . ' ' . $validation . ' data-parsley-errors-container="#parsley-errors-list-email-' . $counter . '"  />';
			$html .= pt_prefill_warning( $counter ); // Show a warning to editors and administrators about prefilled fields (so we get less requests about this)


			// Add a filter here to allow developers to hook into the form
			$filter_html = '';
			$newsletter_segments = is_array($newsletter_segments) ? $newsletter_segments : esc_attr($newsletter_segments);
			$html .= apply_filters( 'pt_after_email_field', $filter_html, $newsletter, $newsletter_label, esc_attr($newsletter_list), $newsletter_segments, $newsletter_after, $newsletter_hide_checkbox, $counter );

			$html .= '<div id="parsley-errors-list-email-' . $counter . '"></div>';
			if(!empty($quantity)) {
				$attributes = '';
				$attributes .= (!empty($quantity_min) ? 'min="' . $quantity_min . '" ' : '');
				$attributes .= (!empty($quantity_max) ? 'max="' . $quantity_max . '" ' : '');
				$attributes .= (!empty($quantity_step) ? 'step="' . $quantity_step . '" ' : '');
				$default_value = (!empty($quantity_min) ? 'value="' . $quantity_min . '" ' : 'value=1');
				$quantity_html = '<div class="paytium-quantity-input">'.
					'<button type="button" class="paytium-spinner decrement">-</button>'.
					'<input type="number" ' . $attributes . ' class="pt-quantity-input" name="' . $id . '[quantity]" ' . $default_value . '  >'.
					'<button type="button" class="paytium-spinner increment">+</button>'.
					'</div>';
				$html .= $quantity_html;
			}
			if (!empty($attr['amount'])) {
				$html .= '<input type="hidden" name="' . $id . '[amount]" value="' . pt_user_amount_to_float($attr['amount']) . '"/>';
				$html .= '<input type="hidden" name="' . $id . '[tax_percentage]" value="' . floatval($attr['tax']) . '">';
				$html .= '<input type="hidden" name="' . $id . '[type]" value="email">';
			}
			$html .= '</div>'; // pt-form-group

			break;

		case 'phone':
			$ptfg_counter ++;
			$default     = ! empty( $attr['default'] ) ? $attr['default'] : '';
			$label       = ! empty( $attr['label'] ) ? $attr['label'] : 'Phone';

			$html .= '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-field-phone' . $class . '">';
			$html .= '<label for="pt-field-phone">' . esc_html($label) . ':</label><input type="text" id="pt-field-phone-' . $counter . '" name="pt-field-phone-' . $counter . '" autocomplete="given-name" class="pt-field pt-field-phone" data-parsley-phone data-pt-user-label="' . $label . '" value="' . $default . '" data-pt-field-type="' . $attr['type'] . '"  ' . $required . ' ' . $placeholder .' "/>';
			$html .= '</div>'; // pt-form-group

			break;

		case 'textarea':

            $ptfg_counter++;
			$default  = $attr['default'];
			$label    = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Comments';
			$paid_field_class = ( ! empty( $attr['amount'] ) ) ? ' pt-paid-field' : '';
			$has_quantity_class = ( ! empty( $attr['quantity'] ) ) ? ' has-quantity-input' : '';

			$html .= '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-field-textarea' . $has_quantity_class . $class . '">';
			$html .= '<label for="pt-field-textarea">' . esc_html($label) . ':</label><textarea id="pt-field-textarea-' . $counter . '" name="pt-field-textarea-' . $counter . '" class="pt-field pt-field-textarea' . $paid_field_class . '" value="' . $default . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" data-pt-price="' . pt_user_amount_to_float($attr['amount']) . '" ' . $required . ' ' . $maxlength . ' ' . $minlength . ' ' . $validation . ' ' . $placeholder . '>' . $default . '</textarea>';
			if(!empty($quantity)) {
				$attributes = '';
				$attributes .= (!empty($quantity_min) ? 'min="' . $quantity_min . '" ' : '');
				$attributes .= (!empty($quantity_max) ? 'max="' . $quantity_max . '" ' : '');
				$attributes .= (!empty($quantity_step) ? 'step="' . $quantity_step . '" ' : '');
				$default_value = (!empty($quantity_min) ? 'value="' . $quantity_min . '" ' : 'value=1');
				$quantity_html = '<div class="paytium-quantity-input">'.
					'<button type="button" class="paytium-spinner decrement">-</button>'.
					'<input type="number" ' . $attributes . ' class="pt-quantity-input" name="' . $id . '[quantity]" ' . $default_value . '  >'.
					'<button type="button" class="paytium-spinner increment">+</button>'.
					'</div>';
				$html .= $quantity_html;
			}
			if (!empty($attr['amount'])) {
				$html .= '<input type="hidden" name="' . $id . '[amount]" value="' . pt_user_amount_to_float($attr['amount']) . '"/>';
				$html .= '<input type="hidden" name="' . $id . '[tax_percentage]" value="' . floatval($attr['tax']) . '">';
				$html .= '<input type="hidden" name="' . $id . '[type]" value="textarea">';
			}
			$html .= '</div>'; // pt-form-group

			break;

		case 'radio' :

			// Paid field
			if ( ( isset( $attributes['amounts'] ) && ! empty( $attributes['amounts'] ) ) || ( isset( $attributes['options_are_amounts'] ) && 'true' == $attributes['options_are_amounts'] ) ) {
				$html .= pt_cf_radio( $attributes );
				break;
			}

            $ptfg_counter++;
			$default  = $attr['default'];
			$label    = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Options';
			$options  = ( ! empty( $attr['options'] ) ) ? $attr['options'] : 'No options found.';
			$options = explode( '/', $options );

			$html .= '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-field-radio' . $class . '">';
			$html .= ( ! empty( $label ) ? '<label for="pt-field-radio">' . esc_html($label) . ':</label>' : '' );
			$html .= '<div class="pt-radio-group">';


			$i = 1;
			foreach ( $options as $option ) {

				$option = trim( $option );
				$value  = $option;

				if ( empty( $default ) ) {
					$default = $option;
				}

				$html .= '<label title="' . esc_attr( $option ) . '">';
				$html .= '<input type="radio" id="pt-field-radio-' . $counter . '" name="pt-field-radio-' . $counter . '" class="pt-field pt-field-radio" value="' . $option . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" ' . $required .
				         ( $default == $option ? ' checked="checked"' : ' ' ) . ' >';
				$html .= '<span>' . ( isset( $option_name ) ? $option_name : $option ) . '</span>';
				$html .= '</label>';

				$i ++;
			}

			$html .= '</div>'; //pt-radio-group
			$html .= '</div>'; //pt-form-group

			break;

		case 'checkbox':

			// Paid field
			if ( ( isset( $attributes['amounts'] ) && ! empty( $attributes['amounts'] ) ) || ( isset( $attributes['options_are_amounts'] ) && 'true' == $attributes['options_are_amounts'] ) ) {
				$html .= pt_cf_checkbox_new( $attributes );
				break;
			}

            $ptfg_counter++;
			$label    = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Options';
			$options  = ( ! empty( $attr['options'] ) ) ? $attr['options'] : 'No options found.';
			$options = explode( '/', $options );

			$html .= '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-field-checkbox' . $class . '">';
			$html .= ( ! empty( $label ) ? '<label for="pt-field-checkbox">' . esc_html($label) . ':</label>' : '' );
			$html .= '<div class="pt-checkbox-group">';


			$i = 1;
			foreach ( $options as $option ) {

				$option = trim( $option );

				$html .= '<label><input type="checkbox" id="pt-field-checkbox-' . $counter . '" name="pt-field-checkbox-' . $counter . '[]" class="pt-field pt-field-checkbox" value="' . $option;
				$html .= '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" data-parsley-multiple="checkbox-'.$ptfg_counter.'" data-parsley-errors-container=".pt-checkbox-group-errors-'.$ptfg_counter.'" " ' . $required;
				$html .= ' value="' . $option . '" >';

				$html .= $option;
				$html .= '</label>';

				$i ++;
			}

			$html .= '<div class="pt-checkbox-group-errors-'.$ptfg_counter.'"></div>'; //pt-radio-group
			$html .= '</div>'; //pt-checkbox-group
			$html .= '</div>'; //pt-form-group

			break;

		case 'select':
		case 'dropdown':

			// Paid field
			if ( ( isset( $attributes['amounts'] ) && ! empty( $attributes['amounts'] ) ) || ( isset( $attributes['options_are_amounts'] ) && 'true' == $attributes['options_are_amounts'] ) ) {
				$html .= pt_cf_dropdown( $attributes );
				break;
			}

			$ptfg_counter ++;
			$default           = $attr['default'];
			$label             = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Options';
			$options           = ( ! empty( $attr['options'] ) ) ? $attr['options'] : 'No options found.';
			$first_option      = ( ! empty( $attr['first_option'] ) ) ? $attr['first_option'] : '';
			$first_option_text = ( ! empty( $attr['first_option_text'] ) ) ? $attr['first_option_text'] : 'Maak een keuze';
			$options           = explode( '/', $options );

			$has_quantity_class = ( ! empty( $attr['quantity'] ) ) ? ' has-quantity-input' : '';

			$html .= '<div class="pt-form-group pt-form-group-' . $ptfg_counter . ' pt-form-group-field-dropdown' . $has_quantity_class . $class . '">';
			$html .= ( ! empty( $label ) ? '<label for="pt-field-dropdown">' . esc_html($label) . ':</label>' : '' );
			$html .= '<select id="pt-field-dropdown-' . $counter . '" name="pt-field-dropdown-' . $counter . '" class="pt-form-control pt-field pt-field-dropdown" value="' . $default . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" ' . $required . ' data-pt-user-data="' . $user_data . '" >';

			// Allow users to configure what the first option in an amount dropdown should be
			// If $first_option is amount, don't show any extra option in the dropdown, otherwise:
			if ( $first_option == '' ) {
				$html .= '<option hidden disabled selected value="' . __( 'Select an option', 'paytium' ) . '" selected>' . __( 'Select an option', 'paytium' ) . '</option>';
			}

			if ( $first_option == 'text' ) {
				$html .= '<option hidden disabled selected value="' . __( $first_option_text, 'paytium' ) . '" selected>' . $first_option_text . '</option>';
			}

			$i = 1;
			foreach ( $options as $option ) {

				$option = trim( $option );

				$html .= '<option';
				$html .= ( ( $required === 'true' ) ? 'required' : '' ) . ' value="' . $option . '" >';

				$html .= $option;

				$i ++;
			}

			$html .= '</select>';
			$html .= '</div>'; //pt-form-group

			break;

		case 'terms':

            $ptfg_counter++;
			$label    = ( ! empty( $attr['label'] ) ) ? $attr['label'] : 'Terms & Conditions';
			$link     = ( ! empty( $attr['link'] ) ) ? $attr['link'] : 'No link found.';

			// Overwrite required for terms, should be required by default unless set to false
			$required_terms    = ( ( $attr['required'] ) !== 'false' ) ? 'required' : '';

			$html .= '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-field-terms' . $class . '">';

			$html .= '<input type="checkbox" id="pt-field-checkbox-' . $counter . '" name="pt-field-checkbox-' . $counter . '[]" class="pt-field pt-field-checkbox" value="' . $label . '" data-pt-field-type="' . $attr['type'] . '" data-pt-user-label="' . $label . '" data-pt-user-data="' . $user_data . '" ';
			$html .= $required_terms . ' value="' . $label . '" >';

			if ( $link != 'No link found.') {
				$html .= '<label for="pt-field-checkbox-' . $counter . '">';
				$html .= '<a href="' . $link . '" target="_blank">';
				$html .= esc_html($label);
				$html .= '</a>';
				$html .= '</label>';
			} else {
				$html .= '<label for="pt-field-checkbox-' . $counter . '">';
				$html .= esc_html($label);
				$html .= '</label>';
			}

			$html .= '</div>'; //pt-form-group

			break;

        case 'file':

	        $ptfg_counter ++;

	        $allowed_mime_types_media_uploader = get_allowed_mime_types();
	        $allowed_mime_types                = '';

	        foreach ($allowed_mime_types_media_uploader as $key=>$value) {
		        $allowed_mime_types .= $value . ',';
	        }
	        $allowed_mime_types = $allowed_file_types ? $allowed_file_types : substr($allowed_mime_types, 0, -1);

	        $html .= '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-field-upload' . $class . '">';
	        $html .= '<label for="pt-paytium-uploaded-file">' . ( ! empty( $label ) ? __(esc_html($label) , 'paytium')  : __('Upload file', 'paytium'));
	        $html .= ':</label>';
	        $html .= '<input type="file" id="pt-paytium-uploaded-file" name="pt-paytium-uploaded-file[]" class="pt-paytium-uploaded-file" data-pt-field-type="pt-paytium-uploaded-file"
                        data-parsley-filemaxmegabytes="'.$max_file_size.'" data-parsley-maxfiles="'.$max_files.'" data-parsley-trigger="change" data-parsley-filemimetypes="'.$allowed_mime_types.'" multiple="multiple" '.$required.' />';
	        $html .= '</div>';

            break;

		case 'date':

			$ptfg_counter ++;

			$label = ! empty( $label ) ? __(esc_html($label) , 'paytium')  : __('Date', 'paytium');
			$date_placeholder = ( ! empty( $attr['placeholder'] ) ) ? 'placeholder="' . esc_attr( $attr['placeholder'] ) . '"' : 'placeholder="' . __('Bijvoorbeeld', 'paytium') . ': 31-12-' . date('Y', time()) . '"';

			$html .= '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-field-date">';
			$html .= '<label for="pt-field-date-'.$counter.'">' . esc_html($label) . ':</label>';
			$html .= '<input type="text" id="pt-field-date-'.$counter.'" name="pt-field-date-'.$counter.'" class="pt-paytium-date' . $class . '" data-pt-field-type="' .
					 $attr['type'] . '" data-pt-user-label="' . $label . '" autocomplete="off" '.$required.'  ' . $date_placeholder  . ' data-parsley-date data-parsley-errors-container="#parsley-errors-list-date-' . $counter . '" />';
			$html .= '<div id="parsley-errors-list-date-' . $counter . '"></div>';
			$html .= '</div>';

			break;

		case 'birthday':

			$ptfg_counter ++;

			$label = ! empty( $label ) ? __(esc_html($label) , 'paytium')  : __('Date', 'paytium');
			$date_placeholder = ( ! empty( $attr['placeholder'] ) ) ? 'placeholder="' . esc_attr( $attr['placeholder'] ) . '"' : 'placeholder="' . __('Bijvoorbeeld', 'paytium') . ': 31-12-' . date('Y', time()) . '"';

			$html .= '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-field-birthday' . $class . '">';
			$html .= '<label for="pt-field-birthday-'.$counter.'">' . esc_html($label) . ':</label>';
			$html .= '<input type="text" id="pt-field-birthday-'.$counter.'" name="pt-field-birthday-'.$counter.'" class="pt-paytium-birthday" data-pt-field-type="' .
			         $attr['type'] . '" data-pt-user-label="' . $label . '" autocomplete="off" '.$required.'  ' . $date_placeholder  . ' data-parsley-date data-parsley-errors-container="#parsley-errors-list-date-' . $counter . '" />';
			$html .= '<div id="parsley-errors-list-date-' . $counter . '"></div>';
			$html .= '</div>';

			break;

	}

	$args = pt_get_args( '', $attr, $counter );

	$counter++;

	return apply_filters( 'pt_paytium_field', $html, $args );

}
add_shortcode( 'paytium_field', 'pt_field' );

/**
 * Function to add button with different types - [paytium_button]
 *
 * @since  1.1.0
 * @author Alex Saydan
 */
function pt_button( $attributes ) {

	if(!Paytium_Shortcode_Tracker::$is_main_shortcode) {
		return;
	}

	global $counter;

	if (!empty($attributes)) {
		foreach ($attributes as $key => $attribute) {
			$attributes[$key] = esc_attr($attribute);
		}
	}

	$attr = shortcode_atts(array(
		'label' => get_option('pt_paytium_button', ''),
		'class' => 'paytium-button-el',
		'style' => '',
	), $attributes, 'paytium_button');

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_button_' . $counter, 'paytium_button', $attr, false );

	$html = '';

	$args = pt_get_args( '', $attr, $counter );

	// Add a filter here to allow developers to hook into the form
	$filter_html = '';
	$html .= apply_filters( 'pt_before_payment_button', $filter_html );

	// Payment button.
	$html .= '<button class="pt-payment-btn ' . $class . '"' . (!empty($style) ? ' style="' . $style . '" ' : '') . '><span>' . esc_html($label) . '</span></button>';
	$counter++;

	return apply_filters( 'pt_paytium_button', $html, $args );

}
add_shortcode( 'paytium_button', 'pt_button' );

/**
 * Function to add the custom user amount textbox via shortcode - [paytium_amount]
 *
 * @since 1.0.0
 */
function pt_uea_amount( $attr ) {

	if(!Paytium_Shortcode_Tracker::$is_main_shortcode) {
		return;
	}

	global $counter, $ptfg_counter, $limit_data, $pt_id, $form_currency;
    $ptfg_counter++;

	$currency = is_file( PT_PATH . 'features/currency.php' ) ? get_paytium_currency_symbol($form_currency[$pt_id]) : '€';
	$currency_symbol_after = $currency == 'NOK' || $currency == 'SEK' || $currency == 'fr.';

	if (!empty($attr)) {
		foreach ($attr as $key => $attribute) {
			$attr[$key] = esc_attr($attribute);
		}
	}

	$attr = shortcode_atts( array (
        'label'            => get_option('pt_uea_label', ''),
        'placeholder'      => '',
        'default'          => '',
        'tax'              => '',
        'quantity'         => '',
        'quantity_min'     => '',
        'quantity_max'     => '',
        'quantity_step'    => '',
        'limit'            => '',
        'limit_message'    => '',
        'item_id'          => '',
		'general_limit'    => '',
		'general_item_id'  => '',
		'show_items_left'  => true,
		'show_items_left_after' => 10,
		'show_items_left_only_for_admin' => '',
        'minimum'          => '',
		'crowdfunding_id'  => '',
	), $attr, 'paytium_amount' );

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_amount_' . $counter, 'paytium_amount', $attr, false );

	$currency_class = $currency_symbol_after ? ' currency-after-amount' : '';

	$html = '';
	$html .= ( ! empty( $label ) ? '<label for="pt_uea_custom_amount_' . $counter . '">' . esc_html($label) . '</label>' : '' );
	$html .= '<div class="pt-uea-container pt-uea-container-with-prepend"><div class="pt-uea-container-amount">';
	$html .= '<div class="pt-uea-currency-prepend'.$currency_class.'">';
	$html .= '<span class="pt-uea-currency pt-uea-currency-before">'.$currency.'</span> ';
	$html .= '</div> ';

	$id = 'pt_items[' . absint( $counter ) . ']';

	// Include inline Parsley JS validation data attributes.
	// http://parsleyjs.org/doc/index.html#psly-validators-list
	$html .= '<input type="text" class="pt-field pt-uea-custom-amount" autocomplete="off" name="' . $id . '[amount]" ';
    if (isset($minimum) && $minimum != '') {

	    // Collect all amounts so we can store them later for server side validation
	    pt_paytium_collect_amounts( $minimum );

        $html .= 'id="pt_uea_custom_amount_' . $counter . '" value="' . esc_attr($default) . '" parsley-type="number" data-parsley-open="' . $minimum . '" placeholder="' . esc_attr($placeholder) . '" ';
    }
    else {

	    // Collect all amounts so we can store them later for server side validation
	    pt_paytium_collect_amounts( '0.99' );

        $html .= 'id="pt_uea_custom_amount_' . $counter . '" value="' . esc_attr($default) . '" parsley-type="number"  data-parsley-open="1" placeholder="' . esc_attr($placeholder) . '" ';
    }
	// Point to custom container for errors so we can place the non-USD currencies on the right of the input box.
	$html .= 'data-parsley-errors-container="#pt_uea_custom_amount_errors_' . $counter . '" data-pt-price="' . esc_attr($default) . '">';
    $html .= '</div>';

	$paytium_item_limits = unserialize(get_option('paytium_item_limits'));
	if (!empty($paytium_item_limits) && !empty($limit) && !empty($item_id)) {
		if ( isset($paytium_item_limits[sanitize_key($item_id)])) {
			$items_left = (int)$limit - $paytium_item_limits[sanitize_key($item_id)];
		} else {
			$items_left = null;
		}

		if (!empty($quantity_max) && $quantity_max > $items_left) $quantity_max = $items_left;
	}

    $has_quantity_class = '';
    if(!empty($quantity)) {

	    $has_quantity_class = 'has-quantity-input';

        $attributes = '';
        $attributes .= (!empty($quantity_min) ? 'min="' . $quantity_min . '" ' : '');
        $attributes .= (!empty($quantity_max) ? 'max="' . $quantity_max . '" ' : '');
        $attributes .= (!empty($quantity_step) ? 'step="' . $quantity_step . '" ' : '');
        $default_value = (!empty($quantity_min) ? 'value="' . $quantity_min . '" ' : 'value=1');
		$quantity_html = '<div class="paytium-quantity-input">'.
			'<button type="button" class="paytium-spinner decrement">-</button>'.
			'<input type="number" ' . $attributes . ' class="pt-quantity-input" name="' . $id . '[quantity]" ' . $default_value . '  >'.
			'<button type="button" class="paytium-spinner increment">+</button>'.
			'</div>';
		$html .= $quantity_html;
    }

	$html .= '<input type="hidden" class="pt-field pt-uea-custom-amount-formatted" name="' . esc_attr( $id ) . '[amount]" value="' . esc_attr(  pt_user_amount_to_float($default) ). '" data-pt-price="' . esc_attr( pt_user_amount_to_float($default) ) . '" />';
	$html .= '<input type="hidden" name="' . $id . '[label]" value="' . wp_kses_post( $attr['label'] ) . '">';
	$html .= '<input type="hidden" name="' . $id . '[tax_percentage]" value="' . floatval( $attr['tax'] ) . '">';
	$html .= '<input type="hidden" name="' . $id . '[type]" value="open">';

	if (!empty($crowdfunding_id)) {
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][crowdfunding_id]" value="' . $crowdfunding_id . '" data-pt-user-label="Crowdfunding ID" />';
	}

    if (!empty($limit) && !empty($limit_message) && !empty($item_id)) {

	    $html .= '<input type="hidden" name="pt_items[' . $counter . '][item_id]" value="' . $item_id . '" data-pt-user-label="Item ID" />';
	    $html .= '<input type="hidden" name="pt_items[' . $counter . '][limit]" value="' . $limit . '" data-pt-user-label="Limit" />';
	    $html .= '<input type="hidden" name="pt_items[' . $counter . '][limit-message]" value="' . $limit_message . '" data-pt-user-label="Limit Message" />';

        $limit_data['limits'][$item_id] = 0;

        if (get_option('paytium_item_limits')) {

            $paytium_item_limits = unserialize(get_option('paytium_item_limits'));

            if (array_key_exists(sanitize_key($item_id), $paytium_item_limits) && $paytium_item_limits[sanitize_key($item_id)] >= (int)$limit) {

                $limit_data['limits'][$item_id] = $limit_message;
                $html = '<div class="pt-form-alert">' . __($limit_message, 'paytium') . '</div>';

            } else {
	            $html .= '</div>';
                $html .= '<div id="pt_uea_custom_amount_errors_' . $counter . '"></div>';

				$show_items_left_only_for_admin = filter_var($show_items_left_only_for_admin, FILTER_VALIDATE_BOOLEAN);

				if ( $show_items_left !== 'false' && isset($items_left) && $items_left < $show_items_left_after &&
					(($show_items_left_only_for_admin && current_user_can('administrator')) || (!$show_items_left_only_for_admin))) {

					$html .= '<p class="pt-items-left">'.sprintf(__('Only %s left!','paytium'), $items_left).'</p>';
				}
            }
        }
        else {
			$html .= '</div>';
			$html .= '<div id="pt_uea_custom_amount_errors_' . $counter . '"></div>';
		}

		if (function_exists('pt_paytium_general_limit_reached') && !empty($general_item_id) && !empty($general_limit)) {
			$html .= '<input type="hidden" name="pt_items[' . $counter . '][general_limit]" value="' . $general_limit . '" data-pt-user-label="General Limit" />';
			$html .= '<input type="hidden" name="pt_items[' . $counter . '][general_item_id]" value="' . $general_item_id . '" data-pt-user-label="General Item ID" />';
			if ($paytium_item_limits && $paytium_item_limits && pt_paytium_general_limit_reached($general_item_id,$general_limit,$paytium_item_limits)) {
				$html = '';
			}
		}
    }
    else {
	    // Custom validation errors container for UEA.
	    // Needs counter ID specificity to match input above.
	    $html .= '</div>'; //pt-uea-container
	    $html .= '<div id="pt_uea_custom_amount_errors_' . $counter . '"></div>';
    }

	if (function_exists('pt_paytium_general_limit_reached') && !empty($general_item_id) && !empty($general_limit)) {
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][general_limit]" value="' . $general_limit . '" data-pt-user-label="General Limit" />';
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][general_item_id]" value="' . $general_item_id . '" data-pt-user-label="General Item ID" />';
		if (empty($limit) && !empty($item_id)) {
			$html .= '<input type="hidden" name="pt_items[' . $counter . '][item_id]" value="' . $item_id . '" data-pt-user-label="Item ID" />';
		}
		if ($paytium_item_limits && pt_paytium_general_limit_reached($general_item_id,$general_limit,$paytium_item_limits)) {
			$html = '';
		}
	}

	$args = pt_get_args( '', $attr, $counter );
	$counter++;

	return '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-uea-custom-amount '. $has_quantity_class . '">' . apply_filters( 'pt_paytium_amount', $html, $args ) . '</div>';

}
add_shortcode( 'paytium_amount', 'pt_uea_amount' );


/**
 * Shortcode to output a dropdown list - [paytium_dropdown]
 *
 * @since 1.0.0
 */
function pt_cf_dropdown( $attr ) {

	if(!Paytium_Shortcode_Tracker::$is_main_shortcode) {
		return;
	}

    global $counter, $ptfg_counter, $limit_data, $pt_id, $form_currency;
	$ptfg_counter++;

	$currency = is_file( PT_PATH . 'features/currency.php' ) ? get_paytium_currency_symbol($form_currency[$pt_id]) : '€';
	$currency_symbol_after = $currency == 'NOK' || $currency == 'SEK' || $currency == 'fr.';

	global $pt_script_options;

	if (!empty($attr)) {
		foreach ($attr as $key => $attribute) {
			$attr[$key] = esc_attr($attribute);
		}
	}

	$attr = shortcode_atts( array (
        'label'                  => '',
        'default'                => '',
        'options'                => '',
        'required'               => '',
		'options_are_quantities' => 'false',
        'amounts'                => '',
        'options_are_amounts'    => 'false', // For backwards compatibility
        'first_option'           => '',
        'first_option_text'      => '',
        'tax'                    => '',
        'quantity'               => '',
        'quantity_min'           => '',
        'quantity_max'           => '',
        'quantity_step'          => '',
        'limit'                  => '',
        'limit_message'          => '',
        'item_id'                => '',
		'general_limit'             => '',
		'general_item_id'           => '',
		'show_items_left' => true,
		'show_items_left_after' => 10,
		'show_items_left_only_for_admin' => '',
		'crowdfunding_id' 		 => '',
	), $attr, 'paytium_dropdown' );

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_dropdown_' . $counter, 'paytium_dropdown', $attr, false );

	$id = 'pt_items[' . absint( $counter ) . ']';

	$required = ( ( $attr['required'] ) == 'true' ) ? 'required' : '';

	$quantity_html  = ( ( 'true' == $options_are_quantities ) ? 'data-pt-quantity="true" ' : '' );
	$quantity_class = ( ( 'true' == $options_are_quantities ) ? ' pt-cf-quantity' : '' );

	$amount_class = ( ( ! $amounts == false || $options_are_amounts == 'true' ) ? ' pt-cf-amount' : '' );

	$options = explode( '/', $options );

    if (!empty($limit) && !empty($limit_message) && !empty($item_id)) {
        $item_ids = explode('/', $item_id);
        $item_limits = explode('/', $limit);
        $show_items_left_after_arr = explode('/', $show_items_left_after);

        if (count($options) != count($item_ids)) {
            Paytium_Shortcode_Tracker::update_error_count();

            if (current_user_can('manage_options')) {
                Paytium_Shortcode_Tracker::add_error_message('<h6>' . __('Your number of options and item_ids are not equal.', 'paytium') . '</h6>');
            }

            return '';
        }
        if (count($options) != count($item_limits)) {
            Paytium_Shortcode_Tracker::update_error_count();

            if (current_user_can('manage_options')) {
                Paytium_Shortcode_Tracker::add_error_message('<h6>' . __('Your number of options and limits are not equal.', 'paytium') . '</h6>');
            }

            return '';
        }
    }

	if (is_file( PT_PATH . 'features/general-limit.php' ) && !empty($general_item_id) && !empty($general_limit)) {

		$general_item_ids = explode('/', $general_item_id);
		$general_limits = explode('/', $general_limit);

		if (count($options) != count($general_item_ids)) {
			Paytium_Shortcode_Tracker::update_error_count();

			if (current_user_can('manage_options')) {
				Paytium_Shortcode_Tracker::add_error_message('<h6>' . __('Your number of options and general_item_ids are not equal.', 'paytium') . '</h6>');
			}

			return '';
		}
		if (count($options) != count($general_limits)) {
			Paytium_Shortcode_Tracker::update_error_count();

			if (current_user_can('manage_options')) {
				Paytium_Shortcode_Tracker::add_error_message('<h6>' . __('Your number of options and general_limits are not equal.', 'paytium') . '</h6>');
			}

			return '';
		}

		if (empty($limit) && !empty($item_id)) {
			$item_ids = explode('/', $item_id);
			if (count($options) != count($item_ids)) {
				Paytium_Shortcode_Tracker::update_error_count();

				if (current_user_can('manage_options')) {
					Paytium_Shortcode_Tracker::add_error_message('<h6>' . __('Your number of options and item_ids are not equal.', 'paytium') . '</h6>');
				}

				return '';
			}
		}
	}

    if (!empty($amounts)) {
        $amounts = explode('/', $amounts);
    }

	if ( ! empty( $amounts ) ) {

		if ( count( $options ) != count( $amounts ) ) {
			Paytium_Shortcode_Tracker::update_error_count();

			if ( current_user_can( 'manage_options' ) ) {
				Paytium_Shortcode_Tracker::add_error_message( '<h6>' . __( 'Your number of options and amounts are not equal.', 'paytium' ) . '</h6>' );
			}

			return '';
		}
	}

	$general_limit_class = !empty($general_item_ids) && !empty($general_limits) && empty($item_limits) && empty($quantity) ? ' general-limit-item' : '';
	$html = ( ! empty( $label ) ? '<label id="pt-cf-dropdown-label" for="' . esc_attr( $id ) . '">' . esc_html($label) . ':</label>' : '' );
	$html .= '<select class="pt-form-control pt-cf-dropdown' . $quantity_class . $amount_class . $general_limit_class . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '[amount]" ' . $quantity_html . ' ' . $required . '>';

	// Allow users to configure what the first option in an amount dropdown should be
	$first_option_text  = ( ( $first_option_text != '' ) ? $first_option_text : 'Select an amount' );

	// If $first_option is amount, don't show any extra option in the dropdown, otherwise:

	if ( $first_option == '' ) {
		$html .= '<option hidden disabled selected value="' . __( 'Select an amount', 'paytium' ) . '" selected>' . __( 'Select an amount', 'paytium' ) . '</option>';
	}

	if ( $first_option == 'text' ) {
		$html .= '<option hidden disabled selected value="' . __( $first_option_text, 'paytium' ) . '" selected>' . $first_option_text . '</option>';
	}

    $i = $k = $g = 1;
    foreach ($options as $option) {

		$option = trim( $option );
		$value  = $option;

		if ( $options_are_amounts == 'true' ) {
			$amount = $option;
			$option_name = $currency . ' ' . $amount;
			$value = pt_user_amount_to_float( $value );

			// Collect all amounts so we can store them later for server side validation
			pt_paytium_collect_amounts( $amount );

		} elseif ( ! empty( $amounts ) ) {
			$amount = $amounts[ $i - 1 ];
			$option_name = $option . ' - '. $currency_symbol_after ? $amount . ' ' . $currency : $currency . ' ' . $amount;
			$value = pt_user_amount_to_float( $amount );
		}

		if ( empty( $default ) ) {
			$default = $option;
		}

		if ( $default == $option && $options_are_quantities != 'true' && ! empty( $amounts ) ) {
			$pt_script_options['script']['amount'] = $value;
		}

        if (isset($item_ids) && isset($item_limits)) {

            $paytium_item_limits = unserialize(get_option('paytium_item_limits'));

            if (get_option('paytium_item_limits') && array_key_exists(sanitize_key($item_ids[$i - 1]), $paytium_item_limits)
                && $paytium_item_limits[sanitize_key($item_ids[$i - 1])] >= (int)$item_limits[$i - 1]) {

				if (!empty($general_item_ids) && !empty($general_limits) &&
					function_exists('pt_paytium_general_limit_reached') && pt_paytium_general_limit_reached($general_item_ids[$i - 1],$general_limits[$i - 1],$paytium_item_limits)) {
					$g++;
				}
				else {
					$html .= '<option class="pt-option-disabled" value="' . $value . '" data-pt-price="' . esc_attr($value) . '" disabled>' . (isset($option_name) ? $option_name : $option) . ' (' . __($limit_message, 'paytium') . ')</option>';
				}

                $k++;
            } else {

	            if ( isset($paytium_item_limits[sanitize_key($item_ids[$i - 1])])) {
		            $items_left      = (int) $item_limits[ $i - 1 ] - $paytium_item_limits[ sanitize_key( $item_ids[ $i - 1 ] ) ];

					$show_items_left_after = is_array($show_items_left_after_arr) && isset($show_items_left_after_arr[$i - 1]) ? $show_items_left_after_arr[$i - 1] : 10;
					$show_items_left_only_for_admin = filter_var($show_items_left_only_for_admin, FILTER_VALIDATE_BOOLEAN);

		            $items_left_text = $show_items_left !== 'false' && isset($items_left) && $items_left < $show_items_left_after &&
										(($show_items_left_only_for_admin && current_user_can('administrator')) || (!$show_items_left_only_for_admin)) ?
										'(' . sprintf(__('Only %s left!','paytium'), $items_left) . ')' :
										'';
	            } else {
	            	$items_left_text = '';
	            }

				$general_limit_html_data = isset($general_item_ids,$general_limits) ? 'data-general_item_id="' . $general_item_ids[$i - 1] . '" data-general_limit="' . $general_limits[$i - 1] . '"' : '';
                $html .= '<option value="' . $value . '" data-pt-price="' . esc_attr($value) . '" data-item_id="' . $item_ids[$i - 1] . '" data-limit="' . $item_limits[$i - 1] . '" '.$general_limit_html_data.'>' . (isset($option_name) ? $option_name : $option) . ' ' . $items_left_text . '</option>';

				if (!empty($quantity_max) && $quantity_max > $items_left) $quantity_max = $items_left;
            }
        }
		elseif (!empty($general_item_ids) && !empty($general_limits) && empty($item_limits)) {

			$general_limit_html_data = isset($general_item_ids,$general_limits) ? ' data-general_item_id="' . $general_item_ids[$i - 1] . '" data-general_limit="' . $general_limits[$i - 1] . '"' : '';
			$item_id = !empty($item_ids) && isset($item_ids[$i - 1]) ? ' data-item_id="' . $item_ids[$i - 1].'"' : '';

			$html .= '<option value="' . $value . '" data-pt-price="' . esc_attr($value) . '" '. $item_id . $general_limit_html_data.'>' . (isset($option_name) ? $option_name : $option) . '</option>';

			if ( isset($paytium_item_limits[sanitize_key($general_item_ids[$i - 1])])) {
				$items_left = (int)$general_limits[$i - 1] - $paytium_item_limits[sanitize_key($general_item_ids[$i - 1])];
			} else {
				$items_left = $general_limits[$i - 1];
			}
			$general_items_left = $items_left;

			if (!empty($quantity_max) && $quantity_max > $items_left) $quantity_max = $items_left;
		}
        else {
            $html .= '<option value="' . $value . '" data-pt-price="' . esc_attr($value) . '">' . (isset($option_name) ? $option_name : $option) . '</option>';
        }

        $i++;
    }

	$html .= '</select>';

	$has_quantity_class = '';
    if(!empty($quantity)) {

	    $has_quantity_class = 'has-quantity-input';

        $attributes = '';
        $attributes .= (!empty($quantity_min) ? 'min="' . $quantity_min . '" ' : '');
        $attributes .= (!empty($quantity_max) ? 'max="' . $quantity_max . '" ' : '');
        $attributes .= (!empty($quantity_step) ? 'step="' . $quantity_step . '" ' : '');
        $default_value = (!empty($quantity_min) ? 'value="' . $quantity_min . '" ' : 'value=1');
		$limit_class = isset($general_items_left) ? ' general-limit-qty' : '';
		$quantity_html = '<div class="paytium-quantity-input">'.
			'<button type="button" class="paytium-spinner decrement">-</button>'.
			'<input type="number" ' . $attributes . ' class="pt-quantity-input'.$limit_class.'" name="' . $id . '[quantity]" ' . $default_value . '  >'.
			'<button type="button" class="paytium-spinner increment">+</button>'.
			'</div>';
		$html .= $quantity_html;
    }
	$html .= '<input type="hidden" name="' . $id . '[label]" value="' . wp_kses_post( $attr['label'] ) . '" data-pt-original-label="' . wp_kses_post( $attr['label'] ) . '">';
	$html .= '<input type="hidden" name="' . $id . '[value]" value="">';
	$html .= '<input type="hidden" name="' . $id . '[tax_percentage]" value="' . floatval( $attr['tax'] ) . '">';
	$html .= '<input type="hidden" name="' . $id . '[type]" value="select">';

	if (!empty($limit) && !empty($limit_message) && !empty($item_id)) {
		$html .= '<input type="hidden" name="' . $id . '[item_id]" value="" data-pt-user-label="Item ID" />';
		$html .= '<input type="hidden" name="' . $id . '[limit]" value="" data-pt-user-label="Limit" />';
		$html .= '<input type="hidden" name="' . $id . '[limit-message]" value="' . $limit_message . '" data-pt-user-label="Limit Message" />';
	}

	if (!empty($general_limit) && !empty($general_item_id)) {
		$html .= '<input type="hidden" name="' . $id . '[general_item_id]" value="" data-pt-user-label="General Item ID" />';
		$html .= '<input type="hidden" name="' . $id . '[general_limit]" value="" data-pt-user-label="General Limit" />';
		if (empty($limit) && !empty($item_id)) {
			$html .= '<input type="hidden" name="' . $id . '[item_id]" value="" data-pt-user-label="Item ID" />';
		}
	}

	if (!empty($crowdfunding_id)) {
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][crowdfunding_id]" value="' . $crowdfunding_id . '" data-pt-user-label="Crowdfunding ID" />';
	}

    $args = pt_get_args($id, $attr, $counter);

	$counter++;

	if ($i == $k && $i != $g) {
        $limit_data['limits'][$item_id] = $limit_message;
        if ($options_are_amounts == 'true' || !empty($amounts)) {
            if(isset($limit_data['amount_count'])) {
                $limit_data['amount_count']++;
            }
            else $limit_data['amount_count'] = 1;
        }
        $html = '<div class="pt-form-alert">' . __($limit_message, 'paytium') . '</div>';
    }
	elseif ($i == $g) {
		$html = '';
	}

    return '<div class="pt-form-group pt-form-group-' . $ptfg_counter . ' pt-form-group-dropdown ' . $has_quantity_class . '">' . apply_filters('pt_paytium_dropdown', $html, $args) . '</div>';

}
add_shortcode( 'paytium_dropdown', 'pt_cf_dropdown' );

/**
 * Shortcode to set subscription details - [paytium_subscription /]
 *
 * @since 1.3.0
 */
function pt_subscription( $attr ) {

	if(!Paytium_Shortcode_Tracker::$is_main_shortcode) {
		return;
	}

	global $counter, $ptfg_counter;
	$ptfg_counter++;

	if (!empty($attr)) {
		foreach ($attr as $key => $attribute) {
			$attr[$key] = esc_attr($attribute);
		}
	}

	$attr = shortcode_atts( array (
		'interval'            => '',
		'interval_options'    => '',
		'interval_label'      => '',
		'interval_amounts'    => '',
		'times'               => '',
		'first_payment'       => '',
		'first_payment_tax'   => '',
		'first_payment_label' => '',
		'optional'            => '',
		'optional_label'      => '',
		'tax'                 => '',
		'start_date'          => '',
	), $attr, 'paytium_subscription' );

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_subscription_' . $counter, 'paytium_subscription', $attr, false );

	$interval                     = ( ! empty( $attr['interval'] ) ) ? $attr['interval'] : '';
	$interval_options             = ( ! empty( $attr['interval_options'] ) ) ? explode( ',', $attr['interval_options'] ) : '';
	$interval_label               = ( ! empty( $attr['interval_label'] ) ) ? $attr['interval_label'] : __( 'Select a subscription interval', 'paytium' );
	$interval_amounts             = ( ! empty( $attr['interval_amounts'] ) ) ? $attr['interval_amounts'] : '';
	$times                        = ( ! empty( $attr['times'] ) ) ? $attr['times'] : '';
	$first_payment                = ( ! empty( $attr['first_payment'] ) ) ? $attr['first_payment'] : '';
	$first_payment_tax_percentage = ( ! empty( $attr['first_payment_tax'] ) ) ? $attr['first_payment_tax'] : '';
	$first_payment_label          = ( ! empty( $attr['first_payment_label'] ) ) ? $attr['first_payment_label'] : '';
	$optional                     = ( ! empty( $attr['optional'] ) ) ? $attr['optional'] : '';
	$optional_label               = ( ! empty( $attr['optional_label'] ) ) ? $attr['optional_label'] : __( 'Do you want a recurring payment?', 'paytium' );
	$tax                          = ( ! empty( $attr['tax'] ) ) ? $attr['tax'] : '';
	$start_date                   = ( ! empty( $attr['start_date'] ) ) ? $attr['start_date'] : '';


	$html = '<input type="hidden" id="pt-subscription-interval" name="pt-subscription-interval" class="pt-subscription-interval" value="' . $interval . '" data-pt-field-type="pt-subscription-interval" />';
	$html .= '<input type="hidden" id="pt-subscription-times" name="pt-subscription-times" class="pt-subscription-times" value="' . $times . '" data-pt-field-type="pt-subscription-times" />';

	if ( !empty($first_payment) ) {
		// Collect all amounts so we can store them later for server side validation
		pt_paytium_collect_amounts( $first_payment );

		$html .= '<input type="hidden" id="pt-subscription-first-payment" name="pt-subscription-first-payment" class="pt-subscription-first-payment" value="' . $first_payment . '" data-pt-field-type="pt-subscription-first-payment" />';
	}

	if ( !empty($first_payment_tax_percentage) ) {
		$html .= '<input type="hidden" id="pt-subscription-first-payment-tax-percentage" name="pt-subscription-first-payment-tax-percentage" class="pt-subscription-first-payment-tax-percentage" value="' . $first_payment_tax_percentage . '" data-pt-field-type="pt-subscription-first-payment-tax-percentage" />';
	}

	if ( !empty($first_payment_label) ) {
		$html .= '<input type="hidden" id="pt-subscription-first-payment-label" name="pt-subscription-first-payment-label" class="pt-subscription-first-payment-label" value="' . $first_payment_label . '" data-pt-field-type="pt-subscription-first-payment-label" />';
	}

	// Allow customers to choose if they want a recurring payment or not
	if ( $optional ) {

		$html .= '<div class="pt-form-group pt-form-group-' . $ptfg_counter . ' pt-form-group-subscription-optional">';

		$html .= ( ! empty( $optional_label ) ? '<label id="pt-cf-radio-label pt-subscription-optional">' . $optional_label . '</label>' : '' );
		$html .= '<div class="pt-radio-group pt-subscription-optional">';

		$option_html = '';

		$option_html .= '<label title="' . esc_attr( 'Yes' ) . '" >';
		// Don't use built-in checked() function here for now since we need "checked" in double quotes.
		$option_html .= '<input type="radio" name="pt-subscription-optional" value="yes" ' . '" checked="checked" ' .
		'class="pt-subscription-optional" data-parsley-errors-container=".pt-form-group">';
		$option_html .= '<span>' . __('Yes') . '</span>';
		$option_html .= '</label>';

		$option_html .= '<label title="' . esc_attr( 'No' ) . '" >';
		// Don't use built-in checked() function here for now since we need "checked" in double quotes.
		$option_html .= '<input type="radio" name="pt-subscription-optional" value="no" ' . '" ' .
		                ' class="pt-subscription-optional" data-parsley-errors-container=".pt-form-group">';
		$option_html .= '<span>' . __('No') . '</span>';
		$option_html .= '</label>';

		$html .= $option_html;

		$html .= '</div>'; //pt-radio-group
		$html .= '</div>'; //pt-form-group

	}

	// Process interval options if any are found
	if ( ! empty( $interval_options ) ) {

		$html .= '<div class="pt-form-group pt-form-group-' . $ptfg_counter . ' pt-form-group-subscription-interval">';

		$html .= ( ! empty( $interval_label ) ? '<label id="pt-cf-radio-label pt-subscription-interval-options-label">' . $interval_label . ':</label>' : '' );
		$html .= '<div class="pt-radio-group pt-subscription-interval-options">';

		$interval_amounts = explode( '/', $interval_amounts );

		$amounts_counter = 0;
		$option_html     = '';
		foreach ( $interval_options as $option ) {

			$option      = trim( $option );
			$value       = $option;
			$option_html .= '<label title="' . esc_attr( $option ) . '" >';

			// If there is no interval amount for this option, stop processing
			// Disabled this, so users can also allow customers to select an own interval with custom amount
			//if ( ! isset( $interval_amounts[ $amounts_counter ] ) ) {
			//	continue;
			//}

			// Collect all amounts so we can store them later for server side validation
			pt_paytium_collect_amounts( $interval_amounts[ $amounts_counter ] );


			// Convert interval options to nicer "human friendly" versions, e.g. 1 month becomes Monthly
			if ( ( $option == 'once' || $option == 'eenmalig' ) ) {
				$option = __( 'Once', 'paytium' );
			} elseif ( $option == '1 months' ) {
				$option = __( 'Monthly', 'paytium' );
			} elseif ( $option == '12 months' ) {
				$option = __( 'Yearly', 'paytium' );
			} elseif ( ( $option !== 'once' && $option !== 'eenmalig' ) ) {
				$option_split = explode( ' ', $option );
				$option       = sprintf( _n( '%s ' . trim( $option_split[1], 's' ), '%s ' . $option_split[1], $option_split[0] ), $option_split[0] );
			}

			// Don't use built-in checked() function here for now since we need "checked" in double quotes.
			$option_html .= '<input type="radio" name="pt-subscription-interval-options" value="' . $value . '" data-pt-label="' . $option . '" data-pt-price="' . pt_user_amount_to_float( $interval_amounts[ $amounts_counter ] ) . '"' . checked( $interval, $option, false ) .
			                ' class="pt-subscription-interval-options" data-parsley-errors-container=".pt-form-group" required>';

			$option_html .= '<span>' . $option . '</span>';
			$option_html .= '</label>';

			$amounts_counter ++;
			continue;

		}

		$html .= $option_html;

		$html .= '</div>'; //pt-radio-group
		$html .= '</div>'; //pt-form-group

		$html .= '<input type="hidden" class="pt-subscription-interval-options-list" name="pt-subscription-interval-options-list" value="'.$attr['interval_options'].'">';
	}

	// If there are interval amounts, add fields where we shall store the selected data as payment items
	if ( ! empty( $interval_amounts ) ) {
		$html .= '<input type="hidden" id="pt-subscription-custom-amount" name="pt-subscription-custom-amount" class="pt-subscription-custom-amount pt-paid-field" value="" data-pt-field-type="pt-subscription-custom-amount" data-pt-price="" />';
		$html .= '<input type="hidden" id="pt-subscription-custom-value" class="pt-subscription-custom-amount" name="pt_items[' . $counter . '][value]" value="' . $interval_amounts[0] . '" disabled>';
		$html .= '<input type="hidden" class="pt-subscription-custom-amount" name="pt_items[' . $counter . '][amount]" value="' . pt_user_amount_to_float( $interval_amounts[0] ) . '" disabled>';
		$html .= '<input type="hidden" class="pt-subscription-custom-amount" name="pt_items[' . $counter . '][tax_percentage]" value="' . floatval( $tax ) . '" disabled>';
		$html .= '<input type="hidden" class="pt-subscription-custom-amount" name="pt_items[' . $counter . '][type]" value="label" disabled>';

		$html .= '<input type="hidden" class="pt-subscription-interval-amounts-list" name="pt-subscription-interval-amounts-list" value="'.$attr['interval_amounts'].'">';
	}

	// Add a optional start date
	if ( $start_date ) {
		$html .= '<input type="hidden" id="pt-subscription-start-date" name="pt-subscription-start-date" class="pt-subscription-start-date" value="' . $start_date . '" data-pt-field-type="pt-subscription-start-date" />';
	}

	$args = pt_get_args( '', $attr, $counter );

	$counter++; // Increment static counter

	return apply_filters( 'pt_subscription', $html, $args );

}
add_shortcode( 'paytium_subscription', 'pt_subscription' );

/**
 * Shortcode to output radio buttons - [paytium_radio]
 *
 * @since 1.0.0
 */
function pt_cf_radio( $attr ) {

	if(!Paytium_Shortcode_Tracker::$is_main_shortcode) {
		return;
	}

    global $counter, $ptfg_counter, $limit_data, $pt_id, $form_currency;
	$ptfg_counter++;

	if (!empty($attr)) {
		foreach ($attr as $key => $attribute) {
			$attr[$key] = esc_attr($attribute);
		}
	}

	$currency = is_file( PT_PATH . 'features/currency.php' ) ? get_paytium_currency_symbol($form_currency[$pt_id]) : '€';
	$currency_symbol_after = $currency == 'NOK' || $currency == 'SEK' || $currency == 'fr.';

	$attr = shortcode_atts(array(
		'id'                     => '',
		'label'                  => '',
		'default'                => '',
		'options'                => '',
		'options_are_quantities' => 'false',
		'amounts'                => '',
		'options_are_amounts'    => 'false',
		'tax'                    => '',
		'quantity'               => '',
		'quantity_min'           => '',
		'quantity_max'           => '',
		'quantity_step'          => '',
		'limit'                  => '',
		'limit_message'          => '',
		'item_id'                => '',
		'general_limit'          => '',
		'general_item_id'        => '',
		'show_items_left'        => true,
		'show_items_left_after'  => 10,
		'show_items_left_only_for_admin' => '',
		'crowdfunding_id'		 => '',
	), $attr, 'paytium_radio');

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_radio_' . $counter, 'paytium_radio', $attr, false );

	$id = 'pt_items[' . absint( $counter ) . ']';

	$options = explode( '/', $options );

    if (!empty($limit) && !empty($limit_message) && !empty($item_id)) {
        $item_ids = explode('/', $item_id);
        $item_limits = explode('/', $limit);
		$show_items_left_after_arr = explode('/', $show_items_left_after);

        if (count($options) != count($item_ids)) {
            Paytium_Shortcode_Tracker::update_error_count();

            if (current_user_can('manage_options')) {
                Paytium_Shortcode_Tracker::add_error_message('<h6>' . __('Your number of options and item_ids are not equal.', 'paytium') . '</h6>');
            }

            return '';
        }
        if (count($options) != count($item_limits)) {
            Paytium_Shortcode_Tracker::update_error_count();

            if (current_user_can('manage_options')) {
                Paytium_Shortcode_Tracker::add_error_message('<h6>' . __('Your number of options and limits are not equal.', 'paytium') . '</h6>');
            }

            return '';
        }
    }

	if (is_file( PT_PATH . 'features/general-limit.php' ) && !empty($general_item_id) && !empty($general_limit)) {

		$general_item_ids = explode('/', $general_item_id);
		$general_limits = explode('/', $general_limit);

		if (count($options) != count($general_item_ids)) {
			Paytium_Shortcode_Tracker::update_error_count();

			if (current_user_can('manage_options')) {
				Paytium_Shortcode_Tracker::add_error_message('<h6>' . __('Your number of options and general_item_ids are not equal.', 'paytium') . '</h6>');
			}

			return '';
		}
		if (count($options) != count($general_limits)) {
			Paytium_Shortcode_Tracker::update_error_count();

			if (current_user_can('manage_options')) {
				Paytium_Shortcode_Tracker::add_error_message('<h6>' . __('Your number of options and general_limits are not equal.', 'paytium') . '</h6>');
			}

			return '';
		}

		if (empty($limit) && !empty($item_id)) {
			$item_ids = explode('/', $item_id);
			if (count($options) != count($item_ids)) {
				Paytium_Shortcode_Tracker::update_error_count();

				if (current_user_can('manage_options')) {
					Paytium_Shortcode_Tracker::add_error_message('<h6>' . __('Your number of options and item_ids are not equal.', 'paytium') . '</h6>');
				}

				return '';
			}
		}
	}

    if (!empty($amounts)) {
        $amounts = explode('/', str_replace(' ', '', $amounts));//

		if ( count( $options ) != count( $amounts ) ) {
			Paytium_Shortcode_Tracker::update_error_count();

			if ( current_user_can( 'manage_options' ) ) {
				Paytium_Shortcode_Tracker::add_error_message( '<h6>' . __( 'Your number of options and amounts are not equal.', 'paytium' ) . '</h6>' );
			}

			return '';
		}
	}

	$quantity_html  = ( ( 'true' == $options_are_quantities ) ? 'data-pt-quantity="true" ' : '' );
	$quantity_class = ( ( 'true' == $options_are_quantities ) ? ' pt-cf-quantity' : '' );
	$amount_class = ( ( ! $amounts == false || $options_are_amounts == 'true' ) ? ' pt-cf-amount' : '' );

	$html = ( ! empty( $label ) ? '<label id="pt-cf-radio-label">' . esc_html($label) . ':</label>' : '' );

	$html .= '<div class="pt-radio-group">';

    $i = $k = $g = 1;
    $option_html = '';
    foreach ($options as $option) {

		$option = trim( $option );
		$value  = $option;

		if ( $options_are_amounts == 'true' ) {
			$amount = $option;
			$option_name = $currency . ' ' . $amount;
			$value = pt_user_amount_to_float( $value );

			// Collect all amounts so we can store them later for server side validation
			pt_paytium_collect_amounts( $amount );

		} elseif ( ! empty( $amounts ) ) {
			$amount = $amounts[ $i - 1 ];
			$option_name = $option . ' - '. $currency_symbol_after ? $amount . ' ' . $currency : $currency . ' ' . $amount;
			$value = pt_user_amount_to_float( $amount );
		}

		if ( empty( $default ) ) {
			$default = $option;
		}

		if ( $default == $option && $options_are_quantities != 'true' && ! empty( $amounts ) ) {
			$pt_script_options['script']['amount'] = $value;
		}


        $option_html .= '<label title="' . esc_attr($option) . '" >';

        if (isset($item_ids) && isset($item_limits)) {

            $paytium_item_limits = unserialize(get_option('paytium_item_limits'));

            if (get_option('paytium_item_limits') && array_key_exists(sanitize_key($item_ids[$i - 1]), $paytium_item_limits)
                && $paytium_item_limits[sanitize_key($item_ids[$i - 1])] >= (int)$item_limits[$i - 1]
            ) {

				if (!empty($general_item_ids) && !empty($general_limits) &&
					function_exists('pt_paytium_general_limit_reached') && pt_paytium_general_limit_reached($general_item_ids[$i - 1],$general_limits[$i - 1],$paytium_item_limits)) {
					$g++;
				}
				else {
					$option_html .= '<input type="radio" name="' . esc_attr($id) . '[amount]" disabled>';
					$option_html .= '<span class="pt-option-disabled">' . (isset($option_name) ? $option_name : $option) . '</span>
                                    <span>(' . __($limit_message, 'paytium') . ')</span>';
				}
                $k++;
            } else {
				$general_limit_html_data = isset($general_item_ids,$general_limits) ? ' data-general_item_id="' . $general_item_ids[$i - 1] . '" data-general_limit="' . $general_limits[$i - 1] . '"' : '';
                // Don't use built-in checked() function here for now since we need "checked" in double quotes.
                $option_html .= '<input type="radio" name="' . esc_attr($id) . '[amount]" value="' . $value . '" ' .
                    'data-pt-price="' . esc_attr($value) . '" ' . checked($default, $option, false) .
	                'data-item_id="' . $item_ids[$i - 1] . '" data-limit="' . $item_limits[$i - 1] . '"' .
                    ' class="' . esc_attr($id) . '_' . $i . $quantity_class . $amount_class . '" data-parsley-errors-container=".pt-form-group" ' . $quantity_html . $general_limit_html_data.'>';
                $option_html .= '<span>' . (isset($option_name) ? $option_name : $option) . '</span>';

				$items_left = $paytium_item_limits && isset($paytium_item_limits[sanitize_key($item_ids[$i - 1])]) ? (int)$item_limits[$i - 1] - $paytium_item_limits[sanitize_key($item_ids[$i - 1])] : (int)$item_limits[$i - 1];

				$show_items_left_after = is_array($show_items_left_after_arr) && isset($show_items_left_after_arr[$i - 1]) ? $show_items_left_after_arr[$i - 1] : 10;

				$show_items_left_only_for_admin = filter_var($show_items_left_only_for_admin, FILTER_VALIDATE_BOOLEAN);

				if ( $show_items_left !== 'false' && isset($items_left) && $items_left < $show_items_left_after &&
					(($show_items_left_only_for_admin && current_user_can('administrator')) || (!$show_items_left_only_for_admin))) {

					$option_html .= '<p class="pt-items-left">'.sprintf(__('Only %s left!','paytium'), $items_left).'</p>';
				}

				if (!empty($quantity_max) && $quantity_max > $items_left) $quantity_max = $items_left;
            }
        }
		elseif (!empty($general_item_ids) && !empty($general_limits) && empty($item_limits)) {

			$general_limit_html_data = isset($general_item_ids,$general_limits) ? ' data-general_item_id="' . $general_item_ids[$i - 1] . '" data-general_limit="' . $general_limits[$i - 1] . '"' : '';
			$item_id = !empty($item_ids) && isset($item_ids[$i - 1]) ? ' data-item_id="' . $item_ids[$i - 1].'"' : '';

			$general_limit_class = empty($quantity) ? ' general-limit-item' : '';
			$option_html .= '<input type="radio" name="' . esc_attr($id) . '[amount]" value="' . $value . '" ' .
				'data-pt-price="' . esc_attr($value) . '" ' . checked($default, $option, false) .
				$item_id . '" class="' . esc_attr($id) . '_' . $i . $quantity_class . $amount_class . $general_limit_class .
				'" data-parsley-errors-container=".pt-form-group" ' . $quantity_html . $general_limit_html_data . '>';
			$option_html .= '<span>' . (isset($option_name) ? $option_name : $option) . '</span>';

			if ( isset($paytium_item_limits[sanitize_key($general_item_ids[$i - 1])])) {
				$items_left = (int)$general_limits[$i - 1] - $paytium_item_limits[sanitize_key($general_item_ids[$i - 1])];
			} else {
				$items_left = $general_limits[$i - 1];
			}
			$general_items_left = $items_left;

			if (!empty($quantity_max) && $quantity_max > $items_left) $quantity_max = $items_left;
		}
        else {

            // Don't use built-in checked() function here for now since we need "checked" in double quotes.
            $option_html .= '<input type="radio" name="' . esc_attr($id) . '[amount]" value="' . $value . '" ' .
                'data-pt-price="' . esc_attr($value) . '" ' . checked($default, $option, false) .
                ' class="' . esc_attr($id) . '_' . $i . $quantity_class . $amount_class . '" data-parsley-errors-container=".pt-form-group" ' . $quantity_html . '>';
            $option_html .= '<span>' . (isset($option_name) ? $option_name : $option) . '</span>';

        }

        $option_html .= '</label>';

        $i++;
    }

    $html .= $option_html;

	$has_quantity_class = '';
	if(!empty($quantity)) {

		$has_quantity_class = 'has-quantity-input';

		$attributes = '';
        $attributes .= (!empty($quantity_min) ? 'min="' . $quantity_min . '" ' : '');
        $attributes .= (!empty($quantity_max) ? 'max="' . $quantity_max . '" ' : '');
        $attributes .= (!empty($quantity_step) ? 'step="' . $quantity_step . '" ' : '');
        $default_value = (!empty($quantity_min) ? 'value="' . $quantity_min . '" ' : 'value=1');
		$limit_class = isset($general_items_left) ? ' general-limit-qty' : '';
		$quantity_html = '<div class="paytium-quantity-input">'.
			'<button type="button" class="paytium-spinner decrement">-</button>'.
			'<input type="number" ' . $attributes . ' class="pt-quantity-input'.$limit_class.'" name="' . $id . '[quantity]" ' . $default_value . '  >'.
			'<button type="button" class="paytium-spinner increment">+</button>'.
			'</div>';
		$html .= $quantity_html;
	}

    $html .= '<input type="hidden" name="' . $id . '[label]" value="' . wp_kses_post($attr['label']) . '" data-pt-original-label="' . wp_kses_post($attr['label']) . '">';
	$html .= '<input type="hidden" name="' . $id . '[value]" value="">';
    $html .= '<input type="hidden" name="' . $id . '[tax_percentage]" value="' . floatval($attr['tax']) . '">';
    $html .= '<input type="hidden" name="' . $id . '[type]" value="radio">';

	if (isset($item_ids) && isset($item_limits)) {
		$html .= '<input type="hidden" name="' . $id . '[item_id]" value="" data-pt-user-label="Item ID" />';
		$html .= '<input type="hidden" name="' . $id . '[limit]" value="" data-pt-user-label="Limit" />';
		$html .= '<input type="hidden" name="' . $id . '[limit-message]" value="' . $limit_message . '" data-pt-user-label="Limit Message" />';
	}

	if (!empty($general_limit) && !empty($general_item_id)) {
		$html .= '<input type="hidden" name="' . $id . '[general_item_id]" value="" data-pt-user-label="General Item ID" />';
		$html .= '<input type="hidden" name="' . $id . '[general_limit]" value="" data-pt-user-label="General Limit" />';
		if (empty($limit) && !empty($item_id)) {
			$html .= '<input type="hidden" name="' . $id . '[item_id]" value="" data-pt-user-label="Item ID" />';
		}
	}

	if (!empty($crowdfunding_id)) {
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][crowdfunding_id]" value="' . $crowdfunding_id . '" data-pt-user-label="Crowdfunding ID" />';
	}

	$html .= '</div>'; //pt-radio-group

	$args = pt_get_args( $id, $attr, $counter );

	$counter++;

	if ($i == $k && $i != $g) {
		$limit_data['limits'][$item_id] = $limit_message;
        if ($options_are_amounts == 'true' || !empty($amounts)) {
            if(isset($limit_data['amount_count'])) {
                $limit_data['amount_count']++;
            }
            else $limit_data['amount_count'] = 1;
        }
        $html = '<div class="pt-form-alert">' . __($limit_message, 'paytium') . '</div>';
    }
    elseif ($i == $g) {
		$html = '';
	}

    return '<div class="pt-form-group pt-form-group-' . $ptfg_counter . ' pt-form-group-radio ' . $has_quantity_class . '">' . apply_filters('pt_paytium_radio', $html, $args) . '</div>';

}
add_shortcode( 'paytium_radio', 'pt_cf_radio' );


/**
 * Shortcode to output a label- [paytium_field type='label']
 *
 * @since 1.0.0
 */
function pt_cf_label( $attr ) {

	if(!Paytium_Shortcode_Tracker::$is_main_shortcode) {
		return;
	}

	global $counter, $ptfg_counter, $limit_data, $pt_id, $form_currency;
	$ptfg_counter++;

	if (!empty($attr)) {
		foreach ($attr as $key => $attribute) {
			$attr[$key] = esc_attr($attribute);
		}
	}

	$attr = shortcode_atts( array (
		'label'           => '',
		'amount'          => '',
		'tax'             => '',
		'quantity'        => '',
		'quantity_min'    => '',
		'quantity_max'    => '',
		'quantity_step'   => '',
		'limit'           => '',
		'limit_message'   => '',
		'until'           => '',
		'until_message'   => __( 'Payments are no longer possible.', 'paytium' ),
		'item_id'         => '',
		'general_limit'   => '',
		'general_item_id' => '',
		'show_items_left' => true,
		'show_items_left_after' => 10,
		'show_items_left_only_for_admin' => '',
		'multiplied_by' => '',
		'add_zero_tax'  => '',
		'crowdfunding_id' => '',
		'show_amount'     => '',
	), $attr, 'paytium_type' );

	extract( $attr );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_label_' . $counter, 'paytium_label', $attr, false );

	$multiplied_by_data = '';

	$class = '';

	if (!empty($multiplied_by)) {
		$class .= ' class="pt-multiplied-by" ';
		$multiplied_by_data = 'data-pt_multiplied_by="'.$multiplied_by.'"';
	}

	$general_limit_class = !empty($general_item_id) && !empty($general_limit) && empty($limit) && empty($quantity) ? 'general-limit-item' : '';
	$class .= $general_limit_class;

	$id = 'pt_items[' . absint( $counter ) . ']';
	$html = ( ! empty( $attr['label'] ) ? '<label for="' . esc_attr( $id ) . '[value]"'. $multiplied_by_data .' class="'.$class.'">' . wp_kses_post( $attr['label'] ) . '</label>' : '' );

	if ( ! empty( $limit ) ) {
		$items_left = $limit;
	}

	$paytium_item_limits = unserialize(get_option('paytium_item_limits'));
	if (!empty($paytium_item_limits) && !empty($limit) && !empty($item_id) && array_key_exists(sanitize_key($item_id), $paytium_item_limits) ) {
		$items_left = (int)$limit - $paytium_item_limits[sanitize_key($item_id)];
		if (!empty($quantity_max) && $quantity_max > $items_left) $quantity_max = $items_left;
	}
	elseif (empty($limit) && function_exists('pt_paytium_general_limit_reached') && !empty($general_item_id) && !empty($general_limit)) {

		$general_items_left = array_key_exists(sanitize_key($general_item_id), $paytium_item_limits) ? (int)$general_limit - $paytium_item_limits[sanitize_key($general_item_id)] : (int)$general_limit;
		if ((!empty($quantity_max) && $quantity_max > $general_items_left) || empty($quantity_max)) $quantity_max = $general_items_left;
		$items_left = $general_items_left;
	}

	// Default information
	$html .= '<input type="hidden" name="' . $id . '[label]" value="' . wp_kses_post($attr['label']) . '">';
	$html .= '<input type="hidden" name="' . $id . '[type]" value="label">';

	$has_quantity_class = '';
	if(!empty($quantity)) {

		$has_quantity_class = 'has-quantity-input';

		$attributes = '';
		$attributes .= (!empty($quantity_min) ? 'min="' . $quantity_min . '" ' : '');
		$attributes .= (!empty($quantity_max) ? 'max="' . $quantity_max . '" ' : '');
		$attributes .= (!empty($quantity_step) ? 'step="' . $quantity_step . '" ' : '');

		if(!empty($items_left)) {
			$attributes .= 'max="' . $items_left . '" ';
		} elseif(!empty($quantity_max)) {
			$attributes .= 'max="' . $quantity_max . '" ';
		}

		$default_value = (!empty($quantity_min) ? 'value="' . $quantity_min . '" ' : 'value=0');
		$limit_class = isset($general_items_left) ? ' general-limit-qty' : '';

		$quantity_html = '<div class="paytium-quantity-input">'.
						  '<button type="button" class="paytium-spinner decrement">-</button>'.
						  '<input type="number" ' . $attributes . ' class="pt-quantity-input'.$limit_class.'" name="' . $id . '[quantity]" ' . $default_value . '  >'.
						  '<button type="button" class="paytium-spinner increment">+</button>'.
						'</div>';
		$html .= $quantity_html;
	}

	if(!empty($show_amount)){
		$html .= '<div class="pt-quantity-amount">'.pt_float_amount_to_currency($amount, $form_currency[$pt_id]) . '</div>';
	}

	$zero_tax = !empty($add_zero_tax) && $add_zero_tax == 'true';
	$zero_tax_class = $zero_tax ? ' pt-zero-tax' : '';

	// Paid label
	if (!empty($amount)) {

		// Collect all amounts so we can store them later for server side validation
		pt_paytium_collect_amounts( $amount );

		if (isset($limit_data['amount_count'])) {
			$limit_data['amount_count']++;
		}
		else $limit_data['amount_count'] = 1;
		$html .= '<input type="hidden" class="pt-cf-amount pt-cf-label-amount ' . $has_quantity_class . $zero_tax_class . '" name="' . esc_attr($id) . '[amount]" value="' . pt_user_amount_to_float($attr['amount']) . '" ' . 'data-pt-price="' . pt_user_amount_to_float($attr['amount']) . '" />';
		$html .= '<input type="hidden" name="' . $id . '[tax_percentage]" value="' . floatval($attr['tax']) . '">';
	}

	if (!empty($crowdfunding_id)) {
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][crowdfunding_id]" value="' . $crowdfunding_id . '" data-pt-user-label="Crowdfunding ID" />';
	}

	$args = pt_get_args( $id, $attr, $counter );

	if (!empty($limit) && !empty($limit_message) && !empty($item_id)) {
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][item_id]" value="' . $item_id . '" data-pt-user-label="Item ID" />';
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][limit]" value="' . $limit . '" data-pt-user-label="Limit" />';
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][limit-message]" value="' . $limit_message . '" data-pt-user-label="Limit Message" />';

		$limit_data['limits'][$item_id] = 0;

		if (get_option('paytium_item_limits')) {

			$show_items_left_only_for_admin = filter_var($show_items_left_only_for_admin, FILTER_VALIDATE_BOOLEAN);

			if (array_key_exists(sanitize_key($item_id), $paytium_item_limits) && $paytium_item_limits[sanitize_key($item_id)] >= (int)$limit) {

				$limit_data['limits'][$item_id] = $limit_message;
				$html = '<div class="pt-form-alert">' . __($limit_message, 'paytium') . '</div>';
			}
			elseif ( $show_items_left !== 'false' && isset($items_left) && $items_left < $show_items_left_after &&
				(($show_items_left_only_for_admin && current_user_can('administrator')) || (!$show_items_left_only_for_admin)) ) {
				$html .= '<p class="pt-items-left">'.sprintf(__('Only %s left!','paytium'), $items_left).'</p>';
			}
		}
	}

	if (function_exists('pt_paytium_general_limit_reached') && !empty($general_item_id) && !empty($general_limit)) {
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][general_limit]" value="' . $general_limit . '" data-pt-user-label="General Limit" />';
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][general_item_id]" value="' . $general_item_id . '" data-pt-user-label="General Item ID" />';
		if (empty($limit) && !empty($item_id)) {
			$html .= '<input type="hidden" name="pt_items[' . $counter . '][item_id]" value="' . $item_id . '" data-pt-user-label="Item ID" />';
		}
		if ($paytium_item_limits && pt_paytium_general_limit_reached($general_item_id,$general_limit,$paytium_item_limits)) {
			$html = '';
		}
	}

	if (!empty($multiplied_by)) {
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][multiplied_by]" value="' . $multiplied_by . '" data-pt-user-label="Multiplied by" />';
	}

	if ($zero_tax) {
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][add_zero_tax]" value="1" data-pt-user-label="Multiplied by" />';
	}

	$counter++;

	return '<div class="pt-form-group pt-form-group-'.$ptfg_counter.' pt-form-group-label ' . $has_quantity_class . '">' . apply_filters( 'pt_paytium_label', $html, $args ) . '</div>';

}

/**
 * Shortcode to enable Paytium Links - [paytium_links /]
 *
 */
function pt_paytium_links( $attr ) {

	if(!Paytium_Shortcode_Tracker::$is_main_shortcode) {
		return;
	}

	global $counter;

	extract( shortcode_atts( array(), $attr ) );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_links_' . $counter, 'paytium_links', $attr, false );

	$auto_redirect = ( ! empty( $attr[0] ) ) ? $attr[0] : '';

	// Add if Paytium Links is set/used
	$html = '<input type="hidden" id="pt-paytium-links" name="pt-paytium-links" class="pt-paytium-links" value="" data-pt-field-type="pt-paytium-links" />';

	// Add if Paytium Links Auto Redirect is set/used
	if ( $auto_redirect === 'auto_redirect' ) {
		$html = '<input type="hidden" id="pt-paytium-links-auto-redirect" name="pt-paytium-links-auto-redirect" class="pt-paytium-links-auto-redirect" value="" data-pt-field-type="pt-paytium-links-auto-redirect" />';

	}

	$args = pt_get_args( '', $attr, $counter );

	$counter++; // Increment static counter

	return apply_filters( 'pt_paytium_links', $html, $args );

}
add_shortcode( 'paytium_links', 'pt_paytium_links' );

/**
 * Shortcode to create 'regular' forms without payment - [paytium_no_payment /]
 *
 */
function pt_paytium_no_payment( $attr ) {

	if(!Paytium_Shortcode_Tracker::$is_main_shortcode) {
		return;
	}

	global $counter;

	extract( shortcode_atts( array(), $attr ) );

	Paytium_Shortcode_Tracker::add_new_shortcode( 'paytium_no_payment_' . $counter, 'paytium_no_payment', $attr, false );

	$invoice = ( ! empty( $attr[0] ) ) ? $attr[0] : '';

	$html = '<input type="hidden" id="pt-paytium-no-payment" name="pt-paytium-no-payment" class="pt-paytium-no-payment" value="1" data-pt-field-type="pt-paytium-no-payment" />';

	if ($invoice === 'invoice') {
        $html .= '<input type="hidden" name="pt-paytium-no-payment-invoice" value="1" />';
    }

	$args = pt_get_args( '', $attr, $counter );

	$counter++; // Increment static counter

	return apply_filters( 'pt_paytium_no_payment', $html, $args );

}
add_shortcode( 'paytium_no_payment', 'pt_paytium_no_payment' );

/**
 * Function to set the id of the args array and return the modified array
 */
function pt_get_args( $id = '', $args = array (), $counter = '' ) {

	if ( ! empty( $id ) ) {
		$args['id'] = $id;
	}

	if ( ! empty( $counter ) && isset($args['unique_id']) ) {
		$args['unique_id'] = $counter;
	}

	return $args;

}

/**
 * Function to guess what the autocomplete value should be
 */
function pt_guess_autocomplete( $label ) {

	$autocomplete_label = strtolower(preg_replace('/[^a-zA-Z]/', '', $label));

	switch ( $autocomplete_label ) {

		default:
			$autocomplete = '';
			break;
		case 'mail':
		case 'email':
		case 'emailadres':
		case 'emailadrress':
			$autocomplete = "email";
			break;
		case 'naam':
			$autocomplete = "name";
			break;
		case 'voornaam':
			$autocomplete = "given-name";
			break;
		case 'achternaam':
			$autocomplete = "family-name";
			break;
		case 'tussenvoegsel':
			$autocomplete = "additional-name";
			break;
		case 'geboortedatum':
			$autocomplete = "bday";
			break;
		case 'geslacht':
			$autocomplete = "sex";
			break;
		case 'organisatie':
		case 'bedrijf':
		case 'bedrijfsnaam':
			$autocomplete = "organization";
			break;
		case 'functie':
		case 'rol':
			$autocomplete = "organization-title";
			break;
		case 'website':
			$autocomplete = "url";
			break;
		case 'adres':
		case 'postadres':
			$autocomplete = "street-address";
			break;
		case 'straat':
		case 'straatnaam':
			$autocomplete = "address-line1";
			break;
		case 'huisnummer':
			$autocomplete = "address-line2";
			break;
		case 'postcode':
			$autocomplete = "postal-code";
			break;

		case 'woonplaats':
		case 'plaats':
		case 'plaatsnaam':
		case 'stad':
			$autocomplete = "address-level2";
			break;
		case 'land':
			$autocomplete = "country-name";
			break;
		case 'telefoon':
		case 'telefoonnummer':
		case 'nummer':
		case 'mobielnummer':
		case 'mobiel':
			$autocomplete = "tel";
			break;

	}

	return $autocomplete;

}

/**
 * Shortcode to set user role, if creating one - [paytium_user_data role="role-slug" /]
 *
 */
function pt_paytium_user_data($attr)
{
	if(!Paytium_Shortcode_Tracker::$is_main_shortcode) {
		return;
	}

    global $counter;
    $attr = shortcode_atts(array(
        'role' => '',
    ), $attr, 'paytium_user_data');

    extract($attr);

    // Make user role lowercase for users that don't use the proper user role slug (which should be lowercase)
	$role = strtolower($role);

    Paytium_Shortcode_Tracker::add_new_shortcode('paytium_user_data_' . $counter, 'paytium_user_data', $attr, false);

    $html = '<input type="hidden" id="pt-paytium-user-data" name="pt-paytium-user-data" class="pt-paytium-user-data" value="'.esc_attr($role).'" data-pt-field-type="pt-paytium-user-data" />';

    $args = pt_get_args('', $attr, $counter);

    $counter++; // Increment static counter

    return apply_filters('pt_paytium_user_data', $html, $args);

}
add_shortcode('paytium_user_data', 'pt_paytium_user_data');

/**
 * Shortcode to output a crowdfunding progress bar - [paytium_progress label="Crowdfunding goal" goal_amount="1000" /]
 *
 */
function pt_paytium_progress($attr)
{

	global $counter, $pt_id, $form_currency;

	if (!empty($attr)) {
		foreach ($attr as $key => $attribute) {
			$attr[$key] = esc_attr($attribute);
		}
	}

	$attr = shortcode_atts(array(
        'label' => '',
        'goal_amount' => '',
		'crowdfunding_id' => '',
		'add_amount' => '',
		'decimals' => '',
		'currency' => '',
    ), $attr, 'paytium_progress');

    extract($attr);

    Paytium_Shortcode_Tracker::add_new_shortcode('paytium_progress', 'paytium_progress', $attr, false);

	$total_amount = '';
	$add_amount = pt_user_amount_to_float($add_amount);
	if ( $add_amount != null && is_float($add_amount)) {
		$total_amount =+ $add_amount;
	} else {
		$total_amount = 0;
	}

	$html = '';

	if (!empty($crowdfunding_id) && Paytium_Shortcode_Tracker::$is_main_shortcode) {
		$html .= '<input type="hidden" name="pt_items[' . $counter . '][crowdfunding_id]" value="' . $crowdfunding_id . '" data-pt-user-label="Crowdfunding ID" />';
	}

	// Check first if in live or test mode.
	if ( get_option( 'paytium_enable_live_key', false ) == 1 ) {
		$current_mode = 'live';
	} else {
		$current_mode = 'test';
	}

	$currency = $currency && is_file( PT_PATH . 'features/currency.php' ) ? $currency : ($form_currency[$pt_id] ? $form_currency[$pt_id] : 'EUR');

    if ($crowdfunding_id) {

    	global $wpdb;
		$prepare = $wpdb->prepare( "SELECT post_id FROM " . $wpdb->prefix . "postmeta WHERE meta_key = '_crowdfunding_id' AND meta_value = '%s'",$crowdfunding_id);
		$results = $wpdb->get_results( $prepare );

		foreach ($results as $post_id) {

			$post_id = $post_id->post_id;
			$status = get_post_meta($post_id,'_status',true);
			$mode = get_post_meta($post_id,'_mode',true);
			if ($mode == $current_mode && $status == 'paid' && ($currency == get_post_meta($post_id,'_currency',true) ||
																($currency == 'EUR' && !get_post_meta($post_id,'_currency',true)) ) ) {

				$total_amount += get_post_meta($post_id,'_amount',true);
			}
		}
	}

	else {

		$meta_query = array (
			array (
				'key'     => '_mode',
				'value'   => $current_mode,
				'compare' => '='
			),
			array (
				'key'     => '_status',
				'value'   => 'paid',
				'compare' => '='
			)
		);

		if ($currency == 'EUR') {
			$meta_query[] = [
				'relation' => 'OR',
				[
					'key'     => '_currency',
					'value'   => $currency,
					'compare' => '='
				],
				[
					'key'     => '_currency',
					'compare' => 'NOT EXISTS'
				]
			];
		}
		else {
			$meta_query[] = [
				'key'     => '_currency',
				'value'   => $currency,
				'compare' => '='
			];
		}

		$args = wp_parse_args( array (
			'post_type'   => 'pt_payment',
			'post_status' => 'publish',
			'meta_query' => $meta_query,
			'fields'        => 'ids',
			'posts_per_page' => -1,
		) );

		$live_payments = new WP_Query( $args );

		foreach($live_payments->posts as $id) {
			$post_meta = get_post_meta($id);
			foreach($post_meta as $key => $value) {
				if (strstr($key, '-total-amount')) {
					$total_amount += $value[0];
				}
			}
		}
	}

    $progress = $total_amount/pt_user_amount_to_float($goal_amount);
    if ($progress > 1) {
        $progress = 1;
    }

	$total_amount = pt_float_amount_to_currency( $total_amount, $currency );
	$goal_amount  = pt_float_amount_to_currency( $goal_amount, $currency );

	if ( $decimals == 'false' ) {
		$total_amount = strstr( $total_amount, ',', true );
		$goal_amount  = strstr( $goal_amount, ',', true );
	}

    $progress = $progress*100 . '%';

	$html .= '<div class="pt-crowdfunding-wrapper"><div class="pt-crowdfunding-label">'.esc_html($label).'</div>';
	$html .= '<div class="pt-crowdfunding-progress-bar">
				<div class="pt-crowdfunding-progress-bar-content">
					<span class="pt-progress-bar-current-amount">'.$total_amount.'</span>
					<span class="pt-progress-bar-values-delimiter">/</span>
					<span class="pt-progress-bar-total-amount">'.$goal_amount.'</span>
				</div>
				<div class="pt-crowdfunding-progress-bar-fullness" style="width: '.$progress.';"></div>
			</div>';
	$html .= '</div>';

    return apply_filters('pt_paytium_progress', $html);

}
add_shortcode('paytium_progress', 'pt_paytium_progress');

/**
 * Shortcode to output custom content
 *
 */
function pt_paytium_content($attr, $content )
{
	extract( shortcode_atts( array(), $attr ) );

	Paytium_Shortcode_Tracker::add_new_shortcode('paytium_content', 'paytium_content', $attr, false);

	$no_access_message = __('You can\'t view this private content', 'paytium');

	if (!empty($attr)) {
		foreach ($attr as $key => $attribute) {
			$attr[$key] = esc_attr($attribute);
		}
	}

	$user_role = isset($attr['user_role']) ? $attr['user_role'] : '';
	$payment = isset($attr['payment']) ? $attr['payment'] : '';
	$subscription = isset($attr['subscription']) ? $attr['subscription'] : '';
	$warning_page = isset($attr['warning_page']) && $attr['warning_page'] != 'none' && get_post_status($attr['warning_page']) ? get_post($attr['warning_page'])->post_content : 'none';
	$content_page = isset($attr['content_page']) ? $attr['content_page'] : '';
	$period = isset($attr['period']) ? $attr['period'] : '';
	$logged_in = isset($attr[0]) && $attr[0] == 'logged_in' ? true : false;
	$logged_out = isset($attr[0]) && $attr[0] == 'logged_out' ? true : false;
	$payment_key = isset( $_GET['pt-payment'] ) ? sanitize_key($_GET['pt-payment']) : sanitize_key(@$_COOKIE['ptpayment']);
	$item_id = isset($attr['item_id']) ? $attr['item_id'] : '';

	$html = '';

	if ( ! isset($attr['warning_page'])  ) {
		$html = '<div class="pt-form-alert">' . $no_access_message . '</div>';
	}

	if ( $warning_page != 'none' ) {
		$html = $warning_page;
	}


	if ($period)  {

		// Check if a number of period is set for example "2 day", otherwise set to 1 as default
		if ( preg_match( '!\d+!', $period, $matches ) ) {

			$num_length = strlen( $matches[0] );
			$period     = substr( $period, $num_length, 1 ) != ' ' ?
				'+' . substr( $period, 0, $num_length ) . ' ' . substr( $period, $num_length ) :
				'+' . $period;
		} else {
			$period = '+1 ' . $period;
		}

		// If a user is logged in
		if ( get_current_user_id() !== 0 ) {
			$user_last_payment_date = get_user_meta( get_current_user_id(), '_last_paid_date', true );

			// Check and show warning if expired
			if ( strtotime( $period, strtotime( $user_last_payment_date ) ) < time() ) {
				return $html;
			}
		}

		// If user is not logged in but there is a payment_key (directly after payment)
		if ( $payment_key != null ) {

			$meta_query[] = array (
				'relation' => 'AND',
				array (
					'key'     => '_payment_key',
					'value'   => $payment_key,
					'compare' => '='
				)
			);

			$args = wp_parse_args( array (
				'post_type'      => 'pt_payment',
				'post_status'    => 'publish',
				'meta_query'     => $meta_query,
				'fields'         => 'ids',
				'posts_per_page' => -1,
			) );
		}

		$user_payments = new WP_Query( $args );
		if ( ! empty( $user_payments->posts ) ) {

			$user_last_payment_date = get_the_date( 'Y-m-d', $user_payments->posts[0] );

			// Check and show warning if expired
			if ( strtotime( $period, strtotime( $user_last_payment_date ) ) < time() ) {
				return $html;
			}
		}

	}

	if (empty($attr) || (count($attr) == 1 && array_key_exists('period', $attr))) {
		return $content;
	}

	if ($logged_in === true && is_user_logged_in()) {
		$html = $content;
		if ($content_page != '') {
			$page_content = get_post($content_page)->post_content;
			$html = $page_content;
		}
	}
	elseif ($logged_out === true && !is_user_logged_in()) {
		$html = $content;
		if ($content_page != '') {
			$page_content = get_post($content_page)->post_content;
			$html = $page_content;
		}
	}


	if ($logged_in === false && $logged_out === false) {

		if ($user_role != '' && $payment == '' && $subscription == '') {

			if (paytium_content_user_role_helper_function( $user_role ) === true) {
				$html = $content;
				if ($content_page != '') {
					$page_content = get_post($content_page)->post_content;
					$html = $page_content;
				}
			}

		}
		elseif ($user_role != '' && ($payment != '' || $subscription != '')) {

			if (($payment != '' && paytium_content_payments_helper_function($payment, 'pt_payment', $item_id, $payment_key) === true &&
			     paytium_content_user_role_helper_function( $user_role ) === true) ||
			    ($subscription != '' && paytium_content_payments_helper_function($subscription, 'pt_subscription', $item_id, $payment_key) === true &&
			     paytium_content_user_role_helper_function( $user_role ) === true)) {

				$html = $content;
				if ($content_page != '') {
					$page_content = get_post($content_page)->post_content;
					$html = $page_content;
				}
			}

		}

		elseif ($user_role == '' && ($payment != '' || $subscription != '')) {

			if (($payment != '' && paytium_content_payments_helper_function($payment, 'pt_payment', $item_id, $payment_key) === true) ||
			    ($subscription != '' && paytium_content_payments_helper_function($subscription, 'pt_subscription', $item_id, $payment_key) === true)) {

				$html = $content;
				if ($content_page != '') {
					$page_content = get_post($content_page)->post_content;
					$html = $page_content;
				}
			}
		}
	}

	$html = apply_filters('pt_paytium_content', $html);

	return do_shortcode($html);

}
add_shortcode('paytium_content', 'pt_paytium_content');



/**
 * Payments/subscriptions helper function for [paytium_content]
 *
 */
function paytium_content_payments_helper_function( $data, $post_type, $item_id, $payment_key = null ) {

	$user = wp_get_current_user();
	$if_status = array();
	$if_not_status = array();
	$statuses = explode(',',$data);
	foreach ($statuses as $status) {
		if (substr($status, 0, 1) == '!'){
			$if_not_status[] = substr($status,1);
		}
		else $if_status[] = $status;
	}

	$status_key = $post_type == 'pt_payment' ? '_status' : '_subscription_status';
	$status_meta_query = array('relation' => 'OR', );

	foreach ($if_status as $status) {
		$status_meta_query[] = array(
			'key'     => $status_key,
			'value'   => $status,
			'compare' => '='
		);
	}
	foreach ($if_not_status as $status) {
		$status_meta_query[] = array(
			'key'     => $status_key,
			'value'   => $status,
			'compare' => '!='
		);
	}

	$args = array();

	// If user is logged in
	if ( ($user->ID != 0 && $payment_key == null) || ($user->ID != 0 && $post_type == 'pt_subscription') ) {

		$args = wp_parse_args( array (
			'post_type'   => $post_type,
			'post_status' => 'publish',
			'author'      => $user->ID,

			'meta_query' => $status_meta_query,

			'fields'         => 'ids',
			'posts_per_page' => -1,
		) );
	}

	// If user is not logged in but there is a payment_key (directly after payment)
	if ( $payment_key != null && $post_type != 'pt_subscription' ) {

		$meta_query[] = array('relation' => 'AND',
			array (
				'key'     => '_payment_key',
				'value'   => $payment_key,
				'compare' => '='
			));

		$meta_query[] = $status_meta_query;

		$args = wp_parse_args( array (
			'post_type'   => $post_type,
			'post_status' => 'publish',
			'meta_query'  => $meta_query,
			'fields'         => 'ids',
			'posts_per_page' => -1,

		) );

	}

	$user_payments = new WP_Query( $args );
	if (!empty($user_payments->posts)) {

		if ($item_id != '') {
			global $wpdb;

			$post_query = $wpdb->prepare(
				"
						SELECT post_id
						FROM {$wpdb->prefix}postmeta
						WHERE meta_key
						LIKE '_pt-field-item-id%' AND meta_value = '%s'
						", $item_id
			);
			$post_query = $wpdb->get_results($post_query,ARRAY_N);

			if ($post_type == 'pt_subscription') {
				foreach ($post_query as $result) {
					$subscription_id = get_post_meta($result[0], '_subscription_id',true);
					if ($subscription_id != '') {
						$subscription_status = get_post_meta($subscription_id, '_subscription_status',true);
						if ((!empty($if_status) && in_array($subscription_status,$if_status)) || (!empty($if_not_status) && !in_array($subscription_status,$if_not_status))) return true;
					}
				}

				return false;
			}
			else {
				foreach ($post_query as $result) {
					$payment_status = get_post_meta($result[0], '_status',true);
					if ((!empty($if_status) && in_array($payment_status,$if_status)) || (!empty($if_not_status) && !in_array($payment_status,$if_not_status))) return true;
				}

				return false;
			}
		}

		else return true;

	}
	else return false;

}



/**
 * User role helper function for [paytium_content]
 *
 */
function paytium_content_user_role_helper_function( $user_role ) {

	$user = wp_get_current_user();
	$show_content = false;
	$if_role_is = array();
	$if_role_is_not = array();
	$user_roles = explode(',',$user_role);
	foreach ($user_roles as $role) {
		if (substr($role, 0, 1) == '!'){
			$if_role_is_not[] = substr($role,1);
		}
		else $if_role_is[] = $role;
	}

	foreach ($if_role_is_not as $role) {
		if (in_array($role, $user->roles)) {
			return $show_content;
		}
	}

	if (!empty($if_role_is)) {
		foreach ($if_role_is as $role) {
			if (in_array($role, $user->roles)) {
				$show_content = true;
				break;
			}
		}
	}
	else {
		$show_content = true;
	}

	return $show_content;

}

function pt_normalize_empty_atts($atts) {

	if(is_array($atts)) {
		foreach ( $atts as $attribute => $value ) {
			if ( is_int( $attribute ) ) {
				$atts[ strtolower( $value ) ] = true;
				unset( $atts[ $attribute ] );
			}
		}
	}
	return $atts;
}

/**
 * Function to collect form amounts
 *
 * @since 4.0.0
 */
function pt_paytium_collect_amounts( $amount ) {

	global $collected_amounts;

	$amount = pt_user_amount_to_float( $amount );

	if ( ! empty( $amount ) ) {
		$collected_amounts[] = $amount;
	}

	return $collected_amounts;

}

/**
 * Function to collect form fields
 *
 * @since 4.0.2
 */
function pt_paytium_collect_fields( $id, $type, $required ) {

	global $collected_fields;


	// Check values aren't empty, or add defaults

	$collected_fields[ $id ] = array (
		'id'       => $id,
		'type'     => $type,
		'required' => $required
	);

	return $collected_fields;

}

/**
 * Function to store form elements (amounts, fields)
 *
 * @since 4.0.0
 */
function pt_paytium_store_form_elements( $form_load_id ) {

	global $collected_amounts;
	global $collected_fields;
	global $collected_discounts;

	$form_data['amounts'] = $collected_amounts;
	$form_data['fields']  = $collected_fields;
	$form_data['discounts']  = $collected_discounts;

	// Combine amounts and fields
	$form_load_id = 'paytium_form_load_' . $form_load_id;

	set_transient( $form_load_id, $form_data, 7 * DAY_IN_SECONDS );
	$collected_amounts = [];
	$collected_fields = [];
	$collected_discounts = [];

}

/**
 * Function to get form load ID
 *
 * @since 1.0.0
 */
function pt_paytium_get_form_load_id( $form_id ) {

	$form_load_id = 0;

	if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
		$sslStr = openssl_random_pseudo_bytes( '16', $strong );
		if ( $strong ) {
			$form_load_id = bin2hex( $sslStr );
		}
	}

	return $form_load_id;

}

/**
 * Shortcode for Paytium log in button - [paytium_login_button /]
 *
 * @since 4.2.0
 */

function pt_paytium_login_button($attr) {

	if (!empty($attr)) {
		foreach ($attr as $key => $attribute) {
			$attr[$key] = esc_html($attribute);
		}
	}

	$attr = shortcode_atts(array(
		'login_button_label' => '',
		'my_profile_button_label' => '',
	), $attr, 'paytium_user_data');

	extract($attr);

	Paytium_Shortcode_Tracker::add_new_shortcode('paytium_login_button', 'paytium_login_button', $attr, false);

	$login_button_label = $login_button_label ? $login_button_label : __('Log in', 'paytium');
	$my_profile_button_label = $my_profile_button_label ? $my_profile_button_label : __('My profile', 'paytium');
//	$button_label = is_user_logged_in() ? $my_profile_button_label : $login_button_label;

	if (is_user_logged_in()) {
		$html = '<a href="/wp-admin/admin.php?page=pt-my-profile&tab=my-profile" class="paytium-my-profile-link button">'.$my_profile_button_label.'</a>';
	}
	else {
		$html = '<button id="paytium-login-btn" class="paytium-login-btn button">'.$login_button_label.'</button>';

		add_action('wp_footer', 'wpshout_action_example');
		function wpshout_action_example() {
			$html2 = '<div class="paytium-login-form-overlay"><span class="paytium-login-close">&times;</span><div class="paytium-login-form-wrapper">';
			$html2 .= wp_login_form( array(
				'echo'           => false,
				'redirect'       => site_url( "/wp-admin/admin.php?page=pt-my-profile&tab=my-profile" ),
				'form_id'        => 'paytium_login_form',
				'label_username' => __( 'Username', 'paytium' ),
				'label_password' => __( 'Password', 'paytium' ),
				'label_remember' => __( 'Remember Me', 'paytium' ),
				'label_log_in'   => __( 'Log In', 'paytium' ),
				'id_username'    => 'user_login',
				'id_password'    => 'user_pass',
				'id_remember'    => 'rememberme',
				'id_submit'      => 'wp-submit',
				'remember'       => true,
				'value_username' => NULL,
				'value_remember' => false
			) );
			$html2 .= '</div></div>';
			echo $html2;
		}
	}

	return apply_filters('pt_paytium_login_button', $html);

}
add_shortcode('paytium_login_button', 'pt_paytium_login_button');