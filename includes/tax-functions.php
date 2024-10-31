<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calculate amount excluding taxes.
 *
 * Calculate and get the amount excluding the passed tax percentage.
 *
 * @since 1.5.0
 *
 * @param $amount
 * @param $percentage
 * @return int
 */
function pt_calculate_amount_excluding_tax( $amount, $percentage ) {
	$amount_excl_tax = $amount / ( 1 + ( $percentage / 100 ) );

	return pt_user_amount_to_float( $amount_excl_tax );
}


/**
 * Calculate tax amount.
 *
 * Calculate and get the tax amount based on the passed amount and percentage.
 *
 * @since 1.5.0
 *
 * @param $amount
 * @param $percentage
 * @return float Tax amount.
 */
function pt_calculate_tax_amount( $amount, $percentage ) {
	$amount_excl = pt_calculate_amount_excluding_tax( $amount, $percentage );

	return pt_user_amount_to_float( $amount - $amount_excl );
}
