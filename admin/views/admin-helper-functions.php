<?php

/**
 * Admin helper functions to get the base plugin tab and help tab set
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get Admin Tabs and label
 *
 * since 1.0.0
 *
 * array( $key => $value )
 * $key is the value that is used when making the setting option
 * $value is the display title of the tab
 *
 * @return array
 */
function pt_get_admin_tabs() {

	$tabs = array (
		'keys' => __( 'Mollie API Keys', 'paytium' ),
		// TODO David: Disabled, needs a thorough test
		//'default' => __( 'Default Settings', 'paytium' )
	);

	return apply_filters( 'pt_admin_tabs', $tabs );

}

function pt_get_marketing_subtabs() {

    $tabs = array ();

    return apply_filters( 'pt_marketing_subtabs', $tabs );

}