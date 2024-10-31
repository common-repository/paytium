<div class="pt-alert pt-alert-info" id="pt-new-mollie-account-email-confirmation" style="display: none;">
        <?php echo __( 'You\'ve just created a new Mollie account and will receive a confirmation email.<br />Click the activation URL in that email before you continue!', 'paytium' ); ?>
</div>

<div class="pt-alert pt-alert-danger pt-no-account-details-restart-wizard" style="display: none;">
    <?php echo __('No Mollie username or password found!', 'paytium' )  ?>
    <a href="javascript:void(0);" class="tab-button"
       data-target="connect-mollie"><?php echo __( 'Go back to step 1', 'paytium' ); ?> &rarr;</a>
</div>

<!-- Not verified -->
<div id="active-profiles" class="boxed" style="display: none;">

        <h3><?php _e( 'Choose a website profile', 'paytium' ); ?></h3>

        <p>
	        <?php _e( 'Your Mollie account already contains website profiles, you can select one of those or create a new profile below.', 'paytium' ); ?>
	        <?php _e( 'To view all profiles go to', 'paytium' ); ?>
            <a href='https://www.mollie.com/dashboard/settings/profiles' target='_blank'>mollie.com</a>.
        </p>

        <table class="profiles wp-list-table widefat fixed striped posts">
            <thead>
            <th id="name">Name</th>
            <th id="website">Website</th>
            <th id="select"></th>
            </thead>
            <tbody></tbody>
        </table>
</div>

<div id="create-new-profile" class="boxed" style="display: none;">

	<h3><?php _e( 'Create a new website profile', 'paytium' ); ?></h3>

    <p>
		<?php _e( 'For every website you want to receive payments on, you will need to create a website profile.', 'paytium' ); ?>
		<?php _e( 'Complete the below details and create your website profile!', 'paytium' ); ?>
    </p>

    <p><span class="dashicons dashicons-warning"></span>
		<?php _e( 'All fields are required.', 'paytium' ); ?>
    </p>

    <div class="ajax-response-create-profile"></div>

	<form method="">
		<div class="form-group">
			<label><?php _e( 'Name', 'paytium' ); ?>:</label>
            <input type="text" name="name" class="" value="<?php echo get_bloginfo( 'name' ); ?>">
		</div>
		<div class="form-group">
			<label><?php _e( 'Website', 'paytium' ); ?>:</label>
            <input type="text" name="website" class="" value="<?php echo site_url(); ?>">
		</div>
		<div class="form-group">
			<label><?php _e( 'Email', 'paytium' ); ?>:</label>
            <input type="text" name="email" class="" value="<?php echo get_option( 'admin_email' ); ?>">
		</div>
		<div class="form-group">
			<label><?php _e( 'Phone', 'paytium' ); ?>:</label>
            <input type="text" name="phone" class="">
		</div>

		<a href="javascript:void(0);" id="create-mollie-profile" class="button button-primary"
		   style="margin-top: 10px;"><?php _e( 'Create new website profile', 'paytium' ); ?></a>

		<div class="spinner" style="margin-top: 14px; float: none;"></div>

	</form>

	<a href="javascript:void(0);" class="button button-primary continue-button tab-button" data-target="payment-test"
	   style="display: none;"><?php _e( 'Go to the next step', 'paytium' ); ?> &rarr;</a>

</div>

<!-- Verified -->
<div id="profile-connected" class="boxed" style="display: none;">
    <div class="pt-alert pt-alert-success">
        <?php _e( 'A Mollie website profile is now connected to this site.', 'paytium' ); ?>
    </div>

    <br />

    <div style="text-align: center; margin-top: 10px;">
        <a href="javascript:void(0);" class="button button-primary continue-button tab-button"
           data-target="payment-test"><?php _e( 'Continue to next step', 'paytium' ); ?> &rarr;</a>
    </div>

</div>

