<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * @var PT_Payment $payment
 */
?>
<div class="pt-failed-meta-box">
    <span class="pt-failed-meta-box-message"><?php echo 'Creating payment failed: ' . get_post_meta($payment->id, '_pt_payment_error', true); ?></span>
</div>

