<div class="wrap">

	<h1 style="margin-bottom: 10px;"><?php _e( 'Paytium setup wizard', 'paytium' ); ?></h1>

	<?php $current_step = isset( $_GET['step'] ) ? $_GET['step'] : 'connect-mollie'; ?>
	<div id='pt-setup-wizard'>

		<div class='tabs-panels-wrap'>
			<div class='tabs' id="setup-wizard-tabs">
				<ul>
					<li class="<?php echo $current_step == 'connect-mollie' ? 'active' : ''; ?>">
						<a href='javascript:void(0);'
						   data-target='connect-mollie'><?php _e( '1. Mollie account', 'paytium' ); ?></a>
					</li>
					<li class="<?php echo $current_step == 'create-profile' ? 'active' : ''; ?>">
						<a href='javascript:void(0);'
						   data-target='create-profile'><?php _e( '2. Website profile', 'paytium' ); ?></a>
					</li>

					<li class="<?php echo $current_step == 'payment-test' ? 'active' : ''; ?>">
						<a href='javascript:void(0);'
						   data-target='payment-test'><?php _e( '3. Test payment', 'paytium' ); ?></a>
					</li>
					<li class="<?php echo $current_step == 'first-product' ? 'active' : ''; ?>">
						<a href='javascript:void(0);'
						   data-target='first-product'><?php _e( '4. Payment form', 'paytium' ); ?></a>
					</li>
				</ul>
			</div>

			<div class='panels' id="setup-wizard-panels">

				<div id='connect-mollie' class='setup-wizard-panel'
				     style='<?php echo $current_step != 'connect-mollie' ? 'display: none;' : ''; ?>'><?php
					require_once PT_PATH . 'admin/views/setup-wizard/connect-mollie.php';
					?></div>
				<div id='create-profile' class='setup-wizard-panel'
				     style='<?php echo $current_step != 'create-profile' ? 'display: none;' : ''; ?>'><?php
					require_once PT_PATH . 'admin/views/setup-wizard/create-profile.php';
					?></div>

				<div id='payment-test' class='setup-wizard-panel'
				     style='<?php echo $current_step != 'payment-test' ? 'display: none;' : ''; ?>'><?php
					require_once PT_PATH . 'admin/views/setup-wizard/payment-test.php';
					?></div>
				<div id='first-product' class='setup-wizard-panel'
				     style='<?php echo $current_step != 'first-product' ? 'display: none;' : ''; ?>'><?php
					require_once PT_PATH . 'admin/views/setup-wizard/first-product.php';
					?></div>

			</div>

			<div class='clear'></div>
		</div>

	</div>

</div>
