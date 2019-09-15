import formatPrice from "../common/formatPrice";
import isEmpty from 'lodash/isEmpty';

class PayFormHandler {
    constructor(form, config) {
        this.form = form;
        this.config = config;
        this.formId = config.form_id;
        this.paymentMethod = '';
        this.generalConfig = window.wp_payform_general;
        this.$formNoticeWrapper = form.parent().find('.wpf_form_notices');
    }

    $t(stringKey) {
        if(this.generalConfig.i18n[stringKey]) {
            return this.generalConfig.i18n[stringKey];
        }
        return '';
    }

    initForm() {
        // Init Calculate Payments and on change re-calculate
        this.calculatePayments();
        this.form.find('.wpf_payment_item, .wpf_item_qty, .wpf_tabular_qty').on('change', () => {
            this.calculatePayments();
        });
        this.initPaymentMethodChange();

        if (this.config.stripe_checkout_style == 'embeded_form') {
            this.paymentMethod = 'stripe';
            this.stripe = Stripe(this.config.stripe_pub_key);
            this.initStripeElement();
        } else if (this.config.stripe_checkout_style == 'stripe_checkout') {
            this.paymentMethod = 'stripe';
            this.stripe = Stripe(this.config.stripe_pub_key);
        }

        this.form.on('submit', (e) => {
            e.preventDefault();
            this.handleFormSubmit();
        });

        // Subscription Setup
        this.maybeSubscriptionSetup();
        this.maybeCustomSubscriptionItemSetup();
    }

    handleFormSubmit() {
        this.resetMessages();
        const instance =
            this.validateData() // step 1
                .then(response => {
                    return this.validateRecaptcha();
                }) // step 2
                .then((response) => {
                    return this.stripeElementPaymentToken();
                }) // step 3
                .then(response => {
                    // Before send ajax request
                    this.buttonState('submitting_form', 'Submitting Data...', true, response);
                    jQuery.post(this.generalConfig.ajax_url, {
                        action: 'wpf_submit_form',
                        form_id: this.formId,
                        payment_total: this.form.data('payment_total'),
                        form_data: jQuery(this.form).serialize()
                    })
                        .then(response => {
                            if (!response || !response.data || typeof response == 'string') {
                                this.handleServerUnexpectedError(response);
                                return;
                            }

                            if (response.data.call_next_method) {
                                this[response.data.call_next_method](response.data);
                                return;
                            }
                            this.handlePaymentSuccess(response.data);
                        })
                        .fail(error => {
                            if (typeof error == 'string' || !error.responseJSON.data) {
                                this.handleServerUnexpectedError(response);
                            } else {
                                this.showMessages(error.responseJSON.data.errors, 'error', error.responseJSON.data.message);
                            }
                            this.form.trigger('wpf_form_fail_submission', error.responseJSON.data);
                            this.form.trigger('server_error', [error]);
                            this.form.removeClass('wpf_submitting_form');
                            this.form.find('button.wpf_submit_button').removeAttr('disabled');
                            this.buttonState('normal', '', false);
                        })
                        .always((response) => {
                            if(response && response.responseJSON && response.responseJSON.data && response.responseJSON.data.form_events) {
                                this.fireFormEvents(response.responseJSON.data.form_events, response.responseJSON.data);
                            }
                        });
                })
                .catch((error) => {
                    this.buttonState('state_normal', '', false, error);
                    console.error(error);
                });
    }

    // This is for mainly subscription payment
    async stripeSetupItent(data) {
        this.showMessages(data.message, 'info', '');
        this.buttonState('validating_form', 'Validating Card Info', true, data);
        const { setupIntent, errorAction } = await this.stripe.handleCardSetup(
            data.client_secret, this.stripeCard,
            {
                payment_method_data: {
                    billing_details: {
                        name: data.customer_name,
                        email: data.customer_email,
                    }
                }
            }
        );

        if (errorAction) {
            this.showMessages(errorAction.message, 'error', '');
            this.buttonState('sca_declined', '', false, errorAction);
            return;
        }

        this.handleStripePaymentConfirm({
            action: 'wppayform_sca_inline_confirm_payment_setup_intents',
            form_id: this.formId,
            payment_method: setupIntent.payment_method,
            payemnt_method_id: data.payemnt_method_id,
            payment_intent_id: setupIntent.id,
            submission_id: data.submission_id,
            type: 'handleCardSetup'
        });

    }

    // This is for one time payment
    async initStripeSCAModal(data) {

        this.buttonState('authenticating_sca', 'Authenticating Payment', true, data);

        this.showMessages(data.message, 'info', '');

        const {
            error: errorAction,
            paymentIntent
        } = await this.stripe.handleCardAction(
            data.stripe_payment_intent_client_secret
        );

        if (errorAction) {
            this.showMessages(errorAction.message, 'error', '');
            this.buttonState('sca_declined', '', false, errorAction);
            return;
        }

        this.handleStripePaymentConfirm({
            action: 'wppayform_sca_inline_confirm_payment',
            form_id: this.formId,
            payment_method: paymentIntent.payment_method,
            payment_intent_id: paymentIntent.id,
            submission_id: data.submission_id,
            type: 'handleCardAction'
        });
    }

    handleStripePaymentConfirm(data) {

        this.showMessages('Confirming Payment. Please wait a little bit...', 'info')
        this.buttonState('confirming_payment', 'Confirming Payment', true, data);

        jQuery.post(this.generalConfig.ajax_url, data)
            .then(response => {
                if (!response || !response.data || typeof response == 'string') {
                    this.handleServerUnexpectedError(response);
                    return;
                }
                this.handlePaymentSuccess(response.data);
            })
            .fail(error => {
                if (typeof error == 'string' || !error.responseJSON.data) {
                    this.handleServerUnexpectedError(response);
                } else {
                    this.showMessages(error.responseJSON.data.errors, 'error', error.responseJSON.data.message);
                }

                this.buttonState('payment_failed', '', false, error);
            })
            .always(() => {

            });
    }

    handlePaymentSuccess(data) {
        let confirmation = data.confirmation;
        this.form.parent().addClass('wpf_form_submitted');
        this.form.trigger('wpf_form_submitted', data);
        this.form.trigger('wpf_form_sucess', data);

        this.form.removeClass('wpf_submitting_form');
        this.form.find('button.wpf_submit_button').removeAttr('disabled');
        this.form.removeClass('wpf_submitting_form');

        if (confirmation.redirectTo == 'samePage') {
            this.form.removeClass('wpf_submitting_form');
            this.form.find('button.wpf_submit_button').removeAttr('disabled');
            this.form.parent().removeClass('wpf_form_has_errors');

            this.showMessages(confirmation.messageToShow, 'success');
            if (confirmation.samePageFormBehavior == 'hide_form') {
                this.form.hide();
                jQuery([document.documentElement, document.body]).animate({
                    scrollTop: this.$formNoticeWrapper.offset().top - 100
                }, 200);
            }
            jQuery('#wpf_form_id_' + this.formId)[0].reset();
            this.form.trigger('stripe_clear');
        } else if (confirmation.redirectTo == 'customUrl') {
            if (confirmation.messageToShow) {
                this.showMessages(confirmation.messageToShow, 'success');
            }
            window.location.href = confirmation.customUrl;
            return false;
        }
    }

    // Step 1
    validateData($range) {
        var that = this;
        if(!$range) {
            $range = this.form;
        }
        return new Promise(function(resolve, reject) {
            function getLabel($input) {
                let errorLabel = $input.closest('.wpf_form_group').find('label').text();
                if(!errorLabel) {
                    let placeholder = $input.attr('placeholder');
                    if(placeholder) {
                        errorLabel = placeholder;
                    } else {
                        errorLabel = errorId;
                    }
                }
                return errorLabel;
            }
            let errors = {};
            // Validate the normal inputs
            let $inputTypes = $range.find('input[data-required="yes"][data-type="input"],textarea[data-required="yes"],select[data-required="yes"]');
            if($inputTypes.length) {
                jQuery.each($inputTypes, (index, inputType) => {
                    let $input = jQuery(inputType);
                    if(!$input.val()) {
                        $input.addClass('wpf_has_error');
                        let errorId = $input.attr('name');
                        let label = getLabel($input);
                        errors[errorId] = label +' '+that.$t('is_required');
                    }
                });
            }

            // Validate File Upload
            let $fileUploads = $range.find('input[data-required="yes"][data-type="file_upload"]');
            if($fileUploads.length) {
                jQuery.each($fileUploads, (index, fileUpload) => {
                    let $upload = jQuery(fileUpload);
                    let associateKey = $upload.val();
                    // check if that associate key has any value or not
                    if(!$range.find('input[name^='+associateKey+']').val()) {
                        let errorId = $upload.attr('name');
                        let label = getLabel($upload);
                        errors[errorId] = label +' '+that.$t('is_required');
                    }
                });
            }

            // Validate radio items
            let $radioFields = $range.find('div[data-required="yes"][data-element_type="radio"],div[data-required="yes"][data-required_element="radio"]');

            if($radioFields.length) {
                jQuery.each($radioFields, (index, radioField) => {
                    let $radioField = jQuery(radioField);
                    if(!$radioField.find('input').is(':checked')) {
                        let errorId = $radioField.attr('data-target_element');
                        let errorLabel = $radioField.find('.wpf_input_label label').text();
                        if(!errorLabel) {
                            errorLabel = errorId;
                        }
                        errors[errorId] = errorLabel+ ' '+ that.$t('is_required');
                    }
                });
            }

            // Validate select items
            let $sectionFields = $range.find('div[data-checkbox_required="yes"][data-element_type="checkbox"]');
            if($sectionFields.length) {
                jQuery.each($sectionFields, (index, selectField) => {
                    let $selectField = jQuery(selectField);
                    if(!$selectField.find('input').is(':checked')) {
                        let errorId = $selectField.attr('data-target_element');
                        let errorLabel = $selectField.find('.wpf_input_label label').text();
                        if(!errorLabel) {
                            errorLabel = errorId;
                        }
                        errors[errorId] = errorLabel+ ' '+ that.$t('is_required');
                    }
                });
            }

            if(!isEmpty(errors)) {
                that.showMessages(errors, 'error', that.$t('validation_failed'));
                reject('validation_failed');
                return;
            }
            resolve(true);
        });
    }

    // Step 2
    validateRecaptcha() {
        var that = this;
        return new Promise(function (resolve, reject) {
            if (that.form.attr('data-recaptcha_version') == 'v2') {
                let recaptchInstance = recaptchInstances['form_' + that.formId];
                if (recaptchInstance != undefined) {
                    let response = grecaptcha.getResponse(recaptchInstance);
                    if (!response) {
                        that.showMessages('', 'error', that.$t('verify_recapthca'));
                        reject('recaptca validation error');
                    } else {
                        resolve(response);
                    }
                }
            } else {
                resolve(true);
            }
        });
    }

    // Step 3
    stripeElementPaymentToken() {
        var that = this;
        return new Promise(function (resolve, reject) {

            if(that.paymentMethod != 'stripe') {
                resolve(true);
                return;
            }

            if (!that.stripeCard) {
                resolve(true);
                return;
            }

            that.buttonState('validating_form', 'Validating...', true);

            that.stripe.createPaymentMethod(
                'card',
                that.stripeCard
            ).then((result) => {
                if (result.error) {
                    reject(result.error.message);
                    return;
                }
                let paymentMethodId = result.paymentMethod.id;
                // Append the Payment Method ID to Form
                that.form.find('input[name=__stripe_payment_method_id]').remove();
                var hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', '__stripe_payment_method_id');
                hiddenInput.setAttribute('value', paymentMethodId);
                that.form.append(hiddenInput);
                resolve(true);
                return;
            });
        });
    }

    // step 3.1 - If It's Stripe Hosted Checkout Option
    stripeRedirectToCheckout(data) {
        this.showMessages(data.message, 'success');
        this.stripe.redirectToCheckout({
            sessionId: data.session_id
        }).then((result) => {

        });
    }

    // Subscription Item Handlers
    maybeSubscriptionSetup() {
        let form = this.form;

        // Handle Radio Button Select
        function checkForRadio(element) {
            let elementName = jQuery(element).attr('name');
            let selectedIndex = jQuery(element).val();
            let $wrapper = jQuery(element).closest('.wpf_subscription_controls_radio');
            $wrapper.find('.wpf_subscription_plan_summary_item').hide();
            $wrapper.find('.wpf_subscription_plan_summary_' + elementName + ' .wpf_subscription_plan_index_' + selectedIndex).show();

            $wrapper.find('.subscription_radio_custom').hide();
            $wrapper.find('.subscription_radio_custom_' + selectedIndex).show();
        }

        jQuery.each(form.find('.wpf_subscription_controls_radio input:checked'), function (index, element) {
            checkForRadio(element);
        });

        this.form.find('.wpf_subscription_controls_radio input[type=radio]').on('change', function () {
            checkForRadio(this);
        });

        // Handle Selection Button Select
        function checkForSelections(element) {
            let elementName = jQuery(element).attr('id');
            let selectedIndex = jQuery(element).val();
            form.find('.wpf_subscription_plan_summary_' + elementName + ' .wpf_subscription_plan_summary_item').hide();
            form.find('.wpf_subscription_plan_summary_' + elementName + ' .wpf_subscription_plan_index_' + selectedIndex).show();
        }

        jQuery.each(this.form.find('.wpf_subscrion_plans_select option:selected'), function (index, element) {
            if (jQuery(element).attr('value') != '') {
                checkForSelections(jQuery(element).parent());
            }
        });

        form.find('.wpf_subscrion_plans_select select').on('change', function () {
            checkForSelections(this);
        });
    }

    maybeCustomSubscriptionItemSetup() {
        var that = this;
        this.form.find('.wpf_custom_subscription_input').on('keyup', function () {
            var $el = jQuery(this);
            var value = parseInt($el.val() * 100);
            var $hiddenEl = $el.parent().find('.wpf_payment_item');
            $hiddenEl.data('subscription_amount', value);

            var totalAmount = value + parseInt($el.data('initial_amount'));
            $hiddenEl.data('price', totalAmount);

            $el.closest('.wpf_form_group').find('.wpf_dynamic_input_amount').html(that.getFormattedPrice(value))
            $hiddenEl.trigger('change');
        });

        this.form.find('.wpf_custom_subscription_amount').on('change', function () {
            let $el = jQuery(this);
            let index = $el.data('plan_index');
            let value = parseInt($el.val() * 100);
            let $parent = $el.closest('.wpf_multi_form_controls');
            $parent.find('.wpf_subscription_plan_summary')
                .find('.wpf_subscription_plan_index_' + index)
                .find('.wpf_dynamic_input_amount')
                .html(that.getFormattedPrice(value));

            var $input = $parent.find('.wpf_payment_item').find('.wpf_option_custom_' + index);
            var totalAmount = value + parseInt($input.data('initial_amount'));
            $input
                .data('subscription_amount', value)
                .data('price', totalAmount);

            $parent.find('select').trigger('change');
        });

        this.form.find('.wpf_custom_subscription_amount_radio').on('change', function () {
            let $el = jQuery(this);
            let index = $el.data('plan_index');
            let value = parseInt($el.val() * 100);
            let $parent = $el.closest('.wpf_multi_form_controls');
            $parent.find('.wpf_subscription_plan_summary')
                .find('.wpf_subscription_plan_index_' + index)
                .find('.wpf_dynamic_input_amount')
                .html(that.getFormattedPrice(value));
            var $input = $parent.find('.wpf_option_custom_' + index);
            var totalAmount = value + parseInt($input.data('initial_amount'));
            $input
                .data('subscription_amount', value)
                .data('price', totalAmount);

            $parent.find('input[type=radio]').trigger('change');
        });

    }

    // Payment Calucations
    calculatePayments() {
        let form = this.form;
        let elements = form.find('.wpf_payment_item');
        let itemTotalValue = {};

        let subscriptonAmountTotal = 0;

        elements.each(function (index, elem) {
            let elementType = elem.type;
            let $elem = jQuery(elem);
            let elementName = $elem.attr('name');
            if (elementType == 'radio') {
                let $element = form.find('input[name=' + elementName + ']:checked');
                let itemValue = $element.data('price');
                if (itemValue) {
                    itemTotalValue[elementName] = parseInt(itemValue);
                }
                if ($element.data['subscription_amount']) {
                    subscriptonAmountTotal += parseInt($element.data['subscription_amount']);
                }
            }
            else if (elementType == 'hidden') {
                let itemValue = $elem.data('price');
                if (itemValue) {
                    itemTotalValue[elementName] = parseInt(itemValue);
                }
                if ($elem.attr('data-subscription_amount')) {
                    subscriptonAmountTotal += parseInt($elem.attr('data-subscription_amount'));
                }
            } else if ($elem.data('is_custom_price') == 'yes') {
                let itemValue = jQuery(this).val();
                if (itemValue) {
                    itemTotalValue[elementName] = parseInt(parseFloat(itemValue) * 100);
                }
            } else if (elementType == 'checkbox') {
                let groupId = $elem.data('group_id');
                let groups = form.find('input[data-group_id="' + groupId + '"]:checked');
                let groupTotal = 0;
                groups.each((index, group) => {
                    let itemPrice = jQuery(group).data('price');
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

                if ($element.attr('data-subscription_amount')) {
                    subscriptonAmountTotal += parseInt($element.attr('data-subscription_amount'));
                }
            }
        });
        let allTotalAmount = 0;
        itemTotalValue = this.calculateTabularTotal(itemTotalValue);
        // Get The Total Now
        jQuery.each(itemTotalValue, (itemName, itemValue) => {
            if (itemValue) {
                // check if there has a quantity
                let targetQuantity = form.find('.wpf_item_qty[data-target_product=' + itemName + ']');
                if (targetQuantity.length) {
                    let qty = jQuery(targetQuantity).val();
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
        let taxAmount = this.calCulateTaxAmount(itemTotalValue);
        if (taxAmount) {
            allTotalAmount += taxAmount;
        }

        form.find('.wpf_calc_tax_total').html(this.getFormattedPrice(taxAmount));
        form.find('.wpf_calc_sub_total').html(this.getFormattedPrice(subTotal));
        form.find('.wpf_calc_payment_total').html(this.getFormattedPrice(allTotalAmount));
        form.data('payment_total', allTotalAmount);
        form.data('subscription_total', subscriptonAmountTotal);
    }

    calCulateTaxAmount(itemizedValue) {
        let form = this.form;
        if (!form.hasClass('wpf_has_tax_item')) {
            return 0;
        }
        let taxLines = form.find('label.wpf_tax_line_item');
        let taxTotal = 0;
        jQuery.each(taxLines, (index, lineItem) => {
            let $line = jQuery(lineItem);
            let targetItem = $line.data('target_product');
            let taxPercent = parseFloat($line.data('tax_percent'));
            let taxId = $line.attr('id');
            let taxLineAmount = 0;
            if (itemizedValue[targetItem] && taxPercent) {
                taxLineAmount = itemizedValue[targetItem] * (taxPercent / 100);
                taxTotal += taxLineAmount;
            } else {
            }
            jQuery('span[data-target_tax=' + taxId + ']').html(this.getFormattedPrice(taxLineAmount));
        });
        return taxTotal;
    }

    calculateTabularTotal(itemizedValue) {
        let form = this.form;
        // check the
        let productTables = form.find('table.wpf_tabular_items');
        jQuery.each(productTables, (index, productTable) => {
            let $productTable = jQuery(productTable);
            let productId = $productTable.data('produt_id');
            // find the total product cost
            let productLines = $productTable.find('tbody tr');
            let tableTotal = 0;
            jQuery.each(productLines, (index, productLine) => {
                let price = jQuery(productLine).find('input.wpf_tabular_price').data('price');
                let qty = jQuery(productLine).find('input.wpf_tabular_qty').val();
                if (price && qty) {
                    tableTotal = tableTotal + (parseInt(price) * parseInt(qty));
                }
            });
            form.find('span.wpf_calc_tabular_' + productId).html(this.getFormattedPrice(tableTotal));
            $productTable.attr('data-item_total', tableTotal);
            itemizedValue[productId] = tableTotal;
        });

        return itemizedValue;
    }

    getFormattedPrice(amount) {
        return formatPrice(amount, this.config.currency_settings)
    }

    // Payment Method change Detector
    initPaymentMethodChange() {
        let form = this.form;
        if (parseInt(this.form.find('input[name=__wpf_valid_payment_methods_count]').val()) > 1) {
            let defaultSelected = this.form.find('input[name=__wpf_selected_payment_method]:checked').val();
            this.handlePaymentMethodChange(defaultSelected);
            form.find('input[name=__wpf_selected_payment_method]').on('change', function () {
                form.trigger('payment_method_changed', jQuery(this).val());
            });
            form.on('payment_method_changed', (event, value) => {
                this.handlePaymentMethodChange(value);
            });
        } else {
            // We have to check if any hidden / single payment method exists or not
            let paymentMethod = this.form.find('[data-wpf_payment_method]').data('wpf_payment_method');
            if (paymentMethod) {
                this.form.data('selected_payment_method', paymentMethod);
            }
        }
    }

    handlePaymentMethodChange(paymentMethod) {
        this.form.data('selected_payment_method', paymentMethod);
        if (!paymentMethod) {
            this.paymentMethod = '';
            this.form.find('.wpf_all_payment_methods_wrapper').hide();
            return;
        }
        this.paymentMethod = paymentMethod;
        this.form.find('.wpf_all_payment_methods_wrapper').show();
        this.form.find('.wpf_all_payment_methods_wrapper .wpf_payment_method_element').hide();
        this.form.find('.wpf_all_payment_methods_wrapper .wpf_payment_method_element_' + paymentMethod).show();
    }

    // Stripe Element Handler
    initStripeElement() {
        let elements = this.stripe.elements();
        var style = {
            base: {
                color: "#32325d",
                fontFamily: "-apple-system, BlinkMacSystemFont, sans-serif",
                fontSmoothing: "antialiased",
                fontSize: "16px",
                "::placeholder": {
                    color: "#aab7c4"
                }
            },
            invalid: {
                color: "#fa755a",
                iconColor: "#fa755a"
            }
        };

        var card = elements.create("card", {
            style: style,
            hidePostalCode: this.config.stripe_verify_zip != 'yes'
        });
        // Add an instance of the card Element into the `card-element` <div>.
        card.mount("#" + this.config.stripe_element_id);

        card.addEventListener('change', (event) => {
            if (event.error) {
                this.form.find('.wpf_card-errors').html(event.error.message);
            } else {
                this.form.find('.wpf_card-errors').html('');
            }
        });

        this.stripeCard = card;

        this.form.on('stripe_clear', () => {
            card.clear();
        });
    }

    // Error and Success Messages
    handleServerUnexpectedError(response) {
        this.showMessages(response, 'error', this.$t('submission_error'));
        this.buttonState('normal', '', false);
    }

    showMessages(messages, type, heading) {
        this.resetMessages();

        let html = '';
        if (heading) {
            html += '<p class="wpf_alert_heading">' + heading + '</p>';
        }

        if (typeof messages == 'string' && messages) {
            html += messages;
        }

        if (typeof messages == 'object' && messages) {
            html += '<ul class="wpf_alert_ietms">';
            jQuery.each(messages, (index, message) => {
                html += '<li>' + message + '</li>';
            });
            html += '</ul>';
        }
        if (html) {
            this.$formNoticeWrapper.addClass('wpf_form_notice_'+type);
            this.$formNoticeWrapper.html(html).show();
            if (type == 'error') {
                this.form.parent().addClass('wpf_form_has_errors');
            }
        }
    }

    resetMessages() {
        this.form.removeClass('wpf_form_has_errors');
        this.$formNoticeWrapper.removeClass('wpf_form_notice_error wpf_form_notice_success wpf_form_notice_info')
        this.$formNoticeWrapper.html('').hide();
    }

    fireFormEvents(events, dataOrEvent) {
        if(typeof events == 'object') {
            jQuery.each(events, (index, eventName) => {
                this.form.trigger(eventName, [dataOrEvent]);
            });
        }
    }

    buttonState(state, loadingText, isDisabled, data) {

        let knownStates = 'wpf_submitting wpf_validating_form wpf_submitting_form wpf_authenticating_sca wpf_sca_declined wpf_confirming_payment';
        this.form.removeClass(knownStates);

        if(loadingText) {
            this.form.find('button.wpf_submit_button .wpf_txt_normal').hide();
            this.form.find('button.wpf_submit_button .wpf_txt_loading').text(loadingText).show();
        } else {
            this.form.find('button.wpf_submit_button .wpf_txt_loading').hide();
            this.form.find('button.wpf_submit_button .wpf_txt_normal').show();
        }

        if(isDisabled) {
            this.form.addClass('wpf_has_disabled_btn');
            this.form.find('button.wpf_submit_button').attr('disabled', true);
        } else {
            this.form.removeClass('wpf_has_disabled_btn');
            this.form.find('button.wpf_submit_button').attr('disabled', false);
        }

        this.form.addClass('wpf_'+state);
        this.form.trigger('wpf_'+state, [this.formId, data]);
    }

    /*
    * Subscription related functions
     */
    maybeSubscriptionSetup() {
        // Handle Radio Button Select
        let form = this.form;
        let that = this;

        function checkForRadio(element) {
            let elementName = jQuery(element).attr('name');
            let selectedIndex = jQuery(element).val();
            let $wrapper = jQuery(element).closest('.wpf_subscription_controls_radio');
            $wrapper.find('.wpf_subscription_plan_summary_item').hide();
            $wrapper.find('.wpf_subscription_plan_summary_'+elementName+' .wpf_subscription_plan_index_'+selectedIndex).show();

            $wrapper.find('.subscription_radio_custom').hide();
            $wrapper.find('.subscription_radio_custom_'+selectedIndex).show();
        }

        jQuery.each(form.find('.wpf_subscription_controls_radio input:checked'), function (index, element) {
            checkForRadio(element);
        });

        form.find('.wpf_subscription_controls_radio input[type=radio]').on('change', function () {
            checkForRadio(this);
        });

        // Handle Selection Button Select

        function checkForSelections(element) {
            let elementName = jQuery(element).attr('id');
            let selectedIndex = jQuery(element).val();
            form.find('.wpf_subscription_plan_summary_'+elementName +' .wpf_subscription_plan_summary_item').hide();
            form.find('.wpf_subscription_plan_summary_'+elementName +' .wpf_subscription_plan_index_'+selectedIndex).show();
        }

        jQuery.each(form.find('.wpf_subscrion_plans_select option:selected'), function (index, element) {
            if(jQuery(element).attr('value') != '') {
                checkForSelections(jQuery(element).parent());
            }
        });

        form.find('.wpf_subscrion_plans_select select').on('change', function () {
            checkForSelections(this);
        });
    }
    maybeCustomSubscriptionItemSetup() {
        let that = this;
        let form = this.form;
        let formSettings = this.config;
        form.find('.wpf_custom_subscription_input').on('keyup', function () {
            var $el = jQuery(this);
            var value = parseInt($el.val() * 100);
            var $hiddenEl = $el.parent().find('.wpf_payment_item');
            $hiddenEl.data('subscription_amount', value);

            var totalAmount = value + parseInt($el.data('initial_amount'));
            $hiddenEl.data('price', totalAmount);

            $el.closest('.wpf_form_group').find('.wpf_dynamic_input_amount').html(formatPrice(value, formSettings.currency_settings))
            $hiddenEl.trigger('change');
        });
        form.find('.wpf_custom_subscription_amount').on('change', function () {
            let $el = jQuery(this);
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
            let $el = jQuery(this);
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

}

export default PayFormHandler;