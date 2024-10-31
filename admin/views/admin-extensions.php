<?php

/**
 * Paytium Extensions
 *
 * @package    PT
 * @subpackage Views
 * @author     David de Boer <david@davdeb.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$extensions = array (
	array (
		'key' =>        'mailchimp',
        'title'          => __( 'MailChimp', 'paytium' ),
		'image'          => '1',
		'description'    => __( 'Automatically add emails from your customers/users to your MailChimp lists.', 'paytium' ),
		'form-action-id' => 'q6m3r0',
		'form-id'        => '3155591'
	),
	array (
		'key' =>        'moneybird',
        'title'          => __( 'MoneyBird', 'paytium' ),
		'image'          => '1',
		'description'    => __( 'Automatically send payments to MoneyBird and create invoices.', 'paytium' ),
		'form-action-id' => 'e2h8t9',
		'form-id'        => '3155585'
	),
	array (
		'key' =>        'exactonline',
        'title'          => __( 'Exact Online', 'paytium' ),
		'image'          => '1',
		'description'    => __( 'Automatically send payments to your Exact Online administration/invoices.', 'paytium' ),
		'form-action-id' => 'q1m2n6',
		'form-id'        => '3155571'
	),
	array (
		'key' =>        'googleanalytics',
        'title'          => __( 'Google Analytics', 'paytium' ),
		'image'          => '1',
		'description'    => __( 'Add Google Analytics eCommerce tracking to Paytium, track goals and more.', 'paytium' ),
		'form-action-id' => 'p3k3r7',
		'form-id'        => '3155565'
	),
	array (
		'key' =>        'invoices',
        'title'          => __( 'Invoices', 'paytium' ),
		'image'          => '',
		'description'    => __( 'Automatically create invoices after payments, without other software!', 'paytium' ),
		'form-action-id' => 'g7n7s5',
		'form-id'        => '3973792'
	),
	array (
		'key' =>        'customtext',
        'title'          => __( 'Custom text after payment', 'paytium' ),
		'image'          => '',
		'description'    => __( 'Show your own custom messages/posts/pages after payments.', 'paytium' ),
		'form-action-id' => 'g5x4s5',
		'form-id'        => '3155553'
	),
	array (
		'key' =>        'export',
        'title'          => __( 'Export payments', 'paytium' ),
		'image'          => '',
		'description'    => __( 'Easily export your payments to a CSV or Excel file.', 'paytium' ),
		'form-action-id' => 'k7n1f3',
		'form-id'        => '3155547'
	),
	array (
		'key' =>        'emails',
        'title'          => __( 'Automatic emails', 'paytium' ),
		'image'          => '',
		'description'    => __( 'Automatically send emails to customers and admins.', 'paytium' ),
		'form-action-id' => 'q9k5r9',
		'form-id'        => '3155541'
	),
	array (
		'key' =>        'statistics',
        'title'          => __( 'Statistics and reports', 'paytium' ),
		'image'          => '',
		'description'    => __( 'Get an overview of payments per period, payment methods used, and more!', 'paytium' ),
		'form-action-id' => 'b3z8n2',
		'form-id'        => '3155533'
	),
	array (
		'key' =>        'mailpoetnewsletters',
        'title'          => __( 'MailPoet Newsletters', 'paytium' ),
		'image'          => '1',
		'description'    => __( 'Automatically add emails from users to MailPoet newsletters.', 'paytium' ),
		'form-action-id' => 'r8q1a9',
		'form-id'        => '3155559'
	),
)
?>

<div class="wrap">
	<div id="pt-extensions">
		<div id="pt-extensions-content">

			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="pt_extensions_wrap">
                <p class="pt_extensions_wrap_intro">
					<?php _e( 'Vote for new features in <strong>Paytium Plus</strong>! It\'s the professional version of Paytium, starting at €49 per year. You get three votes. The commercial version makes development and support of all versions sustainable, so you get a <strong>higher quality</strong> plugin.', 'paytium' ); ?></p>

                <p class="pt_extensions_wrap_intro">
					<?php _e( 'By voting you are automatically subscribed to the Paytium newsletter, and you can unsubscribe at anytime.', 'paytium' ); ?>
                </p>

                <p class="pt_extensions_wrap_intro"><?php _e( 'Other suggestions? Send an email to <a href="mailto:support@paytium.nl">support@paytium.nl</a>.', 'paytium' ); ?>
                </p>

                <p class="pt-votes-left" style="display: none;">
					<?php _e( 'You have 3 votes left!', 'paytium' ); ?>
                </p>

				<ul class="products">

					<?php
					shuffle( $extensions );
					foreach ( $extensions as $extension ) : ?>

						<li class="product">

							<?php if ( ! empty( $extension['image'] ) ) { ?>
								<img
									src=" <?php echo PT_URL . 'admin/extension_logos/' . str_replace( ' ', '', strtolower( $extension['key'] ) ) . '.png'; ?>"/>
							<?php } else { ?>
								<h2><?php echo esc_attr($extension['title']) ?></h2>
							<?php } ?>

							<p><?php echo esc_attr($extension['description']) ?></p>

							<?php include( PT_PATH . 'admin/views/admin-extensions-interest-form.php' ); ?>

						</li>

					<?php endforeach; ?>

				</ul>
                <p class="pt-voted-note-large" style="display: none;" >
					<?php _e( 'You\'ve voted three times! Thank you!', 'paytium' ); ?>
                </p>
			</div>

		</div>
		<!-- .pt-extensions-content -->
	</div>
	<!-- .pt-extensions -->
</div><!-- .wrap -->

<script type="text/javascript">

    //localStorage.removeItem('PaytiumVotes');
    //localStorage.setItem('PaytiumVotes', '0');

    jQuery(document).ready(function ($) {

        var ptFeatures = $(this);
        var votes = 3;

        // If there are 0votex left in storage, don't show features, do show Thank you message
        if (localStorage.getItem('PaytiumVotes') == '0') {
            $(ptFeatures).find(".products").each(function (index, element) {
                $(element).hide();
                $(".pt-voted-note-large").show();
            });
        } else {
            $(".products").show();
        }

        // When vote button clicked:
        $("button").click(function (e) {

            // Decrease remaining of votes by one
            votes--;

            // If votes are two or one, show warning with remaining votes
            if (votes == 2 || votes == 1) {
                $(".pt-votes-left").text('You have ' + votes + ' votes left!').fadeIn();
            }

            // If votes are zero:
            if (votes == 0) {

                // Store votes in localStorage
                localStorage.setItem('PaytiumVotes', votes);

                // Remove features, do show Thank you message
                $(ptFeatures).find(".products").each(function (index, element) {
                    console.log('found');
                    $(element).fadeOut();
                    $(".pt-votes-left").fadeOut();
                    $(".pt-voted-note-large").fadeIn();
                });

            }

        });

    });

</script>

<script type="text/javascript"
        src="//static.mailerlite.com/js/w/webforms.min.js?vb01ce49eaf30b563212cfd1f3d202142"></script>
