import StripeElementHandler from './StripeElementHandler';
import StripeCheckoutHandler from './StripeCheckoutHandler';
import PayFormHandler from './FormHandler';

var wpPayformApp = {};
window.recaptchInstances = {};

(function ($) {
    wpPayformApp = {
        forms: {},
        general: window.wp_payform_general,
        formData: {},
        init() {
            let body = $(document.body);
            this.forms = body.find('.wpf_form');
            this.forms.each(function () {
                let form = $(this);
                let formId = form.data('wpf_form_id');
                let formSettings = window['wp_payform_' + formId];

                let formHandler = new PayFormHandler(form, formSettings);
                formHandler.initForm();
                body.trigger('wpPayFormProcessFormElements', [form, formSettings]);
                body.trigger('wp_payform_inited_'+formId, [form, formSettings]);
            });

            this.initDatePiker();
            this.initLightBox();
            $('.wpf_form input').on('keypress', function (e) {
                return e.which !== 13;
            });

            let $inputs = $('.wpf_form').find('input[data-required="yes"][data-type="input"],textarea[data-required="yes"],select[data-required="yes"]');

            $inputs.on('keypress blur', function (e) {
                if ($(this).val()) {
                    $(this).removeClass('wpf_has_error');
                }
            });
        },


        handleStripePayment(form) {
            return new Promise(function (resolve, reject) {
                let selectedPaymentMethid = form.data('selected_payment_method');
                if (selectedPaymentMethid != 'stripe') {
                    resolve(true);
                }
                let checkoutType = 'embeded_form';

                console.log(checkoutType);


            });
        },

        initForm(form) {
            let that = this;
            let form_settings = window['wp_payform_' + form.data('wpf_form_id')];


            form.on('submit', (e) => {
                form.parent().find('.wpf_form_errors').html('').hide();
                e.preventDefault();
                const instance = this.validateData(form)
                    .then(response => {
                        return this.validateRecaptcha(form);
                    })
                    .then((response) => {
                        return this.handleStripePayment(form);
                    })
                    .then(response => {
                        this.submitForm(form);
                    })
                    .catch((error) => {
                        console.error(error);
                    });
            });


            let cardEleementStyle = 'embeded_form';
            let $cardElementDiv = form.find('.wpf_stripe_card_element');
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


            return;
            form.on('submit', function (e) {
                e.preventDefault();

                // Version 2 verfication
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
                    if (form.attr('data-recaptcha_version') == 'v2') {
                        let recaptchInstance = recaptchInstances['form_' + form.data('wpf_form_id')];
                        if (recaptchInstance != undefined) {
                            grecaptcha.reset(recaptchInstance)
                        }
                    }
                    form.trigger('form_server_always',);
                });
        },


        initLightBox() {
            if ($('.wpf_form .wpf_lightbox').length) {
                $('.wpf_form .wpf_lightbox').on('click', lity);
            }
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
        }
    };



    $(document).ready(function ($) {
        wpPayformApp.init();
    });

}(jQuery));

window.wpf_onload_recaptcha_callback = function () {
    console.log('fine');
    jQuery(document).ready(function ($) {
        var $forms = $('.wpf_has_recaptcha');
        $.each($forms, (index, form) => {
            var $form = $(form);
            let formId = $form.attr('data-wpf_form_id');
            let key = $form.attr('data-recaptcha_site_key');
            var recaptchaVersion = $form.attr('data-recaptcha_version');
            if (recaptchaVersion == 'v2') {
                let recaptchaIstanceId = grecaptcha.render('wpf_recaptcha_' + formId, {
                    'sitekey': key
                });
                recaptchInstances['form_' + formId] = recaptchaIstanceId;
                $form.on('refresh_recaptcha', function () {
                    grecaptcha.reset(recaptchaIstanceId);
                });
            } else {
                grecaptcha.execute(key, { action: 'payform/' + formId }).then(function (token) {
                    $form.find('#wpf_recaptcha_' + formId).html('<input type="hidden" name="g-recaptcha-response" value="' + token + '" />')
                });
                $form.on('refresh_recaptcha', function () {
                    grecaptcha.execute(key, {action: 'payform/' + formId}).then(function (token) {
                        $form.find('#wpf_recaptcha_' + formId).html('<input type="hidden" name="g-recaptcha-response" value="' + token + '" />')
                    });
                });
            }
        });
    });
}