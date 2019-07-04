import StripeElementHandler from './StripeElementHandler';
import StripeCheckoutHandler from './StripeCheckoutHandler';
import formatPrice from '../common/formatPrice';
import Payment from './Payment';

(function ($, generalSettings) {

    const wpPayformApp = {
        forms: {},
        general: generalSettings,
        init() {
            const body = $(document.body);
            
            this.forms = body.find('.wpf_form');

            this.forms.each((i, form) => {
                let $form = $(form);
                this.initForm($form);
                body.trigger('wpPayFormProcessFormElements', [$form]);
            });

            this.initDatePiker();

            $('.wpf_form').on('keypress', e => e.which !== 13);
        },
        initForm(form) {
            this.calculatePayments(form);

            const payment = new Payment(form);

            if (payment.isMultiple()) {
                let paymentMethod = payment.getMultiplePayment();
                
                form.on('payment_method_changed', (e, paymentMethod) => {
                    payment.handlePaymentMethodChange(paymentMethod);
                })
                .trigger('payment_method_changed', paymentMethod)
                .find('input[name=__wpf_selected_payment_method]').on('change', (e) => {
                    form.trigger('payment_method_changed', e.target.value);
                });

            } else {
                let paymentMethod = payment.getSinglePayment();

                if (paymentMethod) {
                    form.data('selected_payment_method', paymentMethod);
                }
            }

            form.find('.wpf_payment_item, .wpf_item_qty, .wpf_tabular_qty').on('change', () => {
                this.calculatePayments(form);
            });

            let $cardElementDiv = form.find('.wpf_stripe_card_element');

            let cardEleementStyle = $cardElementDiv.data('checkout_style');
            
            form.on('submit', (e) => {
                e.preventDefault();
                
                let selectedPaymentMethod = payment.getSelectedMethod();

                if (selectedPaymentMethod == 'stripe') {
                    // We have the selected payment method! So, we are triggering that
                    form.trigger(selectedPaymentMethod + '_payment_submit');
                    // We have to do a promise based method because all payment methods does not have
                    // onpage checkout ansyc callbacks
                } else {
                    this.submitForm(form);
                }
            });

            let form_settings = window['wp_payform_' + form.data('wpf_form_id')];

            if (cardEleementStyle == 'embeded_form') {
                let cardElementId = $cardElementDiv.attr('id');
                let elementHandler = StripeElementHandler;
                
                elementHandler.init({
                    form: form,
                    elementId: cardElementId,
                    style: false,
                    pub_key: form_settings.stripe_pub_key
                }, () => this.submitForm(form));

            } else if (cardEleementStyle == 'stripe_checkout') {
                let checkoutSettings = {
                    form: form,
                    billing: $cardElementDiv.data('require_billing_info') == 'yes',
                    shipping: $cardElementDiv.data('require_shipping_info') == 'yes',
                    verify_zip: $cardElementDiv.data('verify_zip') == 'yes',
                    allowRememberMe: $cardElementDiv.data('allow_remember_me') == 'yes',
                    form_settings: form_settings,
                    pub_key: form_settings.stripe_pub_key
                };

                StripeCheckoutHandler.init(checkoutSettings, () => this.submitForm(form));
            }

            this.maybeSubscriptionSetup(form);

            $(document.body).trigger('wpfFormInitialized', [form]);
            $(document.body).trigger('wpfFormInitialized_' + form.data('wpf_form_id'), [form]);
            form.addClass('wpf_form_initialized');
        },
        submitForm(form) {
            let formId = form.data('wpf_form_id');
            form.find('button.wpf_submit_button').attr('disabled', true);
            form.addClass('wpf_submitting_form');
            form.parent().find('.wpf_form_notices').hide();
            form.trigger('wpf_form_submitting', formId);
            
            $.post(this.general.ajax_url, {
                action: 'wpf_submit_form',
                form_id: formId,
                payment_total: form.data('payment_total'),
                form_data: $(form).serialize()
            })
            .then(response => {
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
            })
            .always(() => {
                form.find('input[name=stripeToken]').remove();
            });
        },
        calculatePayments(form) {
            let elements = form.find('.wpf_payment_item');
            let itemTotalValue = {};

            elements.each(function (index, elem) {
                let elementType = elem.type;
                let $elem = $(elem);
                let elementName = $elem.attr('name');
                if (elementType == 'radio') {
                    let itemValue = form.find('input[name=' + elementName + ']:checked').data('price');
                    if (itemValue) {
                        itemTotalValue[elementName] = parseInt(itemValue, 10);
                    }
                }
                else if (elementType == 'hidden') {
                    let itemValue = $elem.data('price');
                    if (itemValue) {
                        itemTotalValue[elementName] = parseInt(itemValue, 10);
                    }
                } else if ($elem.data('is_custom_price') == 'yes') {
                    let itemValue = $(this).val();
                    if (itemValue) {
                        itemTotalValue[elementName] = parseInt((parseFloat(itemValue) * 100), 10);
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
                    let itemValue = form.find('select[name=' + elementName + '] option:selected').data('price');
                    if (itemValue) {
                        itemTotalValue[elementName] = parseInt(itemValue);
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
                            let lineTotal = Math.abs(parseInt(qty, 10)) * itemValue;
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
                    console.log(lineItem);
                    console.log(itemizedValue);
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
                console.log(productLines);
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
                flatpickr.localize(this.general.date_i18n);
                dateFields.each(function (index, dateField) {
                    flatpickr(dateField, $(this).data('date_config'));
                });
            }
        },
        maybeSubscriptionSetup(form) {
            // Handle Radio Button Select
            function checkForRadio(element) {
                let elementName = $(element).attr('name');
                let selectedIndex = $(element).val();
                $(element).closest('.wpf_subscription_controls_radio').find('.wpf_subscription_plan_summary_item').hide();
                $(element).closest('.wpf_subscription_controls_radio').find('.wpf_subscription_plan_summary_'+elementName+' .wpf_subscription_plan_index_'+selectedIndex).show();
            }

            $.each(form.find('.wpf_subscription_controls_radio input:checked'), function (index, element) {
                checkForRadio(element);
            });

            form.find('.wpf_subscription_controls_radio input').on('change', function () {
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


        }
    };

    $(document).ready(function ($) {
        wpPayformApp.init();
    });

})(jQuery, wp_payform_general);