<?php

/**
 * Add admin "Please review" notice
 *
 * @package    PT
 * @subpackage Views
 * @author     David de Boer <david@davdeb.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div id="pt-admin-notice-please-review" class="notice notice-info">
    <div style="margin: 10px;">
        <img class="author-image" src="<?php echo PT_URL . 'admin/img/daviddeboer.png'; ?>"/>

        <span class="dashicons dashicons-star-filled"></span>
        <span class="dashicons dashicons-star-filled"></span>
        <span class="dashicons dashicons-star-filled"></span>
        <span class="dashicons dashicons-star-filled"></span>
        <span class="dashicons dashicons-star-filled"></span>

        <p>
			<?php _e( 'It looks like you are using Paytium successfully, congratulations! Paytium is just a small plugin created by me, David. I\'m an independent developer, not a huge company. If you think Paytium is great, please post a review, that would really help me!',
				'paytium' ); ?>
        </p>
        <p>
            <a href="https://wordpress.org/support/view/plugin-reviews/paytium" class="button-primary"
               style="vertical-align: baseline;"
               target="_blank"><?php _e( 'Yes, I want to help you!', 'paytium' ); ?></a>
            &nbsp;&nbsp;&nbsp;
            <a href="<?php echo esc_url( add_query_arg( 'pt-dismiss-please-review-nag', 1 ) ); ?>"
               class="button-secondary"><?php _e( 'No, hide this message...', 'paytium' ); ?></a>
        </p>
    </div>
</div>