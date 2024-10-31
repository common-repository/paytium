<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * @var PT_Payment $payment
 */

$files = unserialize(get_post_meta($payment->id, '_pt-uploaded-files', true));
$i = 1;
?>

<div class="pt-files">
    <?php foreach($files as $name => $url) : ?>
        <p>
            <span>#<?php echo $i ?></span>
            <a href="<?php echo $url; ?>" class="pt-uploaded-file" target="_blank"><?php _e($name, 'paytium'); ?></a>
        </p>
    <?php $i++; endforeach; ?>
</div>

