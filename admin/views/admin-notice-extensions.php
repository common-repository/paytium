<?php

/**
 * Show notice after plugin install/activate in admin dashboard.
 * Use a random extension description to increase user interest.
 *
 * @package    PT
 * @subpackage Views
 * @author     David de Boer <david@davdeb.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$extensions_descriptions =
	array (
		__( 'Send email addresses from Paytium to MailChimp?', 'paytium' ),
		__( 'Send automatic emails after payments?', 'paytium' ),
		__( 'Create invoices from payments in MoneyBird or Exact Online?', 'paytium' ),
		__( 'Need detailed statistics about payments?', 'paytium' ),
	);
?>

<style>
	#pt-admin-notice .button-primary,
	#pt-admin-notice .button-secondary {
		margin-left: 15px;
	}
</style>

<div id="pt-admin-notice" class="notice notice-info">
	<p>
		<?php echo esc_attr($extensions_descriptions[ array_rand( $extensions_descriptions ) ]); ?>
		<?php __( ' Or other features and integrations?', 'paytium' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pt-extensions' ) ); ?>" class="button-primary"
		   style="vertical-align: baseline;"><?php _e( 'View extra features', 'paytium' ); ?> &rarr;</a>
		<a href="<?php echo esc_url( add_query_arg( 'pt-dismiss-extensions-nag', 1 ) ); ?>"
		   class="button-secondary"><?php _e( 'Hide this', 'paytium' ); ?></a>
	</p>
</div>
