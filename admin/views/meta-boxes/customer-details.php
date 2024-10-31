<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( trim( $payment->get_field_data_html() ) == false ) {
	_e( 'No customer details registered.', 'paytium' );

	return;
}

echo $payment->get_field_data_html();