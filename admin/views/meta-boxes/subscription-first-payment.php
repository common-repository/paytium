<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * @var PT_Payment $payment
 */
?>
<div class="pt-subscription-first-payment-meta-box">

    <table class="widefat pt-subscription-first-payment-table" style="width: 100%">
        <thead>
        <tr>
            <th><?php _e( 'Subscription', 'paytium' ); ?></th>
            <th><?php _e( 'First payment', 'paytium' ); ?>
                <span><?php echo pt_float_amount_to_currency( $payment->get_subscription_first_payment(), $payment->currency ) ?></span></th>
            <th><?php _e( 'Recurring payment', 'paytium' ); ?>
                <span><?php echo pt_float_amount_to_currency( $payment->get_subscription_recurring_payment(), $payment->currency ) ?></span>
            </th>
        </tr>
        </thead>

    </table>

</div>
