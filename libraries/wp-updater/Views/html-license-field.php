<tr class="wp-updater-license-row <?php echo sanitize_html_class( $plugin->get_license_status() ); ?>">
	<td colspan="5">
		<label><?php echo sprintf( __( '%s license:', 'paytium' ), $plugin->get_name() ); ?>&nbsp;
			<input
				type="text"
				value="<?php echo esc_attr( $plugin->get_license_key() ); ?>"
				class="wp-updater-license-input"
				placeholder="<?php echo __( 'Your license key', 'paytium' ); ?>"
				data-plugin="<?php echo esc_attr( $plugin->plugin_basename ); ?>"
			>
		</label>
		<span class="waiting spinner" style="float: none; vertical-align: top;"></span><?php

		if ( $plugin->get_license_status() == 'expired' ) {
			?><em><?php echo __( 'Your license has expired.', 'paytium' ); ?>
            <a href="//www.paytium.nl/extensies/" target="_blank"><?php echo __( 'Please renew it to receive plugin updates.', 'paytium' ); ?></a></em><?php
		} else {
			?><em><?php echo sprintf( __( 'Get your license from %spaytium.nl%s, add it here and press Enter to activate it.', 'paytium' ), '<a href="https://www.paytium.nl/account/" target="_blank" >', '</a>' ); ?></em><?php
		}

        ?><span class="pt-license-message"></span><?php

		if ( $plugin->client->is_update_available() && $plugin->get_license_status() != 'valid' ) {
			require 'html-invalid-update-available.php';
		}

	?></td>
</tr>