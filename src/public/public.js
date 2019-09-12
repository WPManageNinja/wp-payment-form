import StripeElementHandler from './StripeElementHandler';
import StripeCheckoutHandler from './StripeCheckoutHandler';
import formatPrice from '../common/formatPrice';

var wpPayformApp = {};
var recaptchInstances = {};
(function ($) {
    wpPayformApp = {
        forms: {},
        general: window.wp_payform_general,
        formData: {},
        init() {
            let body = $(document.body);
            this.forms = body.find('.wpf_form');
            this.forms.each(function () {
                var form = $(this);
                wpPayformApp.initForm(form);
                body.trigger('wpPayFormProcessFormElements', [form]);
            });
            this.initDatePiker();
            this.initLightBox();
            $('.wpf_form input').on('keypress', function (e) {
                return e.which !== 13;
            });
        },
        initLightBox() {
            if($('.wpf_form .wpf_lightbox').length) {
                $('.wpf_form .wpf_lightbox').on('click', lity);
            }
        },
        initForm(form) {
            let that = this;
            let form_settings = window['wp_payform_' + form.data('wpf_form_id')];
            this.calculatePayments(form);

            if (parseInt(form.find('input[name=__wpf_valid_payment_methods_count]').val()) > 1) {
                let defaultSelected = form.find('input[name=__wpf_selected_payment_method]:checked').val();
                that.handlePaymentMethodChange(form, defaultSelected);
                form.find('input[name=__wpf_selected_payment_method]').on('change', function () {
                    form.trigger('payment_method_changed', $(this).val());
                });
                form.on('payment_method_changed', (event, value) => {
                    that.handlePaymentMethodChange(form, value);
                });
            } else {
                // We have to check if any hidden / single payment method exists or not
                let paymentMethod = form.find('[data-wpf_payment_method]').data('wpf_payment_method');
                if (paymentMethod) {
                    form.data('selected_payment_method', paymentMethod);
                }
            }

            form.find('.wpf_payment_item, .wpf_item_qty, .wpf_tabular_qty').on('change', () => {
                this.calculatePayments(form);
            });

            let $cardElementDiv = form.find('.wpf_stripe_card_element');

            let cardEleementStyle = $cardElementDiv.data('checkout_style');
            form.on('submit', function (e) {
                e.preventDefault();

                // Version 2 verfication
                if(form.attr('data-recaptcha_version') == 'v2') {
                    let recaptchInstance = recaptchInstances['form_'+form.data('wpf_form_id')];
                    if(recaptchInstance != undefined) {
                        let response = grecaptcha.getResponse(recaptchInstance);
                        let $errorDiv = form.parent().find('.wpf_form_errors');
                        if(!response) {
                            $errorDiv.html('<p class="wpf_form_error_heading">Please verify recaptcha first</p>').show();
                            return false;
                        } else {
                            $errorDiv.html('').hide();
                        }
                    }
                }

                let selectedPaymentMethod = form.data('selected_payment_method');
                if (selectedPaymentMethod == 'stripe') {
                    // we have the selected payment method! So, we are triggering that
                    form.trigger(selectedPaymentMethod + '_payment_submit');
                    // We have to do a promise based method because all payment methods does not have
                    // onpage checkout anc callbacks
                } else {
                    that.submitForm(form);
                }
            });

            if (cardEleementStyle == 'embeded_form') {
                let cardElementId = $cardElementDiv.attr('id');
                let elementHandler = StripeElementHandler;
                elementHandler.init({
                    form: form,
                    elementId: cardElementId,
                    style: false,
                    pub_key: form_settings.stripe_pub_key
                }, function () {
                    that.submitForm(form);
                });
            } else if (cardEleementStyle == 'stripe_checkout') {
                let checkoutSettings = {
                    form: form,
                    billing: $cardElementDiv.data('require_billing_info') == 'yes',
                    shipping: $cardElementDiv.data('require_shipping_info') == 'yes',
                    verify_zip: $cardElementDiv.data('verify_zip') == 'yes',
                    allowRememberMe: $cardElementDiv.data('allow_remember_me') == 'yes',
                    form_settings: form_settings,
                    pub_key: form_settings.stripe_pub_key
                }
                StripeCheckoutHandler.init(checkoutSettings, function () {
                    that.submitForm(form);
                });
            }

            this.maybeSubscriptionSetup(form);
            this.maybeCustomSubscriptionItemSetup(form);

            jQuery(document.body).trigger('wpfFormInitialized', [form]);
            jQuery(document.body).trigger('wpfFormInitialized_' + form.data('wpf_form_id'), [form]);
            form.addClass('wpf_form_initialized');
        },
        submitForm(form) {
            form.find('button.wpf_submit_button').attr('disabled', true);
            form.addClass('wpf_submitting_form');
            form.parent().find('.wpf_form_notices').hide();
            let formId = form.data('wpf_form_id');
            form.trigger('wpf_form_submitting', formId);
            $.post(this.general.ajax_url, {
                action: 'wpf_submit_form',
                form_id: formId,
                payment_total: form.data('payment_total'),
                form_data: $(form).serialize()
            })
                .then(response => {
                    if(!response || !response.data || !response.data.confirmation) {
                        let $errorDiv = form.parent().find('.wpf_form_errors');
                        $errorDiv.html('<p class="wpf_form_error_heading">Something is wrong when submitting the form</p>').show();
                        $errorDiv.append('<div class="wpf_error_items">Server Response: ');
                        $errorDiv.append('<p>'+response+'</p>');
                        $errorDiv.append('</div>');
                        form.parent().addClass('wpf_form_has_errors');
                        form.trigger('wpf_form_fail_submission', response);
                        form.removeClass('wpf_submitting_form');
                        form.removeClass('wpf_submitting_form');
                        form.find('button.wpf_submit_button').removeAttr('disabled');
                        return;
                    }

                    let confirmation = response.data.confirmation;
                    form.parent().addClass('wpf_form_submitted');
                    form.trigger('wpf_form_submitted', response.data);
                    if (confirmation.redirectTo == 'samePage') {
                        form.removeClass('wpf_submitting_form');
                        form.find('button.wpf_submit_button').removeAttr('disabled');
                        form.parent().removeClass('wpf_form_has_errors');

                        form.parent().find('.wpf_form_success').html(confirmation.messageToShow).show();
                        if (confirmation.samePageFormBehavior == 'hide_form') {
                            form.hide();
                            $([document.documentElement, document.body]).animate({
                                scrollTop: form.parent().find('.wpf_form_success').offset().top - 100
                            }, 200);
                        }
                        $('#wpf_form_id_' + formId)[0].reset();
                        form.trigger('stripe_clear');
                    } else if (confirmation.redirectTo == 'customUrl') {
                        if (confirmation.messageToShow) {
                            form.parent().find('.wpf_form_success').html(confirmation.messageToShow).show();
                        }
                        window.location.href = confirmation.customUrl;
                        return false;
                    }
                })
                .fail(error => {
                    let $errorDiv = form.parent().find('.wpf_form_errors');
                    $errorDiv.html('<p class="wpf_form_error_heading">' + error.responseJSON.data.message + '</p>').show();
                    $errorDiv.append('<ul class="wpf_error_items">');
                    $.each(error.responseJSON.data.errors, (errorId, errorText) => {
                        $errorDiv.append('<li class="error_item_' + errorId + '">' + errorText + '</li>');
                    });
                    $errorDiv.append('</ul>');
                    form.parent().addClass('wpf_form_has_errors');
                    form.trigger('wpf_form_fail_submission', error.responseJSON.data);
                    form.removeClass('wpf_submitting_form');

                    form.removeClass('wpf_submitting_form');
                    form.find('button.wpf_submit_button').removeAttr('disabled');

                    form.trigger('server_error', [error]);

                })
                .always(() => {
                    form.find('input[name=stripeToken]').remove();
                    if(form.attr('data-recaptcha_version') == 'v2') {
                        let recaptchInstance = recaptchInstances['form_'+form.data('wpf_form_id')];
                        if(recaptchInstance != undefined) {
                            grecaptcha.reset(recaptchInstance)
                        }
                    }
                    form.trigger('form_server_always',);
                });
        },
        calculatePayments(form) {
            let elements = form.find('.wpf_payment_item');
            let itemTotalValue = {};

            let subscriptonAmountTotal = 0;

            elements.each(function (index, elem) {
                let elementType = elem.type;
                let $elem = $(elem);
                let elementName = $elem.attr('name');
                if (elementType == 'radio') {
                    let $element = form.find('input[name=' + elementName + ']:checked');
                    let itemValue = $element.data('price');
                    if (itemValue) {
                        itemTotalValue[elementName] = parseInt(itemValue);
                    }
                    if($element.data['subscription_amount']) {
                        subscriptonAmountTotal += parseInt($element.data['subscription_amount']);
                    }
                }
                else if (elementType == 'hidden') {
                    let itemValue = $elem.data('price');
                    if (itemValue) {
                        itemTotalValue[elementName] = parseInt(itemValue);
                    }
                    if($elem.attr('data-subscription_amount')) {
                        subscriptonAmountTotal += parseInt($elem.attr('data-subscription_amount'));
                    }
                } else if ($elem.data('is_custom_price') == 'yes') {
                    let itemValue = $(this).val();
                    if (itemValue) {
                        itemTotalValue[elementName] = parseInt(parseFloat(itemValue) * 100);
                    }
                } else if (elementType == 'checkbox') {
                    let groupId = $elem.data('group_id');
                    let groups = form.find('input[data-group_id="' + groupId + '"]:checked');
                    let groupTotal = 0;
                    groups.each((index, group) => {
                        let itemPrice = $(group).data('price');
                        if (itemPrice) {
                            groupTotal += parseInt(itemPrice);
                        }
                    });
                    itemTotalValue[groupId] = groupTotal;
                }
                else if (elementType == 'select-one') {
                    let $element = form.find('select[name=' + elementName + '] option:selected');
                    let itemValue = $element.data('price');
                    if (itemValue) {
                        itemTotalValue[elementName] = parseInt(itemValue);
                    }

                    if($element.attr('data-subscription_amount')) {
                        subscriptonAmountTotal += parseInt($element.attr('data-subscription_amount'));
                    }
                }
            });
            let formSettings = window['wp_payform_' + form.data('wpf_form_id')];
            let allTotalAmount = 0;

            itemTotalValue = this.calculateTabularTotal(form, itemTotalValue, formSettings);


            // Get The Total Now
            jQuery.each(itemTotalValue, (itemName, itemValue) => {
                if (itemValue) {
                    // check if there has a quantity
                    let targetQuantity = form.find('.wpf_item_qty[data-target_product=' + itemName + ']');
                    if (targetQuantity.length) {
                        let qty = $(targetQuantity).val();
                        if (qty == 0 || parseInt(qty)) {
                            let lineTotal = Math.abs(parseInt(qty)) * itemValue;
                            itemTotalValue[itemName] = lineTotal;
                            allTotalAmount += lineTotal;
                        }
                    } else {
                        allTotalAmount += itemValue;
                    }
                }
            });

            let subTotal = allTotalAmount;
            let taxAmount = this.calCulateTaxAmount(form, itemTotalValue, formSettings);
            if (taxAmount) {
                allTotalAmount += taxAmount;
            }

            form.find('.wpf_calc_tax_total').html(formatPrice(taxAmount, formSettings.currency_settings));
            form.find('.wpf_calc_sub_total').html(formatPrice(subTotal, formSettings.currency_settings));
            form.find('.wpf_calc_payment_total').html(formatPrice(allTotalAmount, formSettings.currency_settings));
            form.data('payment_total', allTotalAmount);
            form.data('subscription_total', subscriptonAmountTotal);


            // Detect change of subscription custom amount

        },
        calCulateTaxAmount(form, itemizedValue, formSettings) {
            if (!form.hasClass('wpf_has_tax_item')) {
                return 0;
            }
            let taxLines = form.find('label.wpf_tax_line_item');
            let taxTotal = 0;
            $.each(taxLines, (index, lineItem) => {
                let $line = $(lineItem);
                let targetItem = $line.data('target_product');
                let taxPercent = parseFloat($line.data('tax_percent'));
                let taxId = $line.attr('id');
                let taxLineAmount = 0;
                if (itemizedValue[targetItem] && taxPercent) {
                    taxLineAmount = itemizedValue[targetItem] * (taxPercent / 100);
                    taxTotal += taxLineAmount;
                } else {
                }
                jQuery('span[data-target_tax=' + taxId + ']').html(formatPrice(taxLineAmount, formSettings.currency_settings));
            });
            return taxTotal;
        },
        calculateTabularTotal(form, itemizedValue, formSettings) {
            // check the
            let productTables = form.find('table.wpf_tabular_items');
            $.each(productTables, (index, productTable) => {
                let $productTable = $(productTable);
                let productId = $productTable.data('produt_id');
                // find the total product cost
                let productLines = $productTable.find('tbody tr');
                let tableTotal = 0;
                $.each(productLines, (index, productLine) => {
                    let price = $(productLine).find('input.wpf_tabular_price').data('price');
                    let qty = $(productLine).find('input.wpf_tabular_qty').val();
                    if (price && qty) {
                        tableTotal = tableTotal + (parseInt(price) * parseInt(qty));
                    }
                });
                form.find('span.wpf_calc_tabular_' + productId).html(formatPrice(tableTotal, formSettings.currency_settings));
                $productTable.attr('data-item_total', tableTotal);
                itemizedValue[productId] = tableTotal;
            });

            return itemizedValue;
        },

        initDatePiker() {
            let dateFields = $('.wpf_form input.wpf_date_field');
            if (dateFields.length) {
                flatpickr.localize(window.wp_payform_general.date_i18n);
                dateFields.each(function (index, dateField) {
                    let config = $(this).data('date_config');
                    flatpickr(dateField, config);
                });
            }
        },
        handlePaymentMethodChange(form, value) {
            form.data('selected_payment_method', value);
            if (!value) {
                form.find('.wpf_all_payment_methods_wrapper').hide();
                return;
            }
            form.find('.wpf_all_payment_methods_wrapper').show();
            form.find('.wpf_all_payment_methods_wrapper .wpf_payment_method_element').hide();
            form.find('.wpf_all_payment_methods_wrapper .wpf_payment_method_element_' + value).show();
        },
        maybeSubscriptionSetup(form) {

            // Handle Radio Button Select
            function checkForRadio(element) {
                let elementName = $(element).attr('name');
                let selectedIndex = $(element).val();
                let $wrapper = $(element).closest('.wpf_subscription_controls_radio');
                $wrapper.find('.wpf_subscription_plan_summary_item').hide();
                $wrapper.find('.wpf_subscription_plan_summary_'+elementName+' .wpf_subscription_plan_index_'+selectedIndex).show();

                $wrapper.find('.subscription_radio_custom').hide();
                $wrapper.find('.subscription_radio_custom_'+selectedIndex).show();
            }

            $.each(form.find('.wpf_subscription_controls_radio input:checked'), function (index, element) {
                checkForRadio(element);
            });

            form.find('.wpf_subscription_controls_radio input[type=radio]').on('change', function () {
                checkForRadio(this);
            });

            // Handle Selection Button Select

            function checkForSelections(element) {
                let elementName = $(element).attr('id');
                let selectedIndex = $(element).val();
                form.find('.wpf_subscription_plan_summary_'+elementName +' .wpf_subscription_plan_summary_item').hide();
                form.find('.wpf_subscription_plan_summary_'+elementName +' .wpf_subscription_plan_index_'+selectedIndex).show();
            }

            $.each(form.find('.wpf_subscrion_plans_select option:selected'), function (index, element) {
                if($(element).attr('value') != '') {
                    checkForSelections($(element).parent());
                }
            });

            form.find('.wpf_subscrion_plans_select select').on('change', function () {
                checkForSelections(this);
            });
        },
        maybeCustomSubscriptionItemSetup: function(form) {
            var that = this;
            let formSettings = window['wp_payform_' + form.data('wpf_form_id')];
            form.find('.wpf_custom_subscription_input').on('keyup', function () {
                var $el = $(this);
                var value = parseInt($el.val() * 100);
                var $hiddenEl = $el.parent().find('.wpf_payment_item');
                $hiddenEl.data('subscription_amount', value);

                var totalAmount = value + parseInt($el.data('initial_amount'));
                $hiddenEl.data('price', totalAmount);

                $el.closest('.wpf_form_group').find('.wpf_dynamic_input_amount').html(formatPrice(value, formSettings.currency_settings))
                $hiddenEl.trigger('change');
            });

            form.find('.wpf_custom_subscription_amount').on('change', function () {
                let $el = $(this);
                let index = $el.data('plan_index');
                let value = parseInt($el.val() * 100);
                let $parent = $el.closest('.wpf_multi_form_controls');
                $parent.find('.wpf_subscription_plan_summary')
                    .find('.wpf_subscription_plan_index_'+index)
                    .find('.wpf_dynamic_input_amount')
                    .html(formatPrice(value, formSettings.currency_settings));

                var $input = $parent.find('.wpf_payment_item').find('.wpf_option_custom_'+index);
                var totalAmount = value + parseInt($input.data('initial_amount'));
                $input
                    .data('subscription_amount', value)
                    .data('price', totalAmount);

                $parent.find('select').trigger('change');
            });

            form.find('.wpf_custom_subscription_amount_radio').on('change', function () {
                let $el = $(this);
                let index = $el.data('plan_index');
                let value = parseInt($el.val() * 100);
                let $parent = $el.closest('.wpf_multi_form_controls');
                $parent.find('.wpf_subscription_plan_summary')
                    .find('.wpf_subscription_plan_index_'+index)
                    .find('.wpf_dynamic_input_amount')
                    .html(formatPrice(value, formSettings.currency_settings));
                var $input = $parent.find('.wpf_option_custom_'+index);
                var totalAmount = value + parseInt($input.data('initial_amount'));
                $input
                    .data('subscription_amount', value)
                    .data('price', totalAmount);

                $parent.find('input[type=radio]').trigger('change');
            });

        }
    };

    $(document).ready(function ($) {
        wpPayformApp.init();
    });

}(jQuery));

window.wpfOnloadRecaptchaCallback = function () {
    jQuery(document).ready(function ($) {
        var $forms = $('.wpf_has_recaptcha');
        $.each($forms, (index, form) => {
            var $form = $(form);
            let formId = $form.attr('data-wpf_form_id');
            let key = $form.attr('data-recaptcha_site_key');
            var recaptchaVersion = $form.attr('data-recaptcha_version');
            if(recaptchaVersion == 'v2') {
                recaptchInstances['form_'+formId] = grecaptcha.render('wpf_recaptcha_'+formId, {
                    'sitekey' : key
                });
            } else {
                grecaptcha.execute(key, { action: 'payform/'+formId }).then(function(token) {
                    $form.find('#wpf_recaptcha_'+formId).html('<input type="hidden" name="g-recaptcha-response" value="'+token+'" />')
                });

                $form.on('form_server_always', function () {
                    grecaptcha.execute(key, { action: 'payform/'+formId }).then(function(token) {
                        $form.find('#wpf_recaptcha_'+formId).html('<input type="hidden" name="g-recaptcha-response" value="'+token+'" />')
                    });
                });
            }
        });
    });
}