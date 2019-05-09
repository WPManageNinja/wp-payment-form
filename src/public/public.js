import StripeElementHandler from './StripeElementHandler';
import StripeCheckoutHandler from './StripeCheckoutHandler';
import formatPrice from '../common/formatPrice';

var wpPayformApp = {};
(function( $ ) {
    wpPayformApp = {
        forms: {},
        general: window.wp_payform_general,
        formData: {},
        init() {
            let body = $(document.body);
            this.forms = body.find('.wpf_form');
            this.forms.each( function() {
                var form = $( this );
                wpPayformApp.initForm( form );
                body.trigger( 'wpPayFormProcessFormElements', [ form ] );
            });
            this.initDatePiker();
            this.initNumericInputs();
        },
        initForm(form) {
            let that = this;
            let form_settings = window['wp_payform_'+ form.data('wpf_form_id')];
            this.calculatePayments(form);

            if( parseInt(form.find('input[name=__wpf_valid_payment_methods_count]').val()) > 1 ) {
                let defaultSelected = form.find('input[name=__wpf_selected_payment_method]:checked').val();
                that.handlePaymentMethodChange(form, defaultSelected);
                form.find('input[name=__wpf_selected_payment_method]').on('change',function () {
                    form.trigger('payment_method_changed', $(this).val());
                });
                form.on('payment_method_changed', (event, value) => {
                    that.handlePaymentMethodChange(form, value);
                });
            } else {
                // We have to check if any hidden / single payment method exists or not
                let paymentMethod = form.find('[data-wpf_payment_method]').data('wpf_payment_method');
                if(paymentMethod) {
                    form.data('selected_payment_method', paymentMethod);
                }
            }

            form.find('.wpf_payment_item, .wpf_item_qty').on('change', () => {
                this.calculatePayments(form);
            });

            let $cardElementDiv = $('.wpf_stripe_card_element');
            let cardEleementStyle = $cardElementDiv.data('checkout_style');
            form.on('submit', function (e) {
                e.preventDefault();
                let selectedPaymentMethod = form.data('selected_payment_method');
                if(selectedPaymentMethod == 'stripe') {
                    // we have the selected payment method! So, we are triggering that
                    form.trigger(selectedPaymentMethod+'_payment_submit');
                    // We have to do a promise based method because all payment methods does not have
                    // onpage checkout anc callbacks
                } else {
                    that.submitForm(form);
                }
            });

            if(cardEleementStyle == 'embeded_form') {
                let elementHandler = StripeElementHandler;
                elementHandler.init({
                    form: form,
                    element_id: $cardElementDiv.attr('id'),
                    style: false,
                    pub_key: form_settings.stripe_pub_key
                }, function () {
                    that.submitForm(form);
                });
            } else if(cardEleementStyle == 'stripe_checkout') {
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
                    let confirmation = response.data.confirmation;
                    form.parent().addClass('wpf_form_submitted');
                    form.trigger('wpf_form_submitted', response.data);
                    if(confirmation.redirectTo == 'samePage') {
                        form.removeClass('wpf_submitting_form');
                        form.find('button.wpf_submit_button').removeAttr('disabled');
                        form.parent().removeClass('wpf_form_has_errors');

                        form.parent().find('.wpf_form_success').html(confirmation.messageToShow).show();
                        if(confirmation.samePageFormBehavior == 'hide_form') {
                            form.hide();
                            $([document.documentElement, document.body]).animate({
                                scrollTop: form.parent().find('.wpf_form_success').offset().top - 100
                            }, 200);
                        }
                        $('#wpf_form_id_'+formId)[0].reset();
                        form.trigger('stripe_clear');
                    } else if(confirmation.redirectTo == 'customUrl') {
                        if(confirmation.messageToShow) {
                            form.parent().find('.wpf_form_success').html(confirmation.messageToShow).show();
                        }
                        window.location.href = confirmation.customUrl;
                        return false;
                    }
                })
                .fail(error => {
                    let $errorDiv = form.parent().find('.wpf_form_errors');
                    $errorDiv.html('<p class="wpf_form_error_heading">'+error.responseJSON.data.message+'</p>').show();
                    $errorDiv.append('<ul class="wpf_error_items">');
                    $.each(error.responseJSON.data.errors, (errorId, errorText) => {
                        $errorDiv.append('<li class="error_item_'+errorId+'">'+errorText+'</li>');
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
                })
        },
        calculatePayments(form) {
            let elements = form.find('.wpf_payment_item');
            let itemTotalValue = {};
            elements.each(function (index, elem) {
                let elementType = elem.type;
                let elementName = $(elem).attr('name');
                if(elementType == 'radio') {
                    let itemValue = form.find('input[name='+elementName+']:checked').data('price');
                    if(itemValue) {
                        itemTotalValue[elementName] = parseInt(itemValue);
                    }
                }
                else if(elementType == 'hidden') {
                    let itemValue = $(elem).data('price');
                    if(itemValue) {
                        itemTotalValue[elementName] = parseInt(itemValue);
                    }
                } else if($(elem).data('is_custom_price') == 'yes') {
                    let itemValue = $(this).val();
                    if(itemValue) {
                        itemTotalValue[elementName] =  parseInt(parseFloat(itemValue) * 100);
                    }
                } else if(elementType == 'checkbox') {
                    let groupId = $(elem).data('group_id');
                    let groups = form.find('input[data-group_id="'+groupId+'"]:checked');
                    let groupTotal = 0;
                    groups.each((index, group) => {
                        let itemPrice = $(group).data('price');
                        if(itemPrice) {
                            groupTotal += parseInt(itemPrice);
                        }
                    });
                    itemTotalValue[groupId] = groupTotal;
                }
                else if(elementType == 'select-one') {
                    let itemValue = form.find('select[name='+elementName+'] option:selected').data('price');
                    if(itemValue) {
                        itemTotalValue[elementName] = parseInt(itemValue);
                    }
                }
            });
            let formSettings = window['wp_payform_'+form.data('wpf_form_id')];
            let allTotalAmount = 0;
            // Get The Total Now
            jQuery.each(itemTotalValue, (itemName, itemValue) => {
                if(itemValue) {
                    // check if there has a quantity
                    let targetQuantity = form.find('.wpf_item_qty[data-target_product='+itemName+']');
                    if(targetQuantity.length) {
                        let qty = $(targetQuantity).val();
                        if(parseInt(qty)) {
                            allTotalAmount +=  Math.abs(parseInt(qty)) * itemValue;
                        }
                    } else {
                        allTotalAmount += itemValue;
                    }
                }
            });
            if(allTotalAmount) {
                form.find('.wpf_calc_payment_total').html(formatPrice(allTotalAmount, formSettings.currency_settings));
            } else {
                form.find('.wpf_calc_payment_total').html(formatPrice(0, formSettings.currency_settings));
            }
            form.data('payment_total', allTotalAmount);
        },
        initDatePiker() {
            let dateFields = $('.wpf_form input.wpf_date_field');
            if(dateFields.length) {
                dateFields.each(function (index, dateField) {
                    new Pikaday({
                        field: dateField,
                        format: $(this).data('date_format'),
                        i18n: window.wp_payform_general.date_i18n
                    });
                });

            }
        },
        initNumericInputs() {

        },
        handlePaymentMethodChange(form, value) {
            form.data('selected_payment_method', value);
            if(!value) {
                form.find('.wpf_all_payment_methods_wrapper').hide();
                return;
            }
            form.find('.wpf_all_payment_methods_wrapper').show();
            form.find('.wpf_all_payment_methods_wrapper .wpf_payment_method_element').hide();
            form.find('.wpf_all_payment_methods_wrapper .wpf_payment_method_element_'+value).show();
        }
    };

    $( document ).ready( function( $ ) {
        wpPayformApp.init();
    } );

}(jQuery));