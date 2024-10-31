<?php

/**
 * Add admin notice when site already received live payments
 * & completing the Setup Wizard is not necessary
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

<div id="pt-admin-notice" class="notice notice-warning">
	<p>
		<?php _e( 'You have already received "live" payments, are you sure you need to go through the Setup Wizard again?', 'paytium' ); ?>

        <a href="<?php echo esc_url( admin_url( 'admin.php?page=paytium' ) ); ?>" class="button-primary"
           style="vertical-align: baseline;"><?php _e( 'No, back to Settings', 'paytium' ); ?></a>

		<a href="<?php echo esc_url( add_query_arg( 'pt-dismiss-has-live-payments-nag', 1 ) ); ?>"
		   class="button-secondary"><?php _e( 'Yes, hide this', 'paytium' ); ?></a>
	</p>
</div>
