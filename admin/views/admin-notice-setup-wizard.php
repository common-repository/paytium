<?php

/**
 * Show notice after plugin install/activate in admin dashboard.
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

<div id="pt-admin-notice" class="updated">
	<p>
		<?php _e( 'Paytium is now installed.', 'paytium' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pt-setup-wizard' ) ); ?>" class="button-primary"
		   style="vertical-align: baseline;"><?php _e( 'Go through the setup wizard', 'paytium' ); ?></a>
		<a href="<?php echo esc_url( add_query_arg( 'pt-dismiss-install-nag', 1 ) ); ?>"
		   class="button-secondary"><?php _e( 'Hide this', 'paytium' ); ?></a>
	</p>
</div>
