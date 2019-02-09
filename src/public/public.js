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
        },
        initForm(form) {
            let that = this;
            this.calculatePayments(form);

            form.find('.wpf_payment_item, .wpf_item_qty').on('change', () => {
                this.calculatePayments(form);
            });

            let $cardElementDiv = $('.wpf_stripe_card_element');
            let cardEleementStyle = $cardElementDiv.data('checkout_style');

            if(cardEleementStyle == 'embeded_form' || cardEleementStyle == 'overlay_form') {
                let elementHandler = StripeElementHandler;
                elementHandler.init({
                    form: form,
                    element_id: $cardElementDiv.attr('id'),
                    style: false
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
                    form_settings: window['wp_payform_'+ form.data('wpf_form_id')]
                }
                StripeCheckoutHandler.init(checkoutSettings, function () {
                    that.submitForm(form);
                });
            } else {
                // No Card Found So, It's normal Form without payment processing
                form.on('submit', function (e) {
                    e.preventDefault();
                    that.submitForm(form);
                })
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
                })
                .always(() => {
                    form.parent().removeClass('wpf_form_has_errors');
                    form.removeClass('wpf_submitting_form');
                    form.find('button.wpf_submit_button').removeAttr('disabled');
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
                } else if(elementType == 'number') {
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
        }
    };

    $( document ).ready( function( $ ) {
        wpPayformApp.init();
    } );

}(jQuery));