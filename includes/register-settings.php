<?php

/**
 * Register all settings needed for the Settings API.
 *
 * @package    PT
 * @subpackage Includes
 * @author     David de Boer <david@davdeb.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main function to register all of the plugin settings
 *
 * @since 1.0.0
 */
function pt_register_settings() {

	$pt_settings = array (

		/* Default Settings */

		'default' => array (
			array (
				'id'   => 'paytium_settings_note',
				'name' => '',
				'desc' => sprintf( '<a href="%s" target="_blank">%s</a>', pt_ga_campaign_url( PT_WEBSITE_URL . 'handleiding', 'paytium', 'settings', 'docs' ), __( 'See shortcode options and examples', 'paytium' ) ) . ' ' .
				          __( 'for', 'paytium' ) . ' ' . Paytium::get_plugin_title() . '<br/>' .
				          '<p class="description">' . __( 'Shortcode attributes take precedence and will always override site-wide default settings.', 'paytium' ) . '</p>',
				'type' => 'section'
			),
			array (
				'id'   => 'paytium_name',
				'name' => __( 'Site Name', 'paytium' ),
				'desc' => __( 'The name of your store or website. Defaults to Site Name.', 'paytium' ),
				'type' => 'text',
				'size' => 'regular-text'
			),
			array (
				'id'   => 'button_label',
				'name' => __( 'Payment Button Label', 'paytium' ),
				'desc' => __( 'Text to display on the default blue button that users click to initiate a checkout process.', 'paytium' ),
				'type' => 'text',
				'size' => 'regular-text'
			),
			array (
				'id'   => 'paytium_pt_redirect_url',
				'name' => __( 'Redirect URL', 'paytium' ),
				'desc' => __( 'The URL that the user should be redirected to after a payment.', 'paytium' ),
				'type' => 'text',
				'size' => 'regular-text'
			),
			array (
				'id'   => 'paytium_disable_css',
				'name' => __( 'Disable Plugin CSS', 'paytium' ),
				'desc' => __( 'If this option is checked, this plugin\'s CSS file will not be referenced.', 'paytium' ),
				'type' => 'checkbox'
			),
			array (
				'id'   => 'paytium_always_enqueue',
				'name' => __( 'Always Enqueue Scripts & Styles', 'paytium' ),
				'desc' => __( 'Enqueue this plugin\'s scripts and styles on every post and page.', 'paytium' ) . '<br/>' .
				          '<p class="description">' . __( 'Useful if using shortcodes in widgets or other non-standard locations.', 'paytium' ) . '</p>',
				'type' => 'checkbox'
			),
			array (
				'id'   => 'paytium_uninstall_save_settings',
				'name' => __( 'Save Settings', 'paytium' ),
				'desc' => __( 'Save your settings when uninstalling this plugin.', 'paytium' ) . '<br/>' .
				          '<p class="description">' . __( 'Useful when upgrading or re-installing.', 'paytium' ) . '</p>',
				'type' => 'checkbox',
			),
			array (
				'id'   => 'paytium_pt_total_label',
				'name' => __( 'Paytium Total Label', 'paytium' ),
				'desc' => __( 'The default label for the paytium_total shortcode.', 'paytium' ),
				'type' => 'text',
				'size' => 'regular-text'
			),
			array (
				'id'   => 'paytium_pt_uea_label',
				'name' => __( 'Amount Input Label', 'paytium' ),
				'desc' => __( 'Label to show before the amount input.', 'paytium' ),
				'type' => 'text',
				'size' => 'regular-text'
			)
		),
		/* Keys settings */

		'keys'    => array (
			array (
				'id'   => 'paytium_enable_live_key',
				'name' => __( 'Test or Live Mode', 'paytium' ),
				'desc' => '<p class="description">' . __( 'Toggle between using your Test or Live API keys. The selected option has a blue background.', 'paytium' ) . '</p>',
				'type' => 'toggle_control'
			),
			array (
				'id'   => 'paytium_api_key_note',
				'name' => '',
				'desc' => sprintf( '%s <a href="%s" target="_blank">%s</a> %s', __('The test mode can be used when you are building and testing your payment form(s). When you are ready, switch to live mode to start accepting real payments. ', 'paytium'), 'https://my.mollie.com/dashboard/signup/335035', __( 'Login at Mollie to find your API keys', 'paytium' ), __( ' if the below fields are empty or use the Setup Wizard.') ),
				'type' => 'section'
			),
			array (
				'id'   => 'paytium_live_api_key',
				'name' => __( 'Live API Key', 'paytium' ),
				'desc' => '',
				'type' => 'text',
				'size' => 'regular-text'
			),
			array (
				'id'   => 'paytium_test_api_key',
				'name' => __( 'Test API Key', 'paytium' ),
				'desc' => '',
				'type' => 'text',
				'size' => 'regular-text'
			),
			array (
				'id'   => 'paytium_admins_test_mode',
				'class' => get_option('paytium_enable_live_key') == '1' && get_option('paytium_live_api_key') != '' ? '' : 'hidden',
				'name' => __( 'Test mode for administrators', 'paytium' ),
				'desc' => __('When administrators place payments, those payments will be processed with the Mollie test mode. Use this when the site is already live, but payments need to be tested or a new form is being built. Only enable this when you need it, don\'t keep it activated by default. This is only useful for testing new payments and new subscriptions, it can\'t be used for testing renewal payments. If you cancel or refund a live payment or live subscription, it will really be processed!' , 'paytium'),
				'type' => 'checkbox',
				'size' => 'regular-text'
			),
		)
	);

	$pt_settings = apply_filters( 'pt_settings', $pt_settings );

	$pt_settings_title = '';

	foreach ( $pt_settings as $section_key => $section_settings ) {

		add_settings_section(
			'pt_settings_' . $section_key,
			$pt_settings_title,
			'__return_false',
			'pt_settings_' . $section_key
		);

		foreach ( $section_settings as $option ) {
			add_settings_field(
				$option['id'],
				$option['name'],
				function_exists( 'pt_' . $option['type'] . '_callback' ) ? 'pt_' . $option['type'] . '_callback' : 'pt_missing_callback',
				'pt_settings_' . $section_key,
				'pt_settings_' . $section_key,
				pt_get_settings_field_args( $option, $section_key )
			);
			register_setting( 'pt_settings_' . $section_key, $option['id'] );
		}

        if ($section_key == 'advanced') {
            unregister_setting('pt_settings_' . $section_key, 'paytium_mailfrom_override');
            register_setting( 'pt_settings_' . $section_key, 'paytium_mailfrom_override_default', 'absint' );
            register_setting( 'pt_settings_' . $section_key, 'paytium_mailfrom_override_admin', 'absint' );
        }

	}

}

add_action( 'admin_init', 'pt_register_settings' );

/**
 * Return generic add_settings_field $args parameter array.
 *
 * @since   1.0.0
 *
 * @param  string $option  Single settings option key.
 * @param  string $section Section of settings page.
 *
 * @return array  $args    parameter to use with add_settings_field call.
 */
function pt_get_settings_field_args( $option, $section ) {

	$settings_args = wp_parse_args( $option, array (
		'id'      => '',
		'desc'    => '',
		'name'    => '',
		'section' => $section,
		'size'    => '',
		'options' => '',
		'std'     => '',
		'product' => '',
	) );

	// Link label to input using 'label_for' argument if text, textarea, password, select, or variations of.
	// Just add to existing settings args array if needed.
	if ( in_array( $option['type'], array ( 'text', 'select', 'textarea', 'password', 'number' ) ) ) {
		$settings_args = array_merge( $settings_args, array ( 'label_for' => 'pt_settings_' . $section . '[' . $option['id'] . ']' ) );
	}

	return $settings_args;
}


function pt_toggle_control_callback( $args ) {

	foreach ($args as $key => $arg) {
		if ($key == 'desc') continue;
		$args[$key] = esc_attr($arg);
	}

	$value   = get_option( $args['id'], $args['std'] );
	$checked = checked( 1, $value, false );

	$html = '<div class="pt-toggle-switch-wrap">
			<label class="switch-light switch-candy switch-candy-blue" onclick="">
				<input type="checkbox" id="pt_settings_' . $args['section'] . '[' . $args['id'] . ']" name="' . $args['id'] . '" value="1" ' . $checked . '/>
				<span>
					<span>' . __( 'Test', 'paytium' ) . '</span>
					<span>' . __( 'Live', 'paytium' ) . '</span>
				</span>
				<a></a>
			</label></div>';

	echo $html;
}

/**
 * Textbox callback function
 * Valid built-in size CSS class values:
 * small-text, regular-text, large-text, textarea
 *
 * @since 1.0.0
 */
function pt_text_callback( $args ) {

	foreach ($args as $key => $arg) {
		if ($key == 'desc') continue;
		$args[$key] = esc_attr($arg);
	}

	$value = get_option( $args['id'], $args['std'] );

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : '';
	if ($size == 'textarea') {
		$html = "\n" . '<textarea rows="9" id="' . $args['id'] . '" class="large-text" name="' . $args['id'] . '">' . trim( esc_attr( $value ) ) . '</textarea>' . "\n";
    }
    else {
		$html = "\n" . '<input type="text" class="' . $size . '" id="' . $args['id'] . '" name="' . $args['id'] . '" value="' . trim( esc_attr( $value ) ) . '"/>' . "\n";
    }

	// Render and style description text underneath if it exists.
	if ( ! empty( $args['desc'] ) ) {
		$html .= '<p class="description">' . $args['desc'] . '</p>' . "\n";
	}

	echo $html;
}


/**
 * Textbox callback function
 * Valid built-in size CSS class values:
 * small-text, regular-text, large-text
 *
 * @since 1.5.0
 */
function pt_number_callback( $args ) {

	foreach ($args as $key => $arg) {
		if ($key == 'desc') continue;
		$args[$key] = esc_attr($arg);
	}

	$value = get_option( $args['id'], $args['std'] );

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : '';
	$html = "\n" . '<input type="number" class="' . $size . '" id="' . $args['id'] . '" name="' . $args['id'] . '" value="' . trim( esc_attr( $value ) ) . '"/>' . "\n";

	// Render and style description text underneath if it exists.
	if ( ! empty( $args['desc'] ) ) {
		$html .= '<p class="description">' . $args['desc'] . '</p>' . "\n";
	}

	echo $html;
}

/**
 * Date input field HTML.
 *
 * @since 1.0.0
 *
 * @param array $args
 */
function pt_date_callback( $args ) {

	if (strstr($args['id'],'start')) {
		$default_value = isset( $args['std'] ) ? $args['std'] : date('Y-m-1');
	}
	elseif (strstr($args['id'],'end')) {
		$default_value = isset( $args['std'] ) ? $args['std'] : date('Y-m-t');
	}
	else {
		$default_value = isset( $args['std'] ) ? $args['std'] : date('Y-m-d');
	}

	$value = get_option( $args['id'], $default_value );
	$value = $value == '' ? $default_value : $value;

	?><input type="text" class="regular-text" id="<?php echo esc_attr( $args['id'] ); ?>" name="<?php echo esc_attr( $args['id'] ); ?>" value="<?php echo esc_attr( date('d-m-Y', strtotime($value ) ) ); ?>" /><?php

}

/**
 * Single checkbox callback function
 *
 * @since 1.0.0
 */
function pt_checkbox_callback( $args ) {

	foreach ($args as $key => $arg) {
		if ($key == 'desc') continue;
		$args[$key] = esc_attr($arg);
	}

	$value   = get_option( $args['id'], $args['std'] );
	$checked = checked( 1, $value, false );

	$html = "\n" . '<input type="checkbox" id="' . $args['id'] . '" name="' . $args['id'] . '" value="1" ' . $checked . '/>' . "\n";

	// Render and style description text underneath if it exists.
	if ( ! empty( $args['desc'] ) ) {
		$html .= '<p class="description">' . $args['desc'] . '</p>' . "\n";
	}

	echo $html;
}


/**
 * Section callback function
 *
 * @since 1.0.0
 */
function pt_section_callback( $args ) {

	foreach ($args as $key => $arg) {
	    if ($key == 'desc') continue;
		$args[$key] = esc_attr($arg);
	}

	$html = '';

	if ( ! empty( $args['desc'] ) ) {
		$html .= $args['desc'];
	}

	echo $html;
}

/**
 * Select box callback function
 */
function pt_select_callback( $args ) {

	// Return empty string if no options.
	if ( empty( $args['options'] ) ) {
		return;
	}

	foreach ($args as $key => $arg) {
		if ($key == 'desc') continue;
		$args[$key] = esc_attr($arg);
	}

	$value = get_option( $args['id'], $args['std'] );

	$html = "\n" . '<select id="pt_settings_' . $args['section'] . '[' . $args['id'] . ']" name="' . $args['id'] . '"/>' . "\n";

	foreach ( $args['options'] as $option => $name ) :
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>' . "\n";
	endforeach;

	$html .= '</select>' . "\n";

	// Render and style description text underneath if it exists.
	if ( ! empty( $args['desc'] ) ) {
		$html .= '<p class="description">' . $args['desc'] . '</p>' . "\n";
	}

	echo $html;
}

function pt_multi_select_callback( $args ) {

	wp_enqueue_script( 'pt-select2' );
	wp_enqueue_style( 'pt-select2' );

	foreach ($args as $key => $arg) {
		if ($key == 'desc' || $key == 'options') continue;
		$args[$key] = esc_attr($arg);
	}

	$options = $args['options'];
	$value = get_option( $args['id'] ) ? get_option( $args['id'] ) : array();
	$html = "\n" . '<select class="select2 all-options pt_multiselect" multiple="multiple" id="pt_settings_' . $args['section'] . '[' . $args['id'] . ']" name="' . $args['id'] . '[]"/>' . "\n";

	foreach ( $options as $option => $name ) {
		$selected = in_array($option, $value) || empty($value) ? 'selected' : '';
		$html .= '<option value="' . $option . '" ' .$selected. '>' . $name . '</option>' . "\n";
	}

	$html .= '</select>' . "\n";

	if ( ! empty( $args['desc'] ) ) {
		$html .= '<p class="description">' . $args['desc'] . '</p>' . "\n";
	}

	$html .= '<script type="text/javascript">
		jQuery(document).ready(function ($) {
			$( ".select2" ).select2({
				closeOnSelect: false
			});
		});
	</script>';

	echo $html;
}


/**
 * Radio button callback function
 *
 * @since 1.0.0
 */
function pt_radio_callback( $args ) {

	foreach ( $args['options'] as $key => $option ) {

		$value   = get_option( $args['id'], $args['std'] );
		$checked = checked( $key, $value, false );

		echo '<input name="' . $args['id'] . '" id="' . $args['id'] . '" type="radio" value="' . $key . '" ' . $checked . '/>&nbsp;';
		echo '<label for="' . $args['id'] . '">' . $option . '</label><br/>';
	}

	echo '<p class="description">' . $args['desc'] . '</p>';
}

/**
 * Default callback function if correct one does not exist
 *
 * @since 1.0.0
 */
function pt_missing_callback( $args ) {
	printf( __( 'The callback function used for the <strong>%s</strong> setting is missing.', 'paytium' ), $args['id'] );
}
