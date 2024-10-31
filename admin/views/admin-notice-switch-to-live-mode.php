<?php

/**
 * Add admin notice when site is in test mode
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

<style>
	#pt-admin-notice .button-primary,
	#pt-admin-notice .button-secondary {
		margin-left: 15px;
	}
</style>

<div id="pt-admin-notice" class="notice notice-info">
	<p>
		<?php _e( 'You\'re site is currently in test mode. When you are ready, switch to live mode to accept real payments.', 'paytium' ); ?>

        <a href="<?php echo esc_url( admin_url( 'admin.php?page=paytium' ) ); ?>" class="button-primary"
           style="vertical-align: baseline;"><?php _e( 'Go to Settings', 'paytium' ); ?> &rarr;</a>

        <a href="<?php echo esc_url( add_query_arg( 'pt-dismiss-switch-to-live-mode-nag', 1 ) ); ?>"
           class="button-secondary"><?php _e( 'Hide this', 'paytium' ); ?></a>

	</p>
</div>
