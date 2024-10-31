<?php

/**
 * Show a newsletter opt-in after plugin install/activate in admin dashboard.
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

<div id="pt-install-notice" class="updated">

	<div id="mlb2-3155921" class="ml-subscribe-form ml-subscribe-form-3155921">

		<div class="subscribe-form ml-block-success" style="display:none">
			<div class="form-section mb0">
				<p>
					<?php _e( 'Thank you for subscribing to the Paytium newsletter!', 'paytium' ); ?>
				</p>
			</div>
		</div>

        <!-- FOR NEW PLUGIN - UPDATE BELOW ID IN ACTION URL -->
		<form class="ml-block-form" action="//app.mailerlite.com/webforms/submit/u6x4u9" data-id="177069"
		      data-code="c7d6k5" method="POST" target="_blank">
			<div class="subscribe-form horizontal">
				<div class="form-section horizontal" style="display: inline">
					<div class="form-group ml-field-email ml-validate-required ml-validate-email"
					     style="display: inline">
						<span
							class="subscribe-message"><?php _e( 'Subscribe for iDEAL news and Paytium updates:', 'paytium' ); ?></span>
						<input style="display: inline" type="text" name="fields[email]" class="form-control"
						       placeholder="Email*" value="<?php echo wp_get_current_user()->user_email; ?>">
					</div>
				</div>
				<div class="form-section horizontal" style="display: inline">
					<button type="submit" class="primary">
						<?php _e( 'Subscribe now!', 'paytium' ); ?>
					</button>

					<button disabled="disabled" style="display: none;" type="button" class="loading">
						<img src="//static.mailerlite.com/images/rolling.gif" width="20" height="20"
						     style="width: 20px; height: 20px;">
					</button>

				</div>

				<a href="<?php echo esc_url( add_query_arg( 'pt-dismiss-newsletter-nag', 1 ) ); ?>"
				   class="button-secondary"><?php _e( 'Hide this', 'paytium' ); ?></a>

				<div class="clearfix" style="clear: both;"></div>
				<input type="hidden" name="ml-submit" value="1"/>
			</div>
		</form>

		<script>
			function ml_webform_success_3155921() {
				jQuery('.ml-subscribe-form-3155921 .ml-block-success').show();
				jQuery('.ml-subscribe-form-3155921 .ml-block-form, .subscribe-message').hide();
                window.location.search += '&pt-dismiss-newsletter-nag=1';
			}
		</script>

	</div>
</div>
<script type="text/javascript"
        src="//static.mailerlite.com/js/w/webforms.min.js?vb01ce49eaf30b563212cfd1f3d202142"></script>