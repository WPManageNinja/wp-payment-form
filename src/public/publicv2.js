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
            this.initOther();
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
        },
        initOther() {
            $('.wpf_form input').on('keypress', function (e) {
                return e.which !== 13;
            });
            let $inputs = $('.wpf_form').find('input[data-required="yes"][data-type="input"],textarea[data-required="yes"],select[data-required="yes"]');
            $inputs.on('keypress blur', function (e) {
                if ($(this).val()) {
                    $(this).removeClass('wpf_has_error');
                }
            });
        }
    };

    $(document).ready(function ($) {
        wpPayformApp.init();
    });

}(jQuery));

window.wpf_onload_recaptcha_callback = function () {
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