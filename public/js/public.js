/**
 * Paytium Public JS
 *
 * @package PT
 * @author  David de Boer <david@davdeb.com>
 */

/* global jQuery, pt_script */

(function ($) {
    'use strict';

    var currencySymbol = pt.currency_symbol;

    function debug_log( message ) {
        if ( pt.debug == true ) {
            console.log( message );
        }
    }

    $(function () {

        var $body = $('body');
        var ptFormList = $body.find('.pt-checkout-form');

        // Make sure each checkbox change sets the appropriate hidden value (Yes/No) to record
        // to Paytium payment records.
        var ptCheckboxFields = ptFormList.find('.pt-field-checkbox');
        ptCheckboxFields.change(function () {
            var checkbox = $(this);
            var checkboxId = checkbox.prop('id');
            var hiddenField = $body.find('#' + checkboxId + '_hidden'); // Hidden ID field is simply "_hidden" appended to checkbox ID field.

            hiddenField.val(checkbox.is(':checked') ? 'Yes' : 'No'); // Change to "Yes" or "No" depending on checked or not.
        });

        // Process the form(s)
        ptFormList.each(function () {
            var ptForm = $(this),
                ptFormCurrencySymbol = ptForm.data('currency') && getCurrencySymbol(ptForm.data('currency')) !== undefined ?
                    getCurrencySymbol(ptForm.data('currency')) : currencySymbol,
                ptFormQtyDiscount = ptForm.find('input.pt-quantity-discount'),
                ptFormQtyDiscountData = ptFormQtyDiscount.data('quantity-discount') ? ptFormQtyDiscount.data('quantity-discount') : false,
                ptFormCurrentQtyDiscount = 0,
                ptFormCurrentQty = 0,
                ptFormAmountDiscount = ptForm.find('input.pt-amount-discount'),
                ptFormAmountDiscountData = ptFormAmountDiscount.data('amount-discount') ? ptFormAmountDiscount.data('amount-discount') : false,
                ptFormCurrentAmountDiscount = 0,
                ptFormCurrentAmount = 0,
                ptFormGeneralLimit = ptForm.find('.pt-general-items-left').data('general-items-left'),
                ptFormAmounts = [];

            ptForm.find(".pt-cf-amount").each(function () {
                if ($(this).is('select')) {
                    $($(this).children('option')).each(function () {
                        ptFormAmounts.push($(this).attr('data-pt-price'));
                    })
                }
                else {
                    ptFormAmounts.push($(this).attr('data-pt-price'));
                }
            });

            // Add field that allows Paytium processing to know that JS was enabled on form
            $("<input>", {type: "hidden", name: 'pt-paytium-js-enabled', value: 1}).appendTo(ptForm);

            // Enable form button with javascript, so it doesn't show to users that don't have JS enabled
            ptForm.find('.pt-payment-btn')
                .show();

            //
            // START - Paytium No Payment
            //

            function isPaytiumNoPayment() {

                var noPaymentFound = false;

                ptForm.find("[id^=pt-paytium-no-payment]").each(function () {
                    noPaymentFound = true;
                });

                return noPaymentFound;
            }

            //
            // END - Paytium No Payment
            //

            //
            // START - Show a warning about prefilled fields
            //

            ptForm.find(".pt-field-prefill-warning-hint").click(function () {
                var ptPrefillWarningCounter = $(this).attr('data-pt-prefill-warning-counter');
                ptForm.find("#pt-prefill-warning-counter-" + ptPrefillWarningCounter).toggle("slow");
            });

            //
            // END - Show a warning about prefilled fields
            //

            //
            // START - Enable/disable discount apply button depending on input
            //

            ptForm.find('.pt-discount').on('keyup', function (event) {
                var nameInput = ptForm.find('.pt-discount').val();
                if (nameInput.length > 0) {
                    ptForm.find('.pt-discount-button').prop('disabled', false);
                } else {
                    ptForm.find('.pt-discount-button').prop('disabled', true);
                }
            });

            ptForm.find('.pt-discount-button').on('click.ptDiscountFieldButton', function (event) {

                event.preventDefault();

                var formLoadID = ptForm.find('[name="pt-form-load"]').val(),
                    discountCode = ptForm.find('.pt-discount').val(),
                    associateWithEmail = false,
                    email = '';

                if ($(this).siblings('#pt-discount-associate-with-email').length) {
                    associateWithEmail = true;
                    email = $(this).parents('.pt-form-group-field-discount').siblings('.pt-form-group-field-email').children('input[type="email"]').val();
                }

                var noEmailError = associateWithEmail && !email;

                if (discountCode && formLoadID) {

                    $.ajax({
                        url: paytium_localize_script_vars.admin_ajax_url,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            'action': "pt_ajax_check_discount_codes",
                            'data': {'formLoadID': formLoadID, 'discountCode': discountCode, 'email': email}
                        },
                        success: function (response) {

                            if (response.status && !noEmailError) {

                                // Show success message after user applies valid discount code
                                ptForm.find('.pt-discount-true').show();
                                ptForm.find('.pt-discount-false').hide();
                                ptForm.find('.pt-discount-used').hide();

                                // Remove discount fields if they are already added
                                ptForm.find(".pt-discount-type").remove();
                                ptForm.find(".pt-discount-value").remove();
                                ptForm.find(".pt-discount-times").remove();

                                // If discount is valid, add discount fields to the form
                                $("<input>", {
                                    type: "hidden",
                                    name: 'pt-discount[type]',
                                    class: 'pt-discount-type',
                                    value: response.type
                                }).appendTo(ptForm);
                                $("<input>", {
                                    type: "hidden",
                                    name: 'pt-discount[value]',
                                    class: 'pt-discount-value',
                                    value: response.value
                                }).appendTo(ptForm);
                                $("<input>", {
                                    type: "hidden",
                                    name: 'pt-discount[times]',
                                    class: 'pt-discount-times',
                                    value: response.times
                                }).appendTo(ptForm);


                            } else {

                                // Show error message after user applies incorrect discount code
                                ptForm.find('.pt-discount-true').hide();
                                if (response.used) {
                                    ptForm.find('.pt-discount-email-error').hide();
                                    ptForm.find('.pt-discount-false').hide();
                                    ptForm.find('.pt-discount-used').show(); // discount feature
                                }
                                else if (noEmailError) {
                                    ptForm.find('.pt-discount-used').hide();
                                    ptForm.find('.pt-discount-false').hide();
                                    ptForm.find('.pt-discount-email-error').show();
                                }
                                else {
                                    ptForm.find('.pt-discount-email-error').hide();
                                    ptForm.find('.pt-discount-used').hide();
                                    ptForm.find('.pt-discount-false').show();
                                }

                                // Empty discount field and disable apply button, so user can try again
                                ptForm.find('.pt-discount').val('');
                                ptForm.find('.pt-discount-button').prop('disabled', true);

                                // Remove discount fields if they are already added
                                ptForm.find(".pt-discount-type").remove(); // Don't use parseAmount for this one!
                                ptForm.find(".pt-discount-value").remove(); // Don't use parseAmount for this one!
                                ptForm.find(".pt-discount-times").remove(); // Don't use parseAmount for this one!

                            }

                            update_totals();

                        },
                    });
                }
            });

            //
            // END - Enable/disable discount apply button depending on input
            //

            //
            // START - Add subscription first payment to [paytium_total /] shortcode
            //

            // If there is a subscription first payment, adjust form to allow that
            if (ptForm.find("[id^=pt-subscription-first-payment]").length > 0) {
                // Add "First payment" text and amount after the [paytium_total /] Shortcode
                var ptFirstPayment = ptForm.find("[id^=pt-subscription-first-payment]").val();
                var ptFirstPaymentLabel = ptForm.find("[id^=pt-subscription-first-payment-label]").val();

                // If user set a First Payment Label, override the default translation with that
                if (!ptFirstPaymentLabel) {
                    ptFirstPaymentLabel = paytium_localize_script_vars.subscription_first_payment;
                }

                ptForm.find('.pt-total-amount').after('<div class="pt-first-payment-amount">' + ptFirstPaymentLabel + ': ' + currencyFormattedAmount(ptFirstPayment, ptFormCurrencySymbol) + '</div>');
            }

            //
            // END - Add subscription first payment to [paytium_total /] shortcode
            //

            //
            // START - Subscription interval options and optional on page load
            //

            // Uncheck subscription interval options on page load, especially for back button
            ptForm.find('input[name="pt-subscription-interval-options"]:checked').each(function () {
                $('input[name="pt-subscription-interval-options"]').prop('checked', false);
            });

            // Make sure Yes is checked fopr subscription optional on page load, especially for back button
            ptForm.find('input[name="pt-subscription-optional"]:checked').each(function () {
                $('input[name="pt-subscription-optional"]:first').attr('checked', true);
            });

            //
            // END - Subscription interval options and optional on page load
            //

            //
            // START - Add option amount labels to field label
            // Add option amount labels for dropdown/radio to field label, so users can see what customers selected
            //

            // Checkbox with amounts, get all already checked checkboxes and process amounts and labels
            ptForm.find('input[type=checkbox]:checked').filter('.pt-cf-amount').each(function () {

                //
                // Update amount
                //

                // Get id/name for checkbox form group amount
                var ptCheckboxFormGroupAmountId = $(this).attr('name').replace('[amount]', '[amount][total]');

                // Get selected value
                var ptCheckboxFieldValue = $(this).val();

                // Get total amount for this checkbox group
                var ptCheckboxTotalValue = ptForm.find("[id='" + ptCheckboxFormGroupAmountId + "']").val();

                // Add selected value to total amount (don't remove those +'s)
                var ptCheckboxNewTotalValue = +ptCheckboxTotalValue + +ptCheckboxFieldValue;

                // Update the total value in value attribute
                ptForm.find("[id='" + ptCheckboxFormGroupAmountId + "']").attr('value', parseAmount(ptCheckboxNewTotalValue));

                // Update total value in pt-price data attribute
                ptForm.find("[id='" + ptCheckboxFormGroupAmountId + "']").attr('data-pt-price', parseAmount(ptCheckboxNewTotalValue));

                //
                // Update label
                //

                // Get/convert checkbox group label ID
                var ptCheckboxFormGroupLabelId = $(this).attr('name').replace('[amount]', '[label]');

                var ptCheckboxFormValue = $(this).attr('name').replace('[amount]', '[value]');

                // Get previously checked options
                var ptCheckboxCurrentOptions = ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('data-pt-checked-options');

                ptCheckboxCurrentOptions = JSON.parse(ptCheckboxCurrentOptions);

                // If previously checked options are not an array, this means it's empty
                if (Object.keys(ptCheckboxCurrentOptions).length === 0) {
                    ptCheckboxCurrentOptions = {};
                }

                ptCheckboxCurrentOptions[$(this).attr('data-pt-checkbox-id')] = $(this).parent().text();

                // Start string with current group label
                var ptCheckboxCurrentOptionsString = ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('data-pt-original-label');

                // Convert current options to a string for HTML hidden field

                var ptCheckboxFormValueString = '';
                for (var key in ptCheckboxCurrentOptions) {
                    ptCheckboxFormValueString +=  ptCheckboxFormValueString == '' ? ptCheckboxCurrentOptions[key] : ', ' + ptCheckboxCurrentOptions[key];
                }

                // Convert to a format that is save for HTML fields
                ptCheckboxCurrentOptions = JSON.stringify(ptCheckboxCurrentOptions);

                // Add updated options to data-pt-checked-options attribute
                ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('data-pt-checked-options', ptCheckboxCurrentOptions);

                // Add current options string to hidden HTML field
                ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('value', ptCheckboxCurrentOptionsString);
                ptForm.find("[name='" + ptCheckboxFormValue + "']").attr('value', ptCheckboxFormValueString);

            });

            // Radio amounts, try to get first option in radio buttons to use as default if an option is not selected yet
            ptForm.find('input[type=radio]:checked').filter('.pt-cf-amount').each(function () {

                var ptRadioCustomOption = $(this).parent().text();

                var ptRadioFormGroupId = $(this).attr('name').replace('[amount]', '[label]');

                var ptRadioFormValue = $(this).attr('name').replace('[amount]', '[value]');

                var ptRadioFormOptionId = $(this).attr('name').replace('[amount]', '[item_id]');

                var ptRadioFormOptionLimit = $(this).attr('name').replace('[amount]', '[limit]');

                var ptRadioOptionData = $(this).data();

                var ptRadioFormGroupLabel = ptForm.find("[name='" + ptRadioFormGroupId + "']").attr('data-pt-original-label');

                ptForm.find("[name='" + ptRadioFormValue + "']").val(function () {
                    return ptRadioCustomOption;
                });

                ptForm.find("[name='" + ptRadioFormOptionId + "']").val(ptRadioOptionData.item_id);
                ptForm.find("[name='" + ptRadioFormOptionLimit + "']").val(ptRadioOptionData.limit);

                if ($(this).data('general_item_id')) {

                    var ptRadioFormOptionGeneralId = $(this).attr('name').replace('[amount]', '[general_item_id]'),
                        ptRadioFormOptionGeneralLimit = $(this).attr('name').replace('[amount]', '[general_limit]');

                    ptForm.find("[name='" + ptRadioFormOptionGeneralId + "']").val(ptRadioOptionData.general_item_id);
                    ptForm.find("[name='" + ptRadioFormOptionGeneralLimit + "']").val(ptRadioOptionData.general_limit);
                }
            });

            // Dropdown amounts, try to get first option in Dropdown to use as default if an option is not selected yet
            ptForm.find('select.pt-cf-amount').each(function () {

                var ptDropdownCustomOption = $(this).find(':selected').text();

                var ptDropdownFormGroupId = $(this).attr('name').replace('[amount]', '[label]');

                var ptDropdownFormValue = $(this).attr('name').replace('[amount]', '[value]');

                ptForm.find("[name='" + ptDropdownFormValue + "']").val(function () {
                    return ptDropdownCustomOption;
                });
            });

            // Get selected option for radio and dropdown amounts, when user selects them
            ptForm.find('.pt-cf-amount').on('change', function () {

                // Process radio buttons amount labels
                if ($(this).is('input[type="radio"]')) {

                    var ptRadioCustomOption = $(this).parent().text();

                    var ptRadioFormGroupId = $(this).attr('name').replace('[amount]', '[label]');

                    var ptRadioFormValue = $(this).attr('name').replace('[amount]', '[value]');

                    var ptRadioFormOptionId = $(this).attr('name').replace('[amount]', '[item_id]');

                    var ptRadioFormOptionLimit = $(this).attr('name').replace('[amount]', '[limit]');

                    var ptRadioOptionData = $(this).data();

                    ptForm.find("[name='" + ptRadioFormValue + "']").val(function () {
                        return ptRadioCustomOption;
                    });

                    ptForm.find("[name='" + ptRadioFormOptionId + "']").val(ptRadioOptionData.item_id);
                    ptForm.find("[name='" + ptRadioFormOptionLimit + "']").val(ptRadioOptionData.limit);

                    if ($(this).data('general_item_id')) {

                        var ptRadioFormOptionGeneralId = $(this).attr('name').replace('[amount]', '[general_item_id]'),
                            ptRadioFormOptionGeneralLimit = $(this).attr('name').replace('[amount]', '[general_limit]');

                        ptForm.find("[name='" + ptRadioFormOptionGeneralId + "']").val(ptRadioOptionData.general_item_id);
                        ptForm.find("[name='" + ptRadioFormOptionGeneralLimit + "']").val(ptRadioOptionData.general_limit);
                    }

                }

                // Process dropdown amount labels
                if ($(this).is('select')) {

                    var ptDropdownCustomOption = $(this).find(':selected').text();

                    var ptDropdownFormGroupId = $(this).attr('name').replace('[amount]', '[label]');

                    var ptDropdownFormValue = $(this).attr('name').replace('[amount]', '[value]');

                    var ptDropdownFormOptionId = $(this).attr('name').replace('[amount]', '[item_id]');

                    var ptDropdownFormOptionLimit = $(this).attr('name').replace('[amount]', '[limit]');

                    var ptDropdownOptionData = $(this).find(':selected').data();

                    ptForm.find("[name='" + ptDropdownFormValue + "']").val(function () {
                        return ptDropdownCustomOption;
                    });
                    ptForm.find("[name='" + ptDropdownFormOptionId + "']").val(ptDropdownOptionData.item_id);
                    ptForm.find("[name='" + ptDropdownFormOptionLimit + "']").val(ptDropdownOptionData.limit);

                    if ($(this).find(':selected').data('general_item_id')) {

                        var ptDropdownFormOptionGeneralId = $(this).attr('name').replace('[amount]', '[general_item_id]'),
                            ptDropdownFormOptionGeneralLimit = $(this).attr('name').replace('[amount]', '[general_limit]');

                        ptForm.find("[name='" + ptDropdownFormOptionGeneralId + "']").val(ptDropdownOptionData.general_item_id);
                        ptForm.find("[name='" + ptDropdownFormOptionGeneralLimit + "']").val(ptDropdownOptionData.general_limit);
                    }

                }

            });

            //
            // END - Option label to field label
            //

            //
            // START - Update total when a paid field changes
            //

            ptForm.find( '.pt-uea-custom-amount' ).on('keyup change', update_totals );
            ptForm.find( ':checkbox' ).change(update_checkbox_field);
            ptForm.find( '.pt-cf-amount' ).on( 'change', update_totals );

            ptForm.find( '.pt-quantity-input' ).on( 'keyup mouseup', function (e) {
                let general_limit_control_result = general_limit_control($(this));
                if (general_limit_control_result !== undefined && !general_limit_control_result.result) {
                    $(this).val(general_limit_control_result.new_val);
                    alert(ptForm.find('.pt-general-items-left').text());
                }
                update_totals(e);
            } );
            ptForm.find( '.paytium-spinner' ).on( 'click keyup', function (e) {
                let input = $(this).siblings('.pt-quantity-input[type=number]'),
                    general_limit_control_result = general_limit_control(input);

                if (general_limit_control_result !== undefined && !general_limit_control_result.result) {
                    input.val(general_limit_control_result.new_val);
                    alert(ptForm.find('.pt-general-items-left').text());
                }
                update_totals(e);
            } );
            ptForm.find( '.pt-paid-field' ).on( 'input', update_totals );

            ptForm.find( ':radio[name=pt-subscription-interval-options]' ).change(subscription_interval);
            ptForm.find( ':radio[name=pt-subscription-optional]' ).change(subscription_optional);


            //
            // END - Update total when user enters amount (custom amount)
            //

            //
            // START - UEA, Update individual open amounts (uea)
            //

            ptForm.find("input[id^=pt_uea_custom_amount_]").on('keyup change', update_open_field);

            function update_open_field(i, element) {

                var ptUEAFieldName = $(this).attr("name");
                var ptUEAFieldID = $(this).attr("id");
                var ptUEAFieldValue = $(this).val();

                ptForm.find("[id^=" + ptUEAFieldID + "]").val(parseAmount(ptUEAFieldValue));
                ptForm.find("[name*='" + ptUEAFieldName + "']").val(ptUEAFieldValue); // Don't use parseAmount for this one!
                ptForm.find("[name*='" + ptUEAFieldName + "']").attr('data-pt-price', parseAmount(ptUEAFieldValue));
                $(this).parent().siblings('.pt-uea-custom-amount-formatted').val(parseAmount(ptUEAFieldValue));
            }

            //
            // END - UEA, Update individual open amounts (uea) user changes amount
            //

            function get_form_paid_fields() {
                var fields = [];

                ptForm.find( '.pt-cf-label-amount' ).each( function( index, element ) { fields.push( element ); }); // Label fields
                ptForm.find( '.pt-uea-custom-amount' ).each( function( index, element ) { fields.push( element ); }); // Open amount fields
                ptForm.find( 'select.pt-cf-amount' ).each( function( index, element ) { fields.push( element ); }); // Select fields
                ptForm.find( 'input[type=radio].pt-cf-amount:checked' ).each( function( index, element ) { fields.push( element ); }); // Radio fields
                ptForm.find( 'input[type=checkbox].pt-cf-amount' ).each( function( index, element ) { fields.push( element ); }); // Checkbox fields
                ptForm.find( '.pt-paid-field' ).each( function( index, element ) { fields.push( element ); });

                return fields;
            }

            function get_form_total(discount = true) {

                // Get all fields/amounts
                var total = 0;
                var fields = get_form_paid_fields();

                // Loop through each amount field
                $( fields ).each( function( index, element ) {

                    // If a paid field is disabled (e.g. with interval options and subscriptions) don't process it
                    if ($(element).prop('disabled')) {
                        return false;
                    }

                    var fieldAmount = 0;
                    var $quantity_input = $( element ).siblings('.pt-quantity-input').length ?
                        $( element ).siblings('.pt-quantity-input') :
                        $( element ).siblings('.paytium-quantity-input').children('.pt-quantity-input');
                    var quantity = $quantity_input.length ? $quantity_input.val() : 1;
                    if($(element).hasClass('pt-paid-field')) {
                        fieldAmount = $( element ).val() ? $( element ).attr('data-pt-price') * quantity : 0;
                        $( element ).siblings('[name$="[amount]"]').val(parseAmount(fieldAmount));
                    }
                    else if($(element).hasClass('pt-uea-custom-amount')) {
                        fieldAmount = parseAmount($(element).val()) * quantity;
                        $(element).parent().siblings('.pt-uea-custom-amount-formatted').val(parseAmount(fieldAmount));
                    }
                    else {
                        switch ($(element).prop('nodeName')) {
                            case 'SELECT' :
                                fieldAmount = $(element).find('option:selected').attr('data-pt-price') * quantity;
                                fieldAmount ? $(element).find('option:selected').val(parseAmount(fieldAmount)) : '';
                                break;
                            default:
                            case 'INPUT' :
                                var type = $(element).attr('type');
                                if (type == 'hidden' || type == 'text') {
                                    fieldAmount = $(element).attr('data-pt-price') * quantity;
                                    $(element).val(parseAmount(fieldAmount));
                                } else if (type == 'radio' ) {

                                    $quantity_input = $(element).parents('.pt-radio-group').find('.pt-quantity-input');
                                    quantity = $quantity_input.length ? $quantity_input.val() : 1;
                                    fieldAmount = $(element).attr('data-pt-price') * quantity;
                                    $(element).val(parseAmount(fieldAmount));
                                }
                                else if (type == 'checkbox') {
                                    fieldAmount = $(element).attr('data-pt-price') * quantity;
                                    $(element).val(parseAmount(fieldAmount));
                                    if(!$(element).is(':checked')) {
                                        fieldAmount = 0;
                                    }
                                }

                                break;

                        }

                    }
                    total = parseFloat( total ) + parseAmount( fieldAmount );
                });

                return discount ? get_form_total_with_discount(total) : total;
            }

            function get_form_total_with_discount(total) {

                let total_after_discount = total,
                    zero_tax = ptForm.find(".pt-zero-tax").length ? parseFloat(ptForm.find(".pt-zero-tax").val()) : 0,
                    total_without_zero_tax = total - zero_tax;


                // If a discount is found, calculate the new total
                if (ptForm.find(".pt-discount-type").length > 0 && ptForm.find(".pt-discount-value").length > 0) {

                    let discountType = ptForm.find(".pt-discount-type").val(); // Don't use parseAmount for this one!
                    let discountValue = ptForm.find(".pt-discount-value").val(); // Don't use parseAmount for this one!

                    let discount_first_payment = ptForm.find('#pt-discount-first-payment').length;

                    if (discountType.length > 0 && discountValue.length > 0 && !discount_first_payment) {

                        if ((discountType === 'percentage' )) {
                            total_after_discount = parseAmount(total_without_zero_tax * ((100 - discountValue) / 100));
                        }

                        if ((discountType === 'amount' )) {
                            total_after_discount = parseAmount(total_without_zero_tax - discountValue);
                        }

                    }
                }

                return total_after_discount !== total ? total_after_discount + zero_tax : total;
            }

            function get_first_payment_amount() {

                var first_payment_amount = ptForm.find(".pt-subscription-first-payment").val(),
                    discount_exclude_first_payment = ptForm.find('#pt-discount-exclude-first-payment').length;

                if (discount_exclude_first_payment) return first_payment_amount;

                if (ptForm.find(".pt-discount-type").length > 0 && ptForm.find(".pt-discount-value").length > 0 && first_payment_amount) {

                    var discountType = ptForm.find(".pt-discount-type").val(); // Don't use parseAmount for this one!
                    var discountValue = ptForm.find(".pt-discount-value").val(); // Don't use parseAmount for this one!

                    if (discountType.length > 0 && discountValue.length > 0) {

                        if ((discountType === 'percentage' )) {
                            first_payment_amount = parseAmount(first_payment_amount.replace(',','.') * ((100 - discountValue) / 100));
                        }

                        if ((discountType === 'amount' )) {
                            first_payment_amount = parseAmount(first_payment_amount.replace(',','.') - discountValue);
                        }
                    }
                }
                return first_payment_amount;
            }

            function quantity_input_validation($quantity_input) {
                // Get values from input box

                var new_qty = $quantity_input.val();
                var step = $quantity_input.attr( 'step' );
                var max = $quantity_input.attr( 'max' );
                var min = $quantity_input.attr( 'min' );

                // Adjust default values if values are blank
                if ( typeof min == 'undefined' )
                    min = 0;

                if ( typeof step == 'undefined')
                    step = 1;

                // Max Value Validation
                if ( +new_qty > +max && typeof max !== 'undefined' ) {
                    new_qty = max;

                    // Min Value Validation
                } else if ( +new_qty < +min  ) {
                    new_qty = min;
                }

                // Calculate remainder
                var rem = ( new_qty - min ) % step;

                // Step Value Value Validation
                if ( rem != 0 ) {
                    new_qty = +new_qty + (+step - +rem);

                    // Max Value Validation
                    if ( +new_qty > +max ) {
                        new_qty = +new_qty - +step;
                    }
                }

                if (ptFormQtyDiscountData) {

                    let discountQtyCount = ptFormQtyDiscountData.length,
                        i = 0;

                    $(ptFormQtyDiscountData).each(function (k, v) {

                        if ( (ptFormCurrentQtyDiscount !== parseFloat(v.discount) && parseInt(new_qty) >= parseInt(v.quantity)) ||
                             (ptFormCurrentQtyDiscount === parseFloat(v.discount) && parseInt(new_qty) < parseInt(v.quantity)) ) {

                            if (ptFormCurrentQtyDiscount > parseFloat(v.discount) && parseInt(new_qty) >= ptFormCurrentQty) {
                                return;
                            }
                            let data = v;
                            if (parseInt(new_qty) < parseInt(v.quantity)) {
                                if (k < discountQtyCount-1) {
                                    data = ptFormQtyDiscountData[k+1];
                                }
                                else {
                                    i++;
                                    return;
                                }
                            }

                            ptFormCurrentQtyDiscount = parseFloat(data.discount);
                            ptFormCurrentQty = parseInt(data.quantity);

                            let loaderContainer = $( '<div>', {
                                'class': 'pt-checkout-form-loading'
                            }).appendTo( ptForm );

                            let loader = $( '<div>', {
                                'class': 'pt-loader'
                            }).appendTo( loaderContainer );

                            $.ajax({
                                url: paytium_localize_script_vars.admin_ajax_url,
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    'action': "pt_ajax_quantity_discount",
                                    'id': ptFormQtyDiscount.attr('id'),
                                    'data': {
                                        'current_qty': new_qty,
                                        'qty': data.quantity,
                                        'discount': data.discount,
                                        'type': data.type
                                    }
                                },
                                success: function (response) {

                                    if (response.error) {

                                        ptFormCurrentQtyDiscount = 0;
                                        ptFormCurrentQty = 0;
                                        // Remove discount fields
                                        ptForm.find(".pt-discount-type").remove();
                                        ptForm.find(".pt-discount-value").remove();
                                        ptForm.find(".pt-quantity-discount-hidden").remove();

                                        ptForm.find('.pt-discount-true').hide();

                                        update_totals();
                                    }
                                    else {
                                        // Remove discount fields
                                        ptForm.find(".pt-discount-type").remove();
                                        ptForm.find(".pt-discount-value").remove();
                                        ptForm.find(".pt-quantity-discount-hidden").remove();

                                        ptForm.find('.pt-discount-true').hide();

                                        $("<input>", {
                                            type: "hidden",
                                            name: 'pt-quantity-discount',
                                            class: 'pt-quantity-discount-hidden',
                                            value: response.quantity
                                        }).appendTo(ptForm);
                                        $("<input>", {
                                            type: "hidden",
                                            name: 'pt-discount-uid',
                                            class: 'pt-discount-uid',
                                            value: response.uid
                                        }).appendTo(ptForm);
                                        $("<input>", {
                                            type: "hidden",
                                            name: 'pt-discount[type]',
                                            class: 'pt-discount-type',
                                            value: response.type
                                        }).appendTo(ptForm);
                                        $("<input>", {
                                            type: "hidden",
                                            name: 'pt-discount[value]',
                                            class: 'pt-discount-value',
                                            value: response.value
                                        }).appendTo(ptForm);

                                        ptForm.find('.pt-discount-true').show();

                                        update_totals();
                                    }
                                    loaderContainer.remove();
                                },
                            });

                            return false;
                        }
                        else if(parseInt(new_qty) < parseInt(v.quantity)) {
                            i++;
                        }
                    });

                    if (discountQtyCount === i) {
                        ptFormCurrentQtyDiscount = 0;
                        ptFormCurrentQty = 0;
                        // Remove discount fields
                        ptForm.find(".pt-discount-type").remove();
                        ptForm.find(".pt-discount-value").remove();
                        ptForm.find(".pt-quantity-discount-hidden").remove();

                        ptForm.find('.pt-discount-true').hide();

                        update_totals();
                    }

                }

                // Set the new value
                $quantity_input.val( new_qty );
            }

            // When custom subscription interval is checked or unchecked, process that change
            function subscription_interval($interval) {

                var $checkbox = $interval.length ? $interval.siblings(':checkbox') : $(this);

                if (!$checkbox.length) {
                    return false;
                }

                // Make sure there is a value set
                if (typeof $checkbox.attr('value') === 'undefined') {
                    return false;
                }

                // If checkbox was checked, update interval in hidden field
                if ($checkbox.is(':checked') && ($interval instanceof $.Event)) {

                    var ptCheckboxFormLabel = $(this).attr('data-pt-label');
                    var ptCheckboxFormValue = $(this).attr('value');
                    var ptCheckboxFormPrice = $(this).attr('data-pt-price');

                    // If users select "once" as interval, disable all subscriptions
                    if ((ptCheckboxFormValue === 'once') || (ptCheckboxFormValue === 'eenmalig')) {

                        ptForm.find("[id='pt-subscription-interval']").prop('disabled', true);
                        ptForm.find("[id='pt-subscription-times']").prop('disabled', true);
                        ptForm.find("[id='pt-subscription-first-payment']").prop('disabled', true);

                    } else {

                        // Help users by adding an s to an interval when they forget about it
                        if (!ptCheckboxFormValue.endsWith('s')) {
                            ptCheckboxFormValue = ptCheckboxFormValue + 's';
                        }

                        ptForm.find("[id='pt-subscription-interval']").prop('disabled', false);
                        ptForm.find("[name='pt-subscription-interval-options']").prop('disabled', false);
                        ptForm.find("[id='pt-subscription-times']").prop('disabled', false);
                        ptForm.find("[id='pt-subscription-first-payment']").prop('disabled', false);
                        ptForm.find('.pt-subscription-custom-amount').prop('disabled', false);

                        // Update selected interval into subscription field
                        ptForm.find("[id='pt-subscription-interval']").attr('value', ptCheckboxFormValue);
                    }

                    if (ptCheckboxFormPrice) {
                        ptForm.find("[id='pt-subscription-custom-amount']").attr('value', ptCheckboxFormPrice);
                        ptForm.find("[id='pt-subscription-custom-amount']").attr('data-pt-price', ptCheckboxFormPrice);
                        ptForm.find("[id='pt-subscription-custom-value']").attr('value', ptCheckboxFormLabel);
                    }

                }

                update_totals();
            }

            // When subscription optional is checked or unchecked, process that change
            function subscription_optional($optional) {

                var $checkbox = $optional.length ? $optional.siblings(':checkbox') : $(this);

                if (!$checkbox.length) {
                    return false;
                }

                // Make sure there is a value set
                if (typeof $checkbox.attr('value') === 'undefined') {
                    return false;
                }

                // If checkbox was checked, add to total, else deduct
                if ($checkbox.is(':checked') && ($optional instanceof $.Event)) {

                    var $state = false;
                    var $stateRequired = false;
                    if ($(this).attr('value') === 'yes') {
                        $state = false;
                        $stateRequired = true;
                    }

                    if ($(this).attr('value') === 'no') {
                        $state = true;
                        $stateRequired = false;
                    }

                    ptForm.find("[id='pt-subscription-interval']").prop('disabled', $state);
                    ptForm.find("[name='pt-subscription-interval-options']").prop('disabled', $state);
                    ptForm.find("[name='pt-subscription-interval-options']").prop('required', $stateRequired);
                    ptForm.find("[id='pt-subscription-times']").prop('disabled', $state);
                    ptForm.find("[id='pt-subscription-first-payment']").prop('disabled', $state);
                    ptForm.find('.pt-subscription-custom-amount').prop('disabled', $state );
                }

                update_totals();
            }

            // When checkbox with amount is checked or unchecked, process that change
            function update_checkbox_field($quantity_input) {

                var $checkbox = $quantity_input.length ? $quantity_input.siblings(':checkbox') : $(this);

                if(!$checkbox.length) {
                    return false;
                }

                // Make sure it's an amount checkbox by checking data attribute 'data-pt-price' is defined, otherwise abort.
                if (typeof $checkbox.attr('data-pt-price') === 'undefined') {
                    return false;
                }

                var ptCheckboxFormGroupAmountId = $checkbox.attr('name').replace('[amount]', '[amount][total]');
                var total = 0;
                $checkbox.parents('.pt-form-group-checkbox-new').find(':checked').each(function (index, element) {
                    total += parseFloat($(element).val());
                });


                // Update the total value in value attribute
                ptForm.find("[id='" + ptCheckboxFormGroupAmountId + "']").attr('value', parseAmount(total));

                // Update total value in pt-price data attribute
                ptForm.find("[id='" + ptCheckboxFormGroupAmountId + "']").attr('data-pt-price', parseAmount(total));

                // If checkbox was checked, add to total, else deduct
                if ($checkbox.is(':checked') && ($quantity_input instanceof $.Event)) {

                    //
                    // Update label
                    //

                    // Get/convert checkbox group label ID
                    var ptCheckboxFormGroupLabelId = $checkbox.attr('name').replace('[amount]', '[label]');

                    var ptCheckboxFormValue = $(this).attr('name').replace('[amount]', '[value]');

                    // Get previously checked options
                    var ptCheckboxCurrentOptions = ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('data-pt-checked-options');
                    ptCheckboxCurrentOptions = JSON.parse(ptCheckboxCurrentOptions);

                    // If previously checked options are not an array, this means it's empty
                    if ( Object.keys(ptCheckboxCurrentOptions).length === 0 ) {
                        ptCheckboxCurrentOptions = {};
                    }

                    // Get selected label, convert currency symbol to HTML entity
                    ptCheckboxCurrentOptions[$checkbox.attr('data-pt-checkbox-id')] = $checkbox.parent().text();

                    // Start string with current group label
                    var ptCheckboxCurrentOptionsString = ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('data-pt-original-label');

                    // Convert current options to a string for HTML hidden field
                    var ptCheckboxFormValueString = '';

                    for (var key in ptCheckboxCurrentOptions) {
                        ptCheckboxFormValueString +=  ptCheckboxFormValueString == '' ? ptCheckboxCurrentOptions[key] : ', ' + ptCheckboxCurrentOptions[key];
                    }

                    // Convert to a format that is save for HTML fields
                    ptCheckboxCurrentOptions = JSON.stringify(ptCheckboxCurrentOptions);

                    // Add updated options to data-pt-checked-options attribute
                    ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('data-pt-checked-options', ptCheckboxCurrentOptions);


                    // Add current options string to hidden HTML field
                    ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('value', ptCheckboxCurrentOptionsString);
                    ptForm.find("[name='" + ptCheckboxFormValue + "']").attr('value', ptCheckboxFormValueString);

                    //
                    // Update limit
                    //

                    var ptCheckboxFormGroupLimitData = $checkbox.attr('name').replace('[amount]', '[limit_data]');

                    var ptCheckboxItemId = ptForm.find("[name='" + ptCheckboxFormGroupLimitData + "']").val();

                    if ( ptCheckboxItemId !== undefined ) {

                        ptCheckboxItemId = JSON.parse(ptCheckboxItemId);

                        // console.log(ptCheckboxItemId);

                        if (Object.keys(ptCheckboxItemId).length === 0) {
                            ptCheckboxItemId = {};
                        }

                        ptCheckboxItemId[$checkbox.data('item_id')] = {
                            'limit': $checkbox.data('limit'),
                            'quantity': $checkbox.siblings('.pt-quantity-input').val(),
                        };
                        if ($checkbox.data('general_item_id')) {
                            ptCheckboxItemId[$checkbox.data('item_id')]['general_item_id'] = $checkbox.data('general_item_id');
                            ptCheckboxItemId[$checkbox.data('item_id')]['general_limit'] = $checkbox.data('general_limit');
                        }

                        ptCheckboxItemId = JSON.stringify(ptCheckboxItemId);

                        ptForm.find("[name='" + ptCheckboxFormGroupLimitData + "']").val(ptCheckboxItemId);

                    }

                } else if ($checkbox.not(':checked') && ($quantity_input instanceof $.Event) ) {

                    //
                    // Update label
                    //

                    // Get/convert checkbox group label ID
                    var ptCheckboxFormGroupLabelId = $checkbox.attr('name').replace('[amount]', '[label]');

                    var ptCheckboxFormValue = $(this).attr('name').replace('[amount]', '[value]');

                    // Get previously checked options
                    var ptCheckboxCurrentOptions = ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('data-pt-checked-options');
                    ptCheckboxCurrentOptions = JSON.parse(ptCheckboxCurrentOptions);

                    // If previously checked options are an array, remove the selected label
                    if (typeof ptCheckboxCurrentOptions === 'object') {

                        // Only add the selected label if it's not in previously checked options array already

                        var ptCheckboxSelectedLabelId = $checkbox.attr('data-pt-checkbox-id');

                        delete ptCheckboxCurrentOptions[ptCheckboxSelectedLabelId];

                        // Start string with current group label
                        var ptCheckboxCurrentOptionsString = ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('data-pt-original-label');

                        // Convert current options to a string for HTML hidden field

                        var ptCheckboxFormValueString = '';
                        for (var key in ptCheckboxCurrentOptions) {
                            ptCheckboxFormValueString += ptCheckboxFormValueString == '' ? ptCheckboxCurrentOptions[key] : ', ' + ptCheckboxCurrentOptions[key];
                        }

                        // Convert to a format that is save for HTML fields
                        ptCheckboxCurrentOptions = JSON.stringify(ptCheckboxCurrentOptions);

                        // Add updated options to data-pt-checked-options attribute
                        ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('data-pt-checked-options', ptCheckboxCurrentOptions);

                        // Add current options string to hidden HTML field
                        ptForm.find("[name='" + ptCheckboxFormGroupLabelId + "']").attr('value', ptCheckboxCurrentOptionsString);
                        ptForm.find("[name='" + ptCheckboxFormValue + "']").attr('value', ptCheckboxFormValueString);

                    }

                    //
                    // Update limit
                    //

                    var ptCheckboxFormGroupLimitData = $checkbox.attr('name').replace('[amount]', '[limit_data]');

                    var ptCheckboxItemId = ptForm.find("[name='" + ptCheckboxFormGroupLimitData + "']").val();

                    if ( ptCheckboxItemId !== undefined ) {

                        ptCheckboxItemId = JSON.parse(ptCheckboxItemId);

                        if (typeof ptCheckboxItemId === 'object') {

                            var ptCheckboxSelectedItemId = $checkbox.data('item_id');

                            delete ptCheckboxItemId[ptCheckboxSelectedItemId];

                            ptCheckboxItemId = JSON.stringify(ptCheckboxItemId);

                            ptForm.find("[name='" + ptCheckboxFormGroupLimitData + "']").val(ptCheckboxItemId);

                        }
                    }
                }
            }


            function update_totals(e = false) {

                if (e) {
                    if($(e.target).attr('type') && $(e.target).attr('type') == 'number') {
                        quantity_input_validation($(e.target));
                    }

                    if($(e.target).hasClass('paytium-spinner')) {
                        let input = $(e.target).siblings('.pt-quantity-input');
                        if ($(input).attr('type') && $(input).attr('type') == 'number') {
                            quantity_input_validation($(input));
                        }
                    }
                }

                var total = get_form_total(),
                    first_payment_amount = get_first_payment_amount();
                update_checkbox_field($(this));

                if (ptFormAmountDiscountData) {

                    let discountAmountCount = ptFormAmountDiscountData.length,
                        i = 0;
                    $(ptFormAmountDiscountData).each(function (k, v) {

                        let total_without_discount = parseFloat(get_form_total(false));

                        if ( (ptFormCurrentAmountDiscount !== parseFloat(v.discount) && total_without_discount >= parseFloat(v.amount)) ||
                            (ptFormCurrentAmountDiscount === parseFloat(v.discount) && total_without_discount < parseFloat(v.amount)) ) {

                            if (ptFormCurrentAmountDiscount > parseFloat(v.discount) && total_without_discount >= ptFormCurrentAmount) {
                                return;
                            }
                            let data = v;
                            if (total_without_discount < parseFloat(v.amount)) {
                                if (k < discountAmountCount-1) {
                                    data = ptFormAmountDiscountData[k+1];
                                }
                                else {
                                    i++;
                                    return;
                                }
                            }

                            ptFormCurrentAmountDiscount = parseFloat(data.discount);
                            ptFormCurrentAmount = parseInt(data.amount);

                            let loaderContainer = $( '<div>', {
                                'class': 'pt-checkout-form-loading'
                            }).appendTo( ptForm );

                            let loader = $( '<div>', {
                                'class': 'pt-loader'
                            }).appendTo( loaderContainer );

                            $.ajax({
                                url: paytium_localize_script_vars.admin_ajax_url,
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    'action': "pt_ajax_amount_discount",
                                    'id': ptFormAmountDiscount.attr('id'),
                                    'data': {
                                        'current_amount': total_without_discount,
                                        'amount': data.amount,
                                        'discount': data.discount,
                                        'type': data.type
                                    }
                                },
                                success: function (response) {

                                    if (response.error) {

                                        ptFormCurrentAmountDiscount = 0;
                                        ptFormCurrentAmount = 0;
                                        // Remove discount fields
                                        ptForm.find(".pt-discount-type").remove();
                                        ptForm.find(".pt-discount-value").remove();
                                        ptForm.find(".pt-amount-discount-hidden").remove();

                                        ptForm.find('.pt-discount-true').hide();

                                        total = get_form_total();
                                        update_totals_helper(total,first_payment_amount)
                                    }
                                    else {
                                        // Remove discount fields
                                        ptForm.find(".pt-discount-type").remove();
                                        ptForm.find(".pt-discount-value").remove();
                                        ptForm.find(".pt-amount-discount-hidden").remove();

                                        ptForm.find('.pt-discount-true').hide();

                                        $("<input>", {
                                            type: "hidden",
                                            name: 'pt-amount-discount',
                                            class: 'pt-amount-discount-hidden',
                                            value: response.amount
                                        }).appendTo(ptForm);
                                        $("<input>", {
                                            type: "hidden",
                                            name: 'pt-discount-uid',
                                            class: 'pt-discount-uid',
                                            value: response.uid
                                        }).appendTo(ptForm);
                                        $("<input>", {
                                            type: "hidden",
                                            name: 'pt-discount[type]',
                                            class: 'pt-discount-type',
                                            value: response.type
                                        }).appendTo(ptForm);
                                        $("<input>", {
                                            type: "hidden",
                                            name: 'pt-discount[value]',
                                            class: 'pt-discount-value',
                                            value: response.value
                                        }).appendTo(ptForm);

                                        ptForm.find('.pt-discount-true').show();

                                        total = get_form_total_with_discount(total_without_discount);
                                        update_totals_helper(total,first_payment_amount)
                                    }
                                    loaderContainer.remove();
                                },
                            });
                            return false;
                        }
                        else if(total_without_discount < parseFloat(v.amount)) {
                            update_totals_helper(total,first_payment_amount);
                            i++;
                        }
                    });

                    if (discountAmountCount === i) {
                        ptFormCurrentAmountDiscount = 0;
                        ptFormCurrentAmount = 0;
                        // Remove discount fields
                        ptForm.find(".pt-discount-type").remove();
                        ptForm.find(".pt-discount-value").remove();
                        ptForm.find(".pt-amount-discount-hidden").remove();

                        ptForm.find('.pt-discount-true').hide();
                        total = get_form_total();
                        // console.log('here');
                        // console.log(total);
                        update_totals_helper(total,first_payment_amount)
                    }
                    else update_totals_helper(total,first_payment_amount);
                }
                else update_totals_helper(total,first_payment_amount);
            }

            function update_totals_helper(total,first_payment_amount) {

                ptForm.find('.pt-total-amount').html( currencyFormattedAmount( total, ptFormCurrencySymbol ) );
                ptForm.find('.pt_amount').val( total );

                var ptFirstPaymentLabel = ptForm.find("[id^=pt-subscription-first-payment-label]").length ?
                    ptForm.find("[id^=pt-subscription-first-payment-label]").val() + ': ' :
                    paytium_localize_script_vars.subscription_first_payment + ' ';

                ptForm.find('.pt-first-payment-amount').html( ptFirstPaymentLabel + currencyFormattedAmount(first_payment_amount, ptFormCurrencySymbol) );

                var minTotalInput = ptForm.find('.pt-total-minimum');
                if (minTotalInput.length) {
                    minTotalInput.val( total );
                    if (total >= minTotalInput.data('parsley-mintotal') && ptForm.find('div[id^=pt_total_errors]').find('ul').hasClass('filled')) {
                        ptForm.find('div[id^=pt_total_errors]').find('ul').removeClass('filled');
                    }
                }

            }
            update_totals();

            $(ptForm).submit(function(e) {
                if (!validateFormAmounts()) e.preventDefault();
            });

            function validateFormAmounts() {

                let formAmounts = [];
                ptForm.find(".pt-cf-amount").each(function () {
                    if ($(this).is('select')) {
                        $($(this).children('option')).each(function () {
                            let ptPrice = $(this).attr('data-pt-price');
                            formAmounts.push(ptPrice);
                        })
                    }
                    else {
                        let ptPrice = $(this).attr('data-pt-price');
                        formAmounts.push(ptPrice);
                    }
                });

                if (JSON.stringify(ptFormAmounts) !== JSON.stringify(formAmounts)) {
                    window.alert(paytium_localize_script_vars.validation_failed);
                    return false;
                }
                else return true;
            }

            function submitFormProcessing(event) {

                if (!validateFormAmounts()) return;

                debug_log('click.ptPaymentBtn fired');

                if (ptForm.parsley({
                        excluded: 'input[type=button], input[type=submit], input[type=reset]',
                        inputs: 'input, textarea, select, input[type=hidden], :hidden',
                    }).validate()) {

                    // Run totals one more time to ensure the total amount is accurate
                    update_totals();

                    //
                    // START - Process (custom) fields
                    //

                    // Process individual fields
                    $(ptForm.find("[id^=pt-field-]")).each(function (index, element) {

                        var ptFieldValue = $(element).val(); // Get the field value
                        var ptUserLabel = document.getElementById(this.id).getAttribute('data-pt-user-label'); // Get the user defined field label
                        var ptFieldType = document.getElementById(this.id).getAttribute('data-pt-field-type'); // Get the field type

                        // Get required attribute
                        var required = $(element).attr("required");

                        // Validate that required fields are filled
                        if ((required == 'required') && ptFieldValue == '') {

                            window.alert(paytium_localize_script_vars.field_is_required.replace('%s', ptUserLabel));
                            debug_log('ProcessFailed');
                            return false;
                        }

                        // Log everything to Console when troubleshooting
                        debug_log($(element));
                        debug_log('Processing field (type, label, value, id): ' + ptFieldType + ', ' + ptUserLabel + ', ' + ptFieldValue + ', ' + this.id);

                        //
                        // Add the user's field label to form post data, so it can be used as user-facing identifier for that field
                        //

                        // Create unique field ID for the user's field label
                        var ptUserLabelLabel = this.id + "-label";

                        // Add the unique field ID and user's label to the form post data
                        $("<input>", {type: "hidden", name: ptUserLabelLabel, value: ptUserLabel}).appendTo(ptForm);

                        //
                        // Check if field is set as user_data="true" and store the preference if so
                        //
                        var ptUserData = document.getElementById(this.id).getAttribute('data-pt-user-data'); // Get the field type

                        if (ptUserData == 'true') {
                            // Create unique field ID for the user's field label
                            var ptUserDataLabel = this.id + "-user-data";

                            // Add the unique field ID and user's label to the form post data
                            $("<input>", {type: "hidden", name: ptUserDataLabel, value: ptUserData}).appendTo(ptForm);
                        }

                    });

                    //
                    // END - Process (custom) fields
                    //

                    //
                    // START - Process subscription(s)
                    //

                    // Process subscription fields
                    $(ptForm.find("[id^=pt-subscription-]")).each(function (index, element) {

                        if (this.disabled) {
                            return false;
                        }

                        // Get the field value
                        var ptFieldValue = $(element).val();

                        // Get the field type
                        // this.id is field type

                        // Log everything to Console when troubleshooting
                        debug_log($(element));
                        debug_log('Processing ' + this.id + ', ' + ptFieldValue);

                        // Add the unique field ID and user's label to the form post data
                        $("<input>", {type: "hidden", name: this.id, value: ptFieldValue}).appendTo(ptForm);

                    });

                    // Process subscription first payment
                    if (ptForm.find("[id^=pt-subscription-first-payment]").length > 0) {

                        // Get recurring amount/form total before first payment
                        var ptRecurringTotal = ptForm.find(".pt_amount").val();

                        // Add recurring amount
                        $("<input>", {
                            type: "hidden",
                            name: 'pt-subscription-recurring-payment',
                            value: ptRecurringTotal
                        }).appendTo(ptForm);

                        // Add "First payment" text and amount after the [paytium_total /] Shortcode
                        var ptFirstPayment = ptForm.find("[id^=pt-subscription-first-payment]").val();

                        // Update total amount to first payment
                        ptForm.find('.pt_amount').val(ptFirstPayment);

                    }

                    //
                    // END - Process subscription fields
                    //

                    //
                    // START - Remove discount field if it's empty or type and value fields are missing
                    //

                    if (ptForm.find(".pt-discount").length > 0 && !ptForm.find(".pt-discount").val()) {
                        ptForm.find(".pt-discount").prop('disabled', 'true');
                    }

                    if (!ptForm.find('[name$="pt-discount[type]"]').length || !ptForm.find('[name$="pt-discount[value]"]').length) {
                        ptForm.find(".pt-discount").prop('disabled', 'true');
                    }

                    //
                    // END - Remove discount field if it's empty
                    //

                    // If there is no amount entered or amount is too low to be processed by Mollie
                    // block execution of script and show an alert. Why 1 euro? Lower amounts
                    // are just not logical!
                    if ((get_form_total() <= '0.99') && (isPaytiumNoPayment() == false)) {
                        window.alert(paytium_localize_script_vars.amount_too_low);
                        return false;
                    }

                    // Enable the below line if you want to process the form without redirecting
                    // The form data is not stored in Paytium at this point
                    // Also see line 24 in /paytium/includes/process-payment-functions.php
                    //return false;

                    // Unbind original form submit trigger before calling again to "reset" it and submit normally.
                    ptForm.unbind('submit');
                    ptForm.submit();

                    // Disable original payment button and change text for UI feedback while POST-ing to Mollie
                    ptForm.find('.pt-payment-btn')
                        .prop('disabled', true)
                        .find('span')
                        .text(paytium_localize_script_vars.processing_please_wait);

                }

                if (typeof event != 'undefined') {
                    event.preventDefault();
                }
            }

            function check_item_limits(event) {

                var data = {};
                var $item_id_input;

                event.preventDefault();

                $(ptForm.find('[name$="[item_id]"], .pt-checkbox-group')).each(function (index, element) {

                    var $limit_data = $(element).find('[name$="[limit_data]"]');
                    if($limit_data.length) {
                        var json_data = JSON.parse($limit_data.val());
                        $.each(json_data, function (item_id, value) {
                            data[item_id] = value;
                        })
                    }
                    else {
                        var item_id = $(element).val();
                        var limit = $(element).siblings('[name$="[limit]"]').val();
                        var quantity = $(element).siblings('.pt-quantity-input').val();
                        data[item_id] = {'limit': limit, 'quantity': quantity};
                    }

                });

                if(!$.isEmptyObject(data)) {

                    $.ajax({
                        url: paytium_localize_script_vars.admin_ajax_url,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            'action': "pt_ajax_check_item_limits",
                            'data': data
                        },
                        success: function (response) {

                            $('.limit-error-message').remove();
                            if (response.error) {
                                $.each(response.limit_exceeded, function (item_id, data) {
                                    $item_id_input = $('[value="' + item_id + '"]').length ?  $('[value="' + item_id + '"]') : $('[data-item_id="' + item_id + '"]');

                                    $item_id_input.before('<p class="limit-error-message">Limit reached! Only ' + data.items_left + ' items left</p>');

                                });
                            }
                            else {
                                submitFormProcessing();
                            }
                        },

                    });
                }

                else  {
                    submitFormProcessing();
                }

            }

            //
            // START - Paytium Links
            //

            ptForm.find("[id^=pt-paytium-links]").each(function () {

                // Create an object with all data
                function getSearchParameters() {
                    var prmstr = window.location.search.substr(1);
                    prmstr = decodeURIComponent(prmstr);
                    return prmstr != null && prmstr != "" ? transformToAssocArray(prmstr) : {};
                }

                function transformToAssocArray(prmstr) {
                    if (prmstr.indexOf('%20') != -1) {
                        prmstr = decodeURIComponent(prmstr);
                    }
                    var params = {};
                    var prmarr = prmstr.split("&");
                    for (var i = 0; i < prmarr.length; i++) {
                        var tmparr = prmarr[i].split("=");
                        params[tmparr[0]] = tmparr[1];
                    }
                    return params;
                }

                var params = getSearchParameters();

                $.each(params, function (key, valueObj) {

                    $(ptForm.find("[id^=pt-field-]")).each(function (index, element) {

                        // Get the user defined field label
                        var ptUserLabel = document.getElementById(this.id).getAttribute('data-pt-user-label');


                        if (ptUserLabel == key) {
                            $(element).val(valueObj);
                        }

                    });

                    if (ptForm.find('#pt-paytium-pro').length && ptForm.find('.pt-quantity-input').length && key.toLowerCase() == 'quantity') {
                        ptForm.find('.pt-quantity-input').val(valueObj);
                    }

                    if (key.toLowerCase() == 'bedrag' || key.toLowerCase() == 'amount') {
                        ptForm.find("[name*='pt-amount']").val(valueObj);
                        ptForm.find('.pt-uea-custom-amount').val(valueObj);

                        ptForm.find('.pt-uea-custom-amount-formatted').val(parseAmount(valueObj)).attr('data-pt-price', parseAmount(valueObj));

                        ptForm.find("[name*='pt-amount']").attr('data-pt-price', parseAmount(valueObj));
                        ptForm.find('.pt-uea-custom-amount').attr('data-pt-price', parseAmount(valueObj));

                        update_totals();
                    }

                });

                ptForm.find("[id^=pt-paytium-links-auto-redirect]").each(function () {
                    ptForm.find('.pt-payment-btn').click(submitFormProcessing());
                });

            });

            //
            // END - Paytium Links
            //

            ptForm.find('.pt-payment-btn').on('click.ptPaymentBtn', check_item_limits);

            // Multiplier functionality
            ptForm.find('.pt_multiplier')
                .each(function () {
                    pt_multiply($(this));
                })
                .on('change keyup', function () {
                pt_multiply($(this));
            });

            function pt_multiply($this) {

                let multiplier_id = $this.data('pt_multiplier_id'),
                    multiplier_val = +$this.val();

                $(ptForm.find('.pt-multiplied-by')).each(function () {
                    if ($(this).data('pt_multiplied_by').indexOf(multiplier_id) > -1) {

                        let multiplied_by = $(this).data('pt_multiplied_by'),
                            amount_input = $(this).siblings('.pt-cf-amount'),
                            amount_val = amount_input.val(),
                            amount_html = $(this).siblings('.pt-quantity-amount'),
                            multiplier = multiplier_val;

                        if (!$(this).data('start_amount')) {
                            $(this).data('start_amount',+amount_val)
                        }

                        if ($(this).data('pt_multiplied_by').length > multiplier_id.length) {

                            let multipliers_arr = multiplied_by.split(',');

                            $.each(multipliers_arr, function (i,v) {
                                multiplier = multiplier_id !== v ? multiplier * ptForm.find('.pt_multiplier[data-pt_multiplier_id='+v+']').val() : multiplier;
                            })
                        }

                        let new_amount = parseAmount($(this).data('start_amount') * multiplier);

                        amount_input.attr('data-pt-price', new_amount);
                        amount_html.text(currencyFormattedAmount(new_amount, ptFormCurrencySymbol));
                    }
                });

                if (ptForm.find('.pt-discount-checkbox-with-condition').length) {

                    let discountCbGroup = ptForm.find('.pt-discount-checkbox-with-condition'),
                        cb = discountCbGroup.find('.pt-discount-checkbox');

                    if (multiplier_id === discountCbGroup.data('condition-id')) {
                        if (multiplier_val >= discountCbGroup.data('condition-qty')) {
                            cb.prop('disabled',false);
                        }
                        else {
                            // Remove checkbox discount fields
                            ptForm.find(".pt-discount-type").remove();
                            ptForm.find(".pt-discount-value").remove();
                            ptForm.find(".pt-discount-checkbox-hidden").remove();

                            ptForm.find('.pt-discount-true').hide();
                            cb.prop('checked',false).prop('disabled',true).parent().removeClass('checked');
                        }
                    }
                    $(ptForm.find('.pt-multiplied-by'));
                }
                update_totals();
            }


            ptForm.find('.pt-discount-checkbox').on('change', function(e) {

                let loaderContainer = $( '<div>', {
                    'class': 'pt-checkout-form-loading'
                }).appendTo( ptForm );

                let loader = $( '<div>', {
                    'class': 'pt-loader'
                }).appendTo( loaderContainer );

                $(this).prop('disabled',true);

                let cb = $(this),
                    checked = '',
                    data = {};

                data.id = cb.attr('id');
                if ($(this).parents('.pt-discount-checkbox-with-condition').length) {
                    let cbGroup = $(this).parents('.pt-discount-checkbox-with-condition'),
                        conditionId = cbGroup.data('condition-id'),
                        conditionQty = cbGroup.data('condition-qty'),
                        conditionField = ptForm.find('.pt_multiplier[data-pt_multiplier_id="'+conditionId+'"]'),
                        conditionFieldVal = +conditionField.val();

                    data.condition_qty = conditionQty;
                    data.condition_field_val = conditionFieldVal;

                    if (conditionFieldVal < conditionQty) {
                        // console.log(conditionFieldVal < conditionQty);
                        $(this).prop('checked',false);
                        return false;
                    }
                }


                if ($(this).prop('checked')) {
                    checked = true;
                    $(this).parent().addClass('checked')
                }
                else {
                    checked = false;
                    cb.parent().removeClass('checked');
                }
                // console.log(data);

                $.ajax({
                    url: paytium_localize_script_vars.admin_ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        'action': "pt_ajax_discount_checkbox",
                        'data': data
                    },
                    success: function (response) {

                        if (response.error) {
                            // $.each(response.limit_exceeded, function (item_id, data) {
                            //     $item_id_input = $('[value="' + item_id + '"]').length ?  $('[value="' + item_id + '"]') : $('[data-item_id="' + item_id + '"]');
                            //
                            //     $item_id_input.before('<p class="limit-error-message">Limit reached! Only ' + data.items_left + ' items left</p>');
                            //
                            // });
                            console.log('discount checkbox error');
                        }
                        else {
                            console.log('discount checkbox success');

                            if (checked) {
                                $("<input>", {
                                    type: "hidden",
                                    name: 'pt-discount-checkbox',
                                    class: 'pt-discount-checkbox-hidden',
                                    value: response.checkbox
                                }).appendTo(ptForm);
                                $("<input>", {
                                    type: "hidden",
                                    name: 'pt-discount-uid',
                                    class: 'pt-discount-uid',
                                    value: response.uid
                                }).appendTo(ptForm);
                                $("<input>", {
                                    type: "hidden",
                                    name: 'pt-discount[type]',
                                    class: 'pt-discount-type',
                                    value: response.type
                                }).appendTo(ptForm);
                                $("<input>", {
                                    type: "hidden",
                                    name: 'pt-discount[value]',
                                    class: 'pt-discount-value',
                                    value: response.value
                                }).appendTo(ptForm);

                                ptForm.find('.pt-discount-true').show();
                            }
                            else {
                                // Remove discount fields
                                ptForm.find(".pt-discount-type").remove();
                                ptForm.find(".pt-discount-value").remove();
                                ptForm.find(".pt-discount-checkbox-hidden").remove();

                                ptForm.find('.pt-discount-true').hide();
                            }

                            update_totals();
                        }

                        cb.prop('disabled',false);
                        loaderContainer.remove();
                    },

                });
            });

            function general_limit_control(input) {

                if (input !== undefined) {

                    if (input.hasClass('general-limit-qty')) {

                        let currentGeneralQty = 0,
                            currentVal = input.val();

                        ptForm.find(".general-limit-qty").each(function () {
                            currentGeneralQty = currentGeneralQty + parseInt($(this).val());
                        });

                        ptForm.find(".general-limit-item").each(function () {
                            if ((($(this).attr('type') === 'radio' || $(this).attr('type') === 'checkbox') && $(this).prop('checked')) ||
                                  $(this).attr('type') === 'select' || $(this).attr('label')) {
                                currentGeneralQty = currentGeneralQty + 1;
                            }
                        });

                        if (currentGeneralQty > ptFormGeneralLimit) {
                            let newVal = currentVal - (currentGeneralQty - ptFormGeneralLimit);
                            return {'result': false, 'new_val': newVal};
                        }
                    }
                }
            }

        });

        // Convert to formatted amount
        function currencyFormattedAmount(amount, currencySymbol) {
            amount = parseAmount( amount ) + ''; // Convert to string
            amount = Number(Math.round(amount+'e2')+'e-2').toFixed(2);
            amount = amount.replace( '.', pt.decimal_separator );
            return currencySymbol == 'fr.' || currencySymbol == 'NOK' || currencySymbol == 'SEK' ? amount + " " + currencySymbol : currencySymbol + " " + amount;
        }

		/**
		 * Parse to a valid amount.
         * @returns float Valid number.
         */
        function parseAmount( amount ) {
            if ( typeof amount == 'string' ) {
                amount = amount.replace( ',', '.' );
            }

            if ( isNaN( amount ) || amount == '' ) {
                amount = 0;
            }

            amount = parseFloat( amount );
            return roundToTwo( amount );
        }

        // https://stackoverflow.com/a/18358056/3389968
        function roundToTwo(num) {
            return +(Math.round(num + "e+2")  + "e-2");
        }
    });

    window.Parsley
        .addValidator('filemaxmegabytes', {
            requirementType: 'string',
            validateString: function (value, requirement, parsleyInstance) {

                var file = parsleyInstance.$element[0].files;
                var maxBytes = requirement * 1048576;

                if (file.length == 0) {
                    return true;
                }

                var files_data = [];
                $(file).each(function(i,v){
                    if (v.size > maxBytes) {
                        files_data.push(v.size);
                    }
                });

                return files_data.length === 0;

            },
            messages: {
                en: 'File is to big. Maximum allowed size is %s MB.',
                nl: 'Het bestand is te groot, de maximale bestandsgrootte is %s MB.'
            }
        })
        .addValidator('maxfiles', {
            requirementType: 'string',
            validateString: function (value, requirement, parsleyInstance) {

                var file = parsleyInstance.$element[0].files;

                if (file.length == 0) {
                    return true;
                }

                return file.length <= requirement;

            },
            messages: {
                en: 'Maximum of files can be uploaded at once is %s.',
                nl: 'Het maximum aantal bestanden dat tegelijkertijd verstuurd mag worden is %s. '
            }
        })
        .addValidator('filemimetypes', {
            requirementType: 'string',
            validateString: function (value, requirement, parsleyInstance) {

                var file = parsleyInstance.$element[0].files;

                if (file.length == 0) {
                    return true;
                }

                var allowedMimeTypes = requirement.replace(/\s/g, "").split(',');
                var files_data = [];

                $(file).each(function(k,v){

                    if (allowedMimeTypes.indexOf(v.type) == -1) {
                        files_data.push(allowedMimeTypes.indexOf(v.type) == -1);
                    }

                });

                return files_data.length === 0;
            },
            messages: {
                en: 'File mime type not allowed.'
            }
        })
        .addValidator('postcode', {
        requirementType: 'string',
        validateString: function(value, requirement, parsleyInstance) {
            var postcode = parsleyInstance.value.match(/^\d{4}\s?\w{2}$/g);

            return postcode !== null;
        },
        messages: {
            en: 'The postcode entered is invalid, the format should be 1234AB.',
            nl: 'De ingevulde postcode is niet correct, en moet formaat 1234AB hebben.'
        }
    })
        .addValidator('date', {
            requirementType: 'string',
            validateString: function (value, requirement, parsleyInstance) {

                var reqs = value.split("-"),
                    day = reqs[0],
                    month = reqs[1],
                    year = reqs[2].substring(0, 4);

                // check if date is a valid
                var checkdate = new Date(year + "-" + month + "-" + day);
                return Boolean(+checkdate) && parseInt(checkdate.getDate(), 10) === parseInt( day, 10);

            },
            messages: {
                en: 'The date entered is invalid, the format should be DD-MM-YYYY.',
                nl: 'De ingevulde datum is niet correct, en moet formaat DD-MM-JJJJ hebben.'
            }
        })
        .addValidator('open', {
            requirementType: 'string',
            validateString: function (value, requirement, parsleyInstance) {

                let actualValue = $(parsleyInstance.$element).parents('.pt-uea-container-amount').siblings('.pt-uea-custom-amount-formatted').attr('data-pt-price');

                value = value.toString();
                value = parseFloat(value.replace(/,/g, '.'));

                requirement = requirement.toString();
                requirement = parseFloat(requirement.replace(/,/g, '.'));

                if (requirement <= value && value == actualValue) {
                    return true;
                }
                return false;

            },
            messages: {
                en: 'The minimum amount is '+currencySymbol+'%s.',
                nl: 'Het minimum bedrag is '+currencySymbol+'%s.'
            }
        })
        .addValidator('mintotal', {
            requirementType: 'string',
            validateString: function (value, requirement, parsleyInstance) {
                return parseFloat(value) >= requirement;
            },
            messages: {
                en: 'The minimum amount is '+currencySymbol+'%s.',
                nl: 'Het minimum bedrag is '+currencySymbol+'%s.'
            }
        })
        .addValidator('phone', {
            requirementType: 'string',
            validateString: function (value, requirement, parsleyInstance) {

                if (value.match('^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\\s\\.0-9]*$')) {
                    return true;
                }
                else {
                    return false;
                }
            },
            messages: {
                en: 'Please enter a valid phone number',
                nl: 'Voer alstublieft een geldig telefoonnummer in'
            }
        });


    $(document).ready(function () {

        var ptPaytiumDate = jQuery('.pt-paytium-date');

        if (ptPaytiumDate.length > 0) {
            ptPaytiumDate.datepicker({
                dateFormat: 'dd-mm-yy',
                changeMonth: true,
                changeYear: true,
            });
        }
    });

    $(document).ready(function () {

        var ptPaytiumBirthday = jQuery('.pt-paytium-birthday');

        if (ptPaytiumBirthday.length > 0) {
            ptPaytiumBirthday.datepicker({
                dateFormat: 'dd-mm-yy',
                changeMonth: true,
                changeYear: true,
                yearRange: '-85:+0'
            });
        }
    });

    // Remove message after payment if more then once is shown
    $(document).ready(function () {
        $('.pt-payment-details-wrap').eq(1).remove();
    });

    // Paytium login
    $('.paytium-login-btn').on('click', function () {
        $('.paytium-login-form-overlay').fadeIn(200);
        $('html').addClass('paytium-overflow-hidden')
    });

    $('.paytium-login-close').on('click', function () {
        $('.paytium-login-form-overlay').fadeOut(200);
        $('html').removeClass('paytium-overflow-hidden')
    });

    $('.paytium-login-form-wrapper').on('click', function (e) {
        if ($(e.target).is('.paytium-login-form-wrapper')) {
            $('.paytium-login-form-overlay').fadeOut(200);
            $('html').removeClass('paytium-overflow-hidden')
        }
    });

    function getCurrencySymbol(currencyCode) {
        var currencies = {
            EUR: '€',
            USD: '$',
            GBP: '£',
            CHF: 'fr.',
            NOK: 'NOK',
            SEK: 'SEK'
        };
        return currencies[currencyCode];
    }


    /* paytium custom quantity input */
    $('.paytium-spinner.increment').on('click', function () {
        let input = $(this).siblings('.pt-quantity-input[type=number]'),
            val = parseInt(input.val()),
            max = input.attr('max') ? parseInt(input.attr('max')) : '',
            step = input.attr('step') ? parseInt(input.attr('step')) : 1;

        if ((max && val+step <= max) || !max) {
            $(input).val(val+step)
        }
    });
    $('.paytium-spinner.decrement').on('click', function () {
        let input = $(this).siblings('.pt-quantity-input[type=number]'),
            val = parseInt(input.val()),
            min = input.attr('min') ? parseInt(input.attr('min')) : '',
            step = input.attr('step') ? parseInt(input.attr('step')) : 1;

        if ((min && val-step >= min) || (!min && val-step >= 0)) {
            $(input).val(val-step)
        }
    });

}(jQuery));