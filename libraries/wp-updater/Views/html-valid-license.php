<tr class="wp-updater-license-row <?php echo sanitize_html_class( $plugin->get_license_status() ); ?>">
	<td colspan="5">
        <label><?php
		echo sprintf( __( 'Your license for %s is active.', 'paytium' ), $plugin->get_name() ); ?>
		<a
			href="javascript:void(0);"
			class="deactivate"
			data-plugin="<?php echo esc_attr( $plugin->plugin_basename ); ?>"
		><?php echo __( 'Deactivate license', 'paytium' ); ?></a></label>
		<span class="waiting spinner" style="float: none; vertical-align: top;"></span>
	</td>
</tr>