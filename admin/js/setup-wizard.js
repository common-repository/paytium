/**
 * Paytium Admin JS
 *
 * @package PT
 * @author  David de Boer <david@davdeb.com>
 */

/* global jQuery, sc_script */
(function ($) {
	'use strict';

	// Set debug flag.
	var script_debug = ( (typeof pt_script != 'undefined' ) && pt_script.script_debug == true);

	$(function () {

		if ( script_debug ) {
			console.log( 'pt_script', pt_script );
		}

		var $body = $( document.body ),
            $this = '',
		    existingAccount = 0;

        /**************************************************************
         * Setup Wizard main tabs
         *************************************************************/

        const $setupWizard = $( '#pt-setup-wizard' );

		// Tab click
		$setupWizard.on( 'click', '#setup-wizard-tabs a, .tab-button', function() {

			if ( $( this ).data( 'target' ) !== undefined ) {

				// Tabs
				var tabs = $( '#pt-setup-wizard' ).find( '#setup-wizard-tabs' );
                tabs.find( 'li.active' ).removeClass( 'active' );
                tabs.find( '[data-target=' + $( this).data( 'target' ) + ']' ).parent( 'li' ).addClass( 'active' );

				// Panel
				var panels = tabs.parent().find( '#setup-wizard-panels' );

				panels.find( '.setup-wizard-panel' ).removeClass( 'active' ).hide();
				panels.find( '.setup-wizard-panel#' + $( this ).data( 'target' ) ).addClass( 'active' ).show();

			}

		});

		// Target button/area to show/hide areas
		$setupWizard.on( 'click', '.target-button', function() {

			if ( $( this ).data( 'target' ) !== undefined ) {

				var target = $( this ).data( 'target' );
				$( '.target-area:not(#' + target + ')' ).removeClass( 'active' ).slideUp();
				$( '.target-area#' + target ).addClass( 'active' ).slideDown();

			}

		});

        /**************************************************************
         * Setup Wizard > Payment form/First product tabs
         *************************************************************/

        // Tab click
        $setupWizard.on('click', '#first-product-tabs a, .first-product-tab-button', function () {

            if ($(this).data('target') !== undefined) {

                // Tabs
                var tabs = $('#pt-setup-wizard').find('#first-product-tabs');
                tabs.find('li.active').removeClass('active');
                tabs.find('[data-target=' + $(this).data('target') + ']').parent('li').addClass('active');

                // Panel
                var panels = tabs.parent().find('#first-product-panels');
                panels.find('.panel').removeClass('active').hide();
                panels.find('.panel#' + $(this).data('target')).addClass('active').show();

            }

        });

        /**************************************************************
         * Check Mollie account details are found in database
         *************************************************************/

        $(document).ready(function () {
            checkAccount($this);
        });

        $body.on('click', 'a[data-target="connect-mollie"]', function () {
            checkAccount($this);
        });

        function checkAccount() {

            $this = $(this);
            var data = {
                action: 'paytium_mollie_check_account_details',
                nonce: paytium.nonce,
            };
            $.post(ajaxurl, data, function (response) {

                if (response.status == 'success') {
                    $('#pt-account-details-found-continue-wizard').show();
                    return false;
                } else {
                    $('#pt-account-details-found-continue-wizard').hide();
                }
            });
        }

        /**************************************************************
         * Existing Mollie account continue
         *************************************************************/

        $body.on( 'click', '#existing-account-continue', function() {

            existingAccount = 1;

            if ($('#sw-test-api-key').val() != '' || $('#sw-live-api-key').val() != '') {

                var data = {
                    nonce: paytium.nonce,
                    action: 'paytium_sw_save_api_keys'
                };

                if ($('#sw-test-api-key').val() != '') {
                    data['test_api_key'] = $('#sw-test-api-key').val();
                }
                if ($('#sw-live-api-key').val() != '') {
                    data['live_api_key'] = $('#sw-live-api-key').val();
                }
                $.post( ajaxurl, data, function( response ) {
                    existing_account_continue()
                });

            }
            else existing_account_continue();

        });

        function existing_account_continue() {
            $('#pt-setup-wizard .tabs li').removeClass('active');
            $('#pt-setup-wizard .tabs li a[data-target="payment-test"]').parent('li').addClass('active');

            $('#connect-mollie').hide();
            $('#payment-test').show();

            $('#pt-setup-payment-test-box').show();
            $('.pt-no-account-details-restart-wizard').hide();
            checkPayment($this);
        }

        /**************************************************************
         * Create Mollie account
         *************************************************************/

		$body.on( 'click', '#create-mollie-account', function() {

			$( this ).next( '.spinner' ).addClass( 'is-active' );

			$this = $( this );
			var data = {
				action: 'paytium_mollie_create_account',
				form: $( this ).closest( 'form' ).serialize(),
				nonce: paytium.nonce,
			};
			$.post( ajaxurl, data, function( response ) {

				response = JSON.parse( response );
				if ( undefined !== response.message ) {
					$this.parent( 'form' ).prev( '.ajax-response-connect-mollie' ).html( response.message );
				}

				if ( response.status == 'success' ) {
					$this.parents( 'form' ).slideUp();
					$this.parents( 'form' ).next( '.continue-button' ).slideDown();
				}

                $('#pt-new-mollie-account-email-confirmation').show();
				$this.next( '.spinner' ).removeClass( 'is-active' );
			});

		});

        /**************************************************************
         * Website profile
         *************************************************************/

        $body.on('click', 'a[data-target="create-profile"]', function () {

            // If profile is already connected, stop processing
            var ProfileStatus = $('#profile-connected').is(':visible');
            if (ProfileStatus) {
                return false;
            }

            // Check that a Mollie username and password are known
            $this = $(this);
            var data = {
                action: 'paytium_mollie_check_account_details',
                nonce: paytium.nonce,
            };
            $.post(ajaxurl, data, function (response) {

                if (response.status == 'error') {
                    $('#active-profiles').hide();
                    $('#create-new-profile').hide();
                    $('.pt-no-account-details-restart-wizard').show();

                    return false;
                } else {
                    $('#create-new-profile').show();
                    $('.pt-no-account-details-restart-wizard').hide();
                }

            });

            $this = $(this);
            var data = {
                action: 'paytium_mollie_check_for_verified_profiles',
                nonce: paytium.nonce,
            };
            $.post(ajaxurl, data, function (response) {

                if (response.status == 'success') {

                    // Remove current content of table
                    $("table.profiles tbody").remove();

                    // If there are no profiles, don't continue with the table of profiles
                    if ($(response.profiles.profile).length < 1) {
                        return false;
                    }

                    if ($(response.profiles.profile).length > 1) {
                        var Profiles = response.profiles.profile;
                    } else {
                        var Profiles = response.profiles;
                    }

                    $.each(Profiles, function (i, val) {

                        // Generate content for table row
                        var table_row_content;
                        table_row_content = '<tr><td>' + val.name + '</td><td>' + val.website + '</td>';
                        table_row_content += '<td><center><a href="#" onclick="return false;" id="update-profile-preference" class="button button-primary"'
                        table_row_content += 'data-profile-hash="' + val.hash + '" data-profile-test-key="' + val.api_keys.test + '" data-profile-live-key="' + val.api_keys.live + '"';
                        table_row_content += '>Select</a></center></td></tr>';

                        // Add table row content to table
                        $("table.profiles").append(table_row_content)
                    });

                    $('#active-profiles').show();
                }
            });

        });

		// Create profile
		$body.on( 'click', '#create-mollie-profile', function() {

			$( this ).next( '.spinner' ).addClass( 'is-active' );

			$this = $( this );
			var data = {
				action: 'paytium_mollie_create_profile',
				form: $( this ).closest( 'form' ).serialize(),
				nonce: paytium.nonce,
			};
			$.post( ajaxurl, data, function( response ) {

				response = JSON.parse( response );
				if ( undefined !== response.message ) {
					$this.parent( 'form' ).prev( '.ajax-response-create-profile' ).html( response.message );
				}

				if ( response.status == 'success' ) {
					$this.parents( 'form' ).slideUp();
					$this.parents( 'form' ).next( '.continue-button' ).slideDown();
                    $('#active-profiles').hide();
				}

				$this.next( '.spinner' ).removeClass( 'is-active' );

			});

		});

        /**************************************************************
         * Update profile preference
         *************************************************************/

        // Check profile status
        $body.on('click', '#update-profile-preference', function () {

            var profile_hash = $(this).data("profile-hash");
            var profile_test_key = $(this).data("profile-test-key");
            var profile_live_key = $(this).data("profile-live-key");

            $('#profile-not-verified, #profile-verified').hide();
            $('.spinner-wrap').show();

            $this = $(this);
            var data = {
                action: 'paytium_mollie_update_profile_preference',
                hash: profile_hash,
                test_key: profile_test_key,
                live_key: profile_live_key,
                nonce: paytium.nonce,
            };
            $.post(ajaxurl, data, function (response) {

                if ('success' == response.status) {
                    $('#active-profiles').hide();
                    $('#create-new-profile').hide();
                    $('#profile-connected').show();

                    $this.parents('form').next('.continue-button').slideDown();
                    $this.next('.spinner').removeClass('is-active');

                }
                $('.spinner-wrap').hide();

            });


        });

		/**************************************************************
		 * Payment test
		 *************************************************************/

        $(document).ready(function () {
            checkPayment($this);
        });
        $body.on('click', '#check-payment', function () {
            checkPayment($this);
        });

        $body.on('click', 'a[data-target="payment-test"]', function () {

            // Check that a Mollie username and password are known
            $this = $(this);
            var data = {
                action: 'paytium_mollie_check_account_details',
                nonce: paytium.nonce,
            };
            $.post(ajaxurl, data, function (response) {

                if (response.status == 'error') {

                    $('#pt-setup-payment-test-box').hide();

                    $('.pt-no-account-details-restart-wizard').show();

                    return false;
                } else {
                    $('#pt-setup-payment-test-box').show();
                    $('.pt-no-account-details-restart-wizard').hide();
                }

            });

            checkPayment($this);
        });

        function checkPayment() {

            $this = $(this);
            var data = {
                action: 'paytium_check_payment_exists',
                nonce: paytium.nonce,
            };
            $.post(ajaxurl, data, function (response) {

                response = JSON.parse(response);
                if (undefined !== response.message) {
                    $('#payment-test').find('.ajax-response-payment-test').html(response.message);
                }

                if (response.status == 'success') {
                    $('.payment-test-intro').hide();
                    $('.payment-test-button').hide();
                    $('#check-payment').hide();
                    $('#payment-test').find('.ajax-response-payment-test').html(response.message);
                    $('#check-payment-continue-button').show();
                }

            });
        }

        /**************************************************************
         * Create your first payment form
         *************************************************************/

        $body.on('click', 'a[data-target="first-product"]', function () {

            // Check that a Mollie username and password are known
            $this = $(this);
            var data = {
                action: 'paytium_mollie_check_account_details',
                nonce: paytium.nonce,
                existing_account: existingAccount
            };

            $.post(ajaxurl, data, function (response) {

                if (response.status == 'error') {

                    $('#pt-setup-first-payment-form-box').hide();

                    $('.pt-no-account-details-restart-wizard').show();

                    return false;
                } else {
                    $('.pt-no-account-details-restart-wizard').hide();
                    $('#pt-setup-first-payment-form-box').show();
                }

            });

        });

	});

}(jQuery));
