
<div class="pt-alert pt-alert-info" id="pt-account-details-found-continue-wizard" style="display: none;">
	<?php echo __('Mollie account found,', 'paytium' )  ?>
    <a href="javascript:void(0);" class="tab-button"
       data-target="create-profile"><?php echo __( 'continue to step 2', 'paytium' ); ?></a>
	<?php echo __(' or connect another account below.', 'paytium' )  ?>
</div>

<p><img class="author-image" src="<?php echo PT_URL . 'admin/img/daviddeboer.png'; ?>"/>
    <?php _e( 'I\'m David de Boer, online payments expert since 2008 and developer of the Paytium plugin. I\'ll be guiding you while you setup payments on this website.', 'paytium' ); ?>
</p>

<p><?php echo sprintf(__( 'To accept payments on any website, you will need an account at a \'payment provider\'. Paytium works with Mollie.com, because in my experience they are the best, with over %s customers!', 'paytium' ), '60.000' ); ?>
</p>

<p><?php _e( 'Mollie is a certified payments specialist and permanently supervised by the Dutch central bank, \'De Nederlandsche Bank\'. They will process your payments and send them to your bank account on a daily basis.', 'paytium' ); ?>
</p>

<ul>
    <li>
        <i class="dashicons dashicons-yes"></i> <?php echo  __( 'Creating a Mollie account is risk and cost free', 'paytium'); ?>
    </li>
    <li>
        <i class="dashicons dashicons-yes"></i> <?php echo  __( 'There are no setup, monthly or hidden costs', 'paytium'); ?>
    </li>
    <li>
        <i class="dashicons dashicons-yes"></i> <?php echo  __( 'You only pay a small fee for completed transactions', 'paytium'); ?>
    </li>
    <li>
        <i class="dashicons dashicons-yes"></i> <?php echo sprintf( __( 'Questions? Get in touch via the %s support forum %s', 'paytium'), '<a
                href="' . esc_url( 'https://wordpress.org/support/plugin/paytium' ) . '" target="_blank">', '</a>' ); ?>
    </li>
</ul>

<h3><?php _e( 'Connecting Mollie to your website', 'paytium' ); ?></h3>
<p><?php _e( 'A Mollie account needs to be connected to this website. You can create a new Mollie account or connect an existing one. If you are not sure what to do, choose to create a new account. You can always switch to another account or change details later, so there is no risk in getting started with a new account today.', 'paytium' ); ?></p>

<div style="text-align: center; margin-bottom: 10px;">

	<a href="javascript:void(0);" class="button button-primary target-button"
	   data-target="no-mollie-account"><?php _e( 'I don\'t have a Mollie account', 'paytium' ); ?></a>
    <a href="javascript:void(0);" class="button button-secondary target-button"
       data-target="have-mollie-account"><?php _e( 'I have a Mollie account', 'paytium' ); ?></a>&nbsp;
</div>

<div id="have-mollie-account" class="boxed target-area" style="display: none;">

	<h3><?php _e( 'Existing Mollie account', 'paytium' ); ?></h3>

        <p><?php _e('Because you already have a Mollie account, you will need to connect your Mollie account manually. To do this:','paytium') ?></p>
        <ol>
            <li>
                <?php _e('Go to your Mollie dashboard on <a href="https://www.mollie.com/dashboard/settings/profiles" target="_blank">Mollie.com</a>.','paytium') ?>
            </li>
            <li>
				<?php _e('If you don\'t have a website profile for this website: in the Mollie dashboard go to <a href="https://www.mollie.com/dashboard/settings/profiles">"Settings"</a>, choose "Create a website profile".','paytium') ?>
            </li>
            <li>
				<?php _e('After you created a website profile or if you already have one for this website: go to <a href="https://www.mollie.com/dashboard/developers/api-keys">"Developers"</a> in the Mollie dashboard.','paytium') ?>
            </li>
            <li>
				<?php _e('Now, copy and paste the Live and Test API key\'s to the below fields with the "Copy" link.','paytium') ?>
            </li>
        </ol>

        <div class="sw-keys">
            <label for="sw-live-api-key">
		        <?php _e('Live API key','paytium') ?>
                <input type="text" id="sw-live-api-key" name="sw-live-api-key" value="<?php echo esc_attr(get_option('paytium_live_api_key')); ?>">
            </label>
            <label for="sw-test-api-key">
                <?php _e('Test API key','paytium') ?>
                <input type="text" id="sw-test-api-key" name="sw-test-api-key" value="<?php echo esc_attr(get_option('paytium_test_api_key')); ?>">
            </label>
            <p><?php _e('Now click "Continue" to go to the next step of this Setup Wizard (step 2 will be skipped).','paytium') ?></p>
        </div>

        <a href="javascript:void(0);" class="button button-primary continue-button tab-button" id="existing-account-continue">
            <?php _e( 'Continue', 'paytium' ); ?> &rarr;
        </a>

</div>

<div id="no-mollie-account" class="boxed target-area" style="display: none;">

	<h3><?php _e( 'Register with Mollie', 'paytium' ); ?></h3>

	<p><span class="dashicons dashicons-lock"></span>
        <?php _e( 'Your details will be sent to Mollie over a secure and encrypted connection!', 'paytium' ); ?></p>

    <p><span class="dashicons dashicons-warning"></span>
		<?php _e( 'All fields are required.', 'paytium' ); ?>
    </p>

	<div class="ajax-response-connect-mollie"></div>

	<form method="">
		<div class="form-group">
			<label><?php _e( 'Name', 'paytium' );
                ?>:</label>
            <input type="text" name="name" class="">

		</div>
		<div class="form-group">
			<label><?php _e( 'Company name', 'paytium' );
                ?>:</label>
            <input type="text" name="company_name" class="">

		</div>
		<div class="form-group">
			<label><?php _e( 'Email', 'paytium' );
                ?>:</label>
            <input type="text" name="email" class="" value="<?php echo get_option( 'admin_email' ); ?>">

		</div>
		<div class="form-group">
			<label><?php _e( 'Address', 'paytium' );
                ?>:</label>
            <input type="text" name="address" class="">

		</div>
		<div class="form-group">
			<label><?php _e( 'Zip code', 'paytium' );
                ?>:</label>
            <input type="text" name="zipcode" class="">

		</div>
		<div class="form-group">
			<label><?php _e( 'City', 'paytium' );
                ?>:</label>
            <input type="text" name="city" class="">

		</div>
		<div class="form-group">
            <label><?php _e( 'Country', 'paytium' );
				?>:</label>
            <select name="country">
                <option value="NL" selected="selected"><?php _e( 'Netherlands', 'paytium' ); ?></option>
                <option value="BE"><?php _e( 'Belgium', 'paytium' ); ?></option>
                <option value="--" disabled><?php _e( '---------', 'paytium' ); ?></option>
                <option value="AT"><?php _e( 'Austria', 'paytium' ); ?></option>
                <option value="BE"><?php _e( 'Belgium', 'paytium' ); ?></option>
                <option value="BG"><?php _e( 'Bulgaria', 'paytium' ); ?></option>
                <option value="CY"><?php _e( 'Cyprus', 'paytium' ); ?></option>
                <option value="CZ"><?php _e( 'Czech Republic', 'paytium' ); ?></option>
                <option value="DE"><?php _e( 'Germany', 'paytium' ); ?></option>
                <option value="DK"><?php _e( 'Denmark', 'paytium' ); ?></option>
                <option value="EE"><?php _e( 'Estonia', 'paytium' ); ?></option>
                <option value="ES"><?php _e( 'Spain', 'paytium' ); ?></option>
                <option value="FI"><?php _e( 'Finland', 'paytium' ); ?></option>
                <option value="FR"><?php _e( 'France', 'paytium' ); ?></option>
                <option value="GB"><?php _e( 'United Kingdom', 'paytium' ); ?></option>
                <option value="GR"><?php _e( 'Greece', 'paytium' ); ?></option>
                <option value="HU"><?php _e( 'Hungary', 'paytium' ); ?></option>
                <option value="HR"><?php _e( 'Croatia', 'paytium' ); ?></option>
                <option value="IE"><?php _e( 'Ireland, Republic of (EIRE)', 'paytium' ); ?></option>
                <option value="IT"><?php _e( 'Italy', 'paytium' ); ?></option>
                <option value="LT"><?php _e( 'Lithuania', 'paytium' ); ?></option>
                <option value="LU"><?php _e( 'Luxembourg', 'paytium' ); ?></option>
                <option value="LV"><?php _e( 'Latvia', 'paytium' ); ?></option>
                <option value="MT"><?php _e( 'Malta', 'paytium' ); ?></option>
                <option value="NL"><?php _e( 'Netherlands', 'paytium' ); ?></option>
                <option value="PL"><?php _e( 'Poland', 'paytium' ); ?></option>
                <option value="PT"><?php _e( 'Portugal', 'paytium' ); ?></option>
                <option value="RO"><?php _e( 'Romania', 'paytium' ); ?></option>
                <option value="SE"><?php _e( 'Sweden', 'paytium' ); ?></option>
                <option value="SI"><?php _e( 'Slovenia', 'paytium' ); ?></option>
                <option value="SK"><?php _e( 'Slovakia', 'paytium' ); ?></option>

            </select>

        </div>
		<a href="javascript:void(0);" id="create-mollie-account" class="button button-primary"
		   style="margin-top: 10px;"><?php _e( 'Continue', 'paytium' ); ?></a>

		<div class="spinner" style="margin-top: 14px; float: none;"></div>

	</form>

	<a href="javascript:void(0);" class="button button-primary continue-button tab-button" data-target="create-profile"
	   style="display: none;"><?php _e( 'Go to the next step', 'paytium' ); ?> &rarr;</a>

</div>
