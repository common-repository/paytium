/**
 * Paytium Admin JS
 *
 * @package PT
 * @author  David de Boer <david@davdeb.com>
 */

/* global jQuery, sc_script */

(function($) {
	'use strict';

	// Set debug flag.
	let script_debug = ( (typeof sc_script != 'undefined') && sc_script.script_debug == true);

	$(function () {

		if (script_debug) {
			console.log('sc_script', sc_script);
		}

		let $body = $( document.body );

		$body.find( '.sc-license-wrap button.sc-license-action' ).on( 'click.eddLicenseActivate', function( event ) {

			event.preventDefault();

			let button = $(this);
			let licenseWrap = button.closest( '.sc-license-wrap' );
			let licenseInput = licenseWrap.find( 'input.sc-license-input' );

			if ( licenseInput.val().length < 1 ) {

				button.html( sc_strings.activate );
				button.data( 'sc-action', 'activate_license' );
				licenseWrap.find( '.sc-license-message' ).html( sc_strings.inactive_msg ).removeClass( 'sc-valid sc-invalid' ).addClass( 'sc-inactive' );

			} else {

				// WP 4.2+ wants .is-active class added/removed for spinner.
				licenseWrap.find( '.spinner' ).addClass( 'is-active' );

				let data = {
					action: 'sc_activate_license',
					license: licenseInput.val(),
					item: button.data( 'sc-item'),
					sc_action: button.data( 'sc-action' ),
					id: licenseInput.attr( 'id' )
				};

				$.post( ajaxurl, data, function(response) {

					if (script_debug) {
						console.log('EDD license check response', response);
					}

					// WP 4.2+ wants .is-active class added/removed for spinner.
					licenseWrap.find( '.spinner' ).removeClass( 'is-active' );

					if ( response == 'valid' ) {

						button.html( sc_strings.deactivate );
						button.data( 'sc-action', 'deactivate_license' );
						licenseWrap.find( '.sc-license-message' ).html( sc_strings.valid_msg ).removeClass( 'sc-inactive sc-invalid' ).addClass( 'sc-valid' );

					} else if ( response == 'deactivated' ) {

						button.html( sc_strings.activate );
						button.data( 'sc-action', 'activate_license' );
						licenseWrap.find( '.sc-license-message' ).html( sc_strings.inactive_msg ).removeClass( 'sc-valid sc-invalid' ).addClass( 'sc-inactive' );

					} else if ( response == 'invalid' ) {

						licenseWrap.find( '.sc-license-message' ).html( sc_strings.invalid_msg ).removeClass( 'sc-inactive sc-valid' ).addClass( 'sc-invalid' );

					} else if ( response == 'notfound' ) {

						licenseWrap.find( '.sc-license-message' ).html( sc_strings.notfound_msg ).removeClass( 'sc-inactive sc-valid' ).addClass( 'sc-invalid' );

					} else if ( response == 'error' ) {

						licenseWrap.find( '.sc-license-message' ).html( sc_strings.error_msg ).removeClass( 'sc-inactive sc-valid' ).addClass( 'sc-invalid' );
					}
				});
			}
		});

		// Make enter keypress from input box fires off correct activate button in the case of more than one.
		$body.find( '.sc-license-wrap input.sc-license-input').keypress( function ( event ) {

			let licenseInput = $(this);

			if ( event.keyCode == 13 ) {
				event.preventDefault();

				licenseInput.siblings( 'button.sc-license-action:first' ).click();
			}
		});

	});

    // START - Update amount in Setup Wizard > Payment test
    let $body = $( 'body' );
    let ptFormList = $body.find('.pt-checkout-form');

    ptFormList.each(function() {
        let ptForm = $(this);

        ptForm.find('.pt-payment-btn').on('click.ptPaymentBtn', function (event) {
                let finalAmount = '49.95';
                ptForm.find('.pt_amount').val(finalAmount);
        });
    });
    // END - Update amount in Setup Wizard > Payment test


    $(document).ready(function () {

        let current_subscription_option = $('.subscription-option:checked').val();

        $(".paytium-cancel-subscription").bind("click", function (e) {

            $(this).prop('disabled', true);
            e.preventDefault();

            let data = {
                nonce: paytium.nonce,
                'action': 'pt_cancel_subscription',
                'payment_id': $("#payment_id").attr('value'),
                'subscription_id': $("#subscription_id").attr('value'),
                'customer_id': $("#customer_id").attr('value')
            };

            $.post(ajaxurl, data, function (response) {

                let $body = $('body');

                if (response.success == false) {
                    $body.find('.option-group-subscription-cancelled').show();
                    $body.find('.option-group-subscription-cancelled .option-value').text('Cancel failed!');
                    $body.find('.option-group-subscription-cancelled').css('color', '#ba0005');
                }

                if (response.success == true) {

                    // Remove the 'Cancel subscription' button
                    $body.find('#pt_subscription_details #major-publishing-actions').remove();

                    // Change subscription status
                    $body.find('#option-value-subscription-status').text(response.status);
                    $body.find('#option-value-subscription-status').css('color', '#0085ba');

                    // Add 'Cancelled' option row, with cancelledDateTime
                    $body.find('.option-group-subscription-cancelled').show();
                    $body.find('.option-group-subscription-cancelled .option-value').text(response.time);
                    $body.find('.option-group-subscription-cancelled .option-value').css('color', '#0085ba');
                }
            });

        });

        $("#paytium_subscription_update").bind("click", function (e) {

            $(this).prop('disabled', true);
            e.preventDefault();

            let loaderContainer = $( '<div>', {
                'class': 'pt-subscription-update-loading'
            }).appendTo( $body );

            let loader = $( '<div>', {
                'class': 'pt-loader'
            }).appendTo( loaderContainer );

            let new_option = $('.subscription-option:checked'),
                data = {
                 nonce: paytium.nonce,
                'action': 'pt_subscription_update',
                'payment_id': $(this).data('payment-id'),
                'subscription_id': $(this).data('subscription-id'),
                'mollie_subscription_id': $(this).data('mollie-subscription-id'),
                'customer_id': $(this).data('customer-id'),
                'amount': new_option.val(),
                'interval': new_option.data('interval'),
            };

            $.post(ajaxurl, data, function (response) {
                let $body = $('body'),
                    html = '',
                    color = '#3c763d';

                if (response.success == false) {
                    color = '#a94442';
                }

                html += '<div class="pt-subscription-update-response">'+
                            '<div class="pt-subscription-update-response-message-block">'+
                                '<div class="pt-subscription-update-response-message" style="color:'+color+'">'+response.message+'</div>'+
                                '<div class="pt-subscription-update-response-close notice-dismiss"></div>'+
                    '</div></div>';
                loaderContainer.remove();
                $body.append(html);
            });

        });

        let loading = false;

        $("body.post-type-paytium_emails .column-status").on("click", function (e) {

            if (loading == false) {
                console.log(loading);
                loading = true;
                let target = $(this).children('span'),
                    data = {
                        action: 'pt_email_status_change',
                        nonce: paytium.nonce,
                        email_id: target.data('id'),
                        email_status: target.data('status')
                    };

                target.hide();

                let loaderContainer = $( '<span/>', {
                    'class': 'loader-image-container'
                }).insertAfter( target );

                let loader = $( '<img/>', {
                    src: '/wp-admin/images/loading.gif',
                    'class': 'loader-image'
                }).appendTo( loaderContainer );

                $.post( ajaxurl, data, function(response) {

                    if (response == 'success') {
                        if (data['email_status'] == 1) {
                            target.data('status', 0);
                            target.attr('data-status', 0);
                            target.removeClass('dashicons-yes').addClass('dashicons-no');
                        }
                        else {
                            target.data('status', 1);
                            target.attr('data-status', 1);
                            target.removeClass('dashicons-no').addClass('dashicons-yes');
                        }
                    }
                    else {
                        console.log('error');
                    }
                    loaderContainer.remove();
                    target.show();
                    loading = false;
                });
            }
        });

        if ($('input[name="paytium_enable_live_key"]').length > 0) {

            // Always check on page load
            checkMollieApiKeysFields();

            // Also check when API key's might be entered
            $('input[name="paytium_enable_live_key"], table.form-table #paytium_live_api_key, table.form-table #paytium_test_api_key').on('change keyup', function (e) {
                checkMollieApiKeysFields();
            });
        }

        function checkMollieApiKeysFields() {

            let liveApiKey = $('table.form-table #paytium_live_api_key'),
                testApiKey = $('table.form-table #paytium_test_api_key'),
                errorBlock = "<div class='pt-alert pt-alert-info pt-alert-settings'>"+paytium_localize_script_vars.not_entered_api_keys+"</div>";

            if (liveApiKey.val() === '' || testApiKey.val() === '') {
                if ($('.pt-alert-settings').length === 0) {
                    $('table.form-table').before(errorBlock);
                }
            }
            else {
                if ($('.pt-alert-settings').length > 0) {
                    $('.pt-alert-settings').remove();
                }
            }

            if (liveApiKey.val() !== '' && $('input[name="paytium_enable_live_key"]').is(':checked')) {
                $('#paytium_admins_test_mode').parents('tr').show();
            }
            else {
                $('#paytium_admins_test_mode').parents('tr').hide();
            }

        }

        if ( $('#pt_daterange_filter').length > 0 ) {

            $('#pt_daterange_filter').daterangepicker({
                autoUpdateInput: false,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                },
                locale: {
                    format: 'MMMM D YYYY'
                }
            }, function(start, end, label) {
                $('#pt_daterange_filter').val(start.format('MMMM D YYYY')+' - '+end.format('MMMM D YYYY'));
            })
        }

        if ( $('#paytium-export-invoices-date-range-start').length > 0 ) {
            $('#paytium-export-invoices-date-range-start').datepicker({ dateFormat: 'dd-mm-yy' });
            $('#paytium-export-invoices-date-range-end').datepicker({ dateFormat: 'dd-mm-yy' });
        }
        if ( $('#paytium-export-payments-date-range-start').length > 0 ) {
            $('#paytium-export-payments-date-range-start').datepicker({ dateFormat: 'dd-mm-yy' });
            $('#paytium-export-payments-date-range-end').datepicker({ dateFormat: 'dd-mm-yy' });
        }

        $(".paytium-notice .notice-dismiss").on("click", function (e) {

            let noticeId = $(this).parent('.paytium-notice').data('id'),
                data = {
                    id: noticeId,
                    nonce: paytium.nonce,
                    action: 'paytium_notice_dismiss'
                };

            $.post( ajaxurl, data, function(response) {

                if (response == 'success') {
                    console.log('paytium notice dismiss: success');
                }
                else console.log('paytium notice dismiss: error');
            });
        });

        $('.recent-payments tr').click( function() {
            window.location = $(this).find('a').attr('href');
        }).hover( function() {
            $(this).toggleClass('hover');
        });

        if ($('.pt_email_add_attachments').length > 0) {
            if ( typeof wp !== 'undefined' && wp.media && wp.media.editor) {
                $(document).on('click', '.pt_email_add_attachments', function(e) {
                    e.preventDefault();
                    let button = $(this),
                        ids = button.prev(),
                        attachmentsContainer = $('#email-attachments-container');
                    wp.media.editor.send.attachment = function(props, attachment) {

                        let data = {
                                nonce: paytium.nonce,
                                action: 'paytium_emails_attachments',
                                attachment_id: attachment.id
                            },
                            attachmentHtml = '<div class="email-attachment" id="'+attachment.id+'">' +
                                '<span class="pt-remove-attachment">✕</span>';

                        $.post( ajaxurl, data, function(response) {

                            if (response.result == 'success') {

                                if (response.thumbnail) {
                                    attachmentHtml += '<img src="'+response.thumbnail+'" alt="'+attachment.title+'">'
                                }
                                attachmentHtml += '<p><a href="'+attachment.url+'" target="_blank">'+attachment.filename+'</a></p></div>';

                                attachmentsContainer.append(attachmentHtml);

                                let idsVal = ids.val();
                                if (idsVal == '') {
                                    ids.val(attachment.id);
                                }
                                else {
                                    ids.val(idsVal+','+attachment.id);
                                }
                            }
                            else console.log('error');
                        });
                    };
                    wp.media.editor.open(button);
                    return false;
                });
            }
            $(document).on('click','.pt-remove-attachment',function () {

                let id = $(this).parent().attr('id'),
                    ids = $('#pt_email_add_attachments'),
                    idsVal = $(ids).val(),
                    idsArr = idsVal.split(',');

                idsArr.splice( $.inArray(id,idsArr) ,1 );
                $(ids).val(idsArr.toString());

                let that = this;
                $(this).parent().fadeOut('slow', function(){
                    $(that).parent().remove();
                });

            })
        }

        if ($('#export-payments-form').length > 0) {

            $('#export_payments').on('click',function(e){
                e.preventDefault();

                let data = {
                    start_date: $('#paytium-export-payments-date-range-start').val(),
                    end_date: $('#paytium-export-payments-date-range-end').val(),
                    statuses: $('#paytium_order-statuses').val(),
                    columns: $('#paytium_columns').val(),
                    sources: $('#paytium-payment-sources').val(),
                    action: 'paytium_export_payments'
                };

                $.post(ajaxurl, data, function (response) {

                    let selectedOptions = $('#paytium-payment-sources').select2('data');

                    if (response.result === 'conflict' && Array.isArray(selectedOptions) && !selectedOptions.length) {

                        $('#export_payments').hide();
                        $('.conflict').fadeIn();

                    } else if (response.result === 'payments_without_source_id' && Array.isArray(selectedOptions) && !selectedOptions.length) {

                        $('#export_payments').hide();
                        $('.payment-source-warning').fadeIn();

                    } else {
                        console.log(response.result);
                        $('#export-payments-form').submit();
                    }

                });
            });

            let source = $('.select2-selection.select2-selection--multiple').get(2);

            $('#conflict_continue_anyway, #conflict_set_payment_source').on('click',function(e){

                if ($(e.target).is('#conflict_continue_anyway')) {
                    $('#export-payments-form').submit();
                }
                else if ($(e.target).is('#conflict_set_payment_source')) {
                    $('#paytium-payment-sources').select2('open');
                    $(source).css({border: 'solid #FF9800 1px'});
                }
            });

            $('#paytium-payment-sources').on('select2:close', function () {
                $(source).attr('style','border','');
            });

            $('#payment_source_continue_with, #payment_source_continue_without').on('click',function(e){

                $('.payment-source-warning').hide();
                $('.conflict').hide();
                $('#export_payments').fadeIn();

                if ($(e.target).is('#payment_source_continue_with')) {
                    $('#export-payments-form').submit();
                }
                else if ($(e.target).is('#payment_source_continue_without')) {
                    $('#paytium-payment-sources').val('');
                    $('#export-payments-form').submit();
                }
            });

        }

        $('.subscription-option').on('change', function () {
            if ($(this).val() !== current_subscription_option){
                $('#paytium_subscription_update').prop('disabled', false);
            }
            else {
                $('#paytium_subscription_update').prop('disabled', true);
            }
        });
    });

    $('body').on('click','.pt-subscription-update-response', function (e) {
        if (($(e.target).is('.pt-subscription-update-response') && (!$(e.target).hasClass('pt-subscription-update-confirm-wrapper'))) || $(e.target).is('.pt-subscription-update-response-close')) {
            location.reload();
        }
    });

}(jQuery));

