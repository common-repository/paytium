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

<div id="mlb2-<?php echo $extension['form-id'] ?>"
     class="ml-subscribe-form ml-subscribe-form-<?php echo $extension['form-id'] ?>">

	<div class="subscribe-form ml-block-success" style="display:none">
		<div class="form-section mb0">
			<p class="paytium-extension-success-message"><?php _e( 'Thanks, I\'m deciding what to build, and your vote helps!', 'paytium' ); ?></p>
		</div>
	</div>

	<form class="ml-block-form" action="//app.mailerlite.com/webforms/submit/<?php echo $extension['form-action-id'] ?>"
	      data-id="177069" data-code="c7d6k5" method="POST" target="_blank">
		<div class="subscribe-form horizontal">
			<div class="form-section horizontal" style="display: inline">
				<div class="form-group ml-field-email ml-validate-required ml-validate-email" style="display: inline">
					<input style="display: none" type="text" name="fields[email]" class="form-control"
					       placeholder="Email*" value="<?php echo wp_get_current_user()->user_email; ?>">
				</div>
			</div>
			<div class="form-section horizontal test" style="display: inline;">
				<button type="submit" class="primary">
					<?php _e( 'I need this!', 'paytium' ); ?>
				</button>

				<button disabled="disabled" style="display: none;" type="button" class="loading">
					<img src="//static.mailerlite.com/images/rolling.gif" width="20" height="20"
					     style="width: 20px; height: 20px;">
				</button>

			</div>

			<div class="clearfix" style="clear: both;"></div>
			<input type="hidden" name="ml-submit" value="1"/>
		</div>
	</form>

	<script>
		function ml_webform_success_<?php echo $extension['form-id'] ?>() {
			jQuery('.ml-subscribe-form-<?php echo $extension['form-id'] ?> .ml-block-success').show();
			jQuery('.ml-subscribe-form-<?php echo $extension['form-id'] ?> .ml-block-form, .subscribe-message').hide();
		}
	</script>

</div>

