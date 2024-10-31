<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

wp_nonce_field( 'pt_payment_details', 'pt_payment_nonce' );
$source_link = get_post_meta($payment->id, '_source_link', true);
$source_id = get_post_meta($payment->id, '_source_id', true);
$source_title = get_the_title($source_id) != '' ?  get_the_title($source_id) : $source_link;

if ($payment->subscription_payment_status == 'renewal' && !get_post_meta($payment->id, '_source_id', true)) {
	$first_payment_id = pt_get_first_payment_id($payment->subscription_id);
	$source_link = get_post_meta($first_payment_id, '_source_link', true);
	$source_id = get_post_meta($first_payment_id, '_source_id', true);
	$source_title = get_the_title($source_id) != '' ?  get_the_title($source_id) : $source_link;
}
$my_profile_page = isset( $_GET['page'] ) && $_GET['page'] == 'pt-my-profile';
?>

<div class='option-group'>

	<label for='payment-id'><?php _e( 'Payment ID', 'paytium' ); ?></label>
	<span class="option-value"><?php echo $payment->id; ?></span>

</div>

<div class='option-group'>

	<label for='transaction-id'><?php _e( 'Transaction ID', 'paytium' ); ?></label>
	<span class="option-value"><?php echo $payment->get_transaction_id(); ?></span>

</div>

<div class='option-group'>

	<label for='payment-date'><?php _e( 'Payment time', 'paytium' ); ?></label>
	<span class="option-value"><?php echo $payment->get_payment_date(); ?></span>

</div>

<div class='option-group'>

	<label for='payment-status'><?php _e( 'Payment status', 'paytium' ); ?></label>

    <?php if (!$my_profile_page) : ?>
	<select class='' name='payment_status' id="payment-status"><?php
		foreach ( pt_get_payment_statuses() as $key => $value ) :

			?>
			<option <?php selected( $payment->status, $key ); ?> value='<?php echo esc_attr( $key ); ?>'><?php
			echo esc_html( $value );
			?></option><?php

		endforeach;
		?></select>
    <div class="option-description">
		<?php echo sprintf( __( 'Read more about %spayment statuses%s.', 'paytium' ), '<a href="https://www.paytium.nl/handleiding/betalingen-beheren/#betekenis-van-statussen" target="_blank">', '</a>' ); ?>
    </div>
    <?php else: ?>
        <span class="option-value"><?php echo pt_get_payment_statuses()[$payment->status]; ?></span>
	<?php endif; ?>

</div>

<?php if (!$my_profile_page) : ?>
    <div class='option-group'>

        <label for='order-status'><?php _e( 'Order status', 'paytium' ); ?></label>
        <select class='' name='order_status' id="order-status"><?php
            foreach ( pt_get_order_statuses() as $key => $value ) :

                ?>
                <option <?php selected( $payment->order_status, $key ); ?> value='<?php echo esc_attr( $key ); ?>'><?php
                echo esc_html( $value );
                ?></option><?php

            endforeach;
            ?></select>

    </div>
<?php endif; ?>

<div class='option-group'>

	<label for='claimer'><?php _e( 'Amount', 'paytium' ); ?></label>
	<span class="option-value"><?php echo esc_html( pt_float_amount_to_currency( $payment->get_amount(), $payment->currency ) ); ?></span>

</div>

<div class='option-group'>

	<label for='claimer'><?php _e( 'Description', 'paytium' ); ?></label>
	<span class="option-value"><?php echo $payment->get_description(); ?></span>

</div>
<?php if($source_link) : ?>
<div class='option-group'>

    <label for='source'><?php _e( 'Source', 'paytium' ); ?></label>
    <a href="<?php echo $source_link; ?>" class="option-value" target="_blank"><?php echo $source_title; ?></a>

</div>
<?php endif;
