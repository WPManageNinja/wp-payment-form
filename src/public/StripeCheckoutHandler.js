const StripeCheckoutHandler = {
    init(config, callback) {
        var $this = this;
        var handler = StripeCheckout.configure({
            key: config.form_settings.stripe_pub_key,
            image: config.form_settings.stripe_checkout_logo,
            locale: 'auto',
            token: function (token, billing_shipping) {
                $this.stripeTokenHandler(config, callback, token, billing_shipping);
            }
        });

        // Close Checkout on page navigation:
        window.addEventListener('popstate', function () {
            handler.close();
        });

        config.form.on('stripe_payment_submit', function (event) {
            event.preventDefault();
            if(!config.form.data('payment_total')) {
                callback();
                return;
            }

            let paymentAmoyunt = config.form.data('payment_total');
            if(config.form_settings.currency_settings.is_zero_decimal) {
                paymentAmoyunt = parseInt(paymentAmoyunt / 100);
            }

            // Open Checkout with further options:
            let paymentConfig = {
                name: config.form_settings.stripe_checkout_title,
                description: config.form_settings.checkout_description,
                amount: paymentAmoyunt,
                currency: config.form_settings.currency_settings.currency,
                zipCode: config.verify_zip,
                email: config.form.find('input.wpf_customer_email').val(),
                allowRememberMe: config.allowRememberMe
            };

            if(config.billing ) {
                paymentConfig.billingAddress = true;
            }

            if(config.billing && config.shipping) {
                paymentConfig.shippingAddress = true;
            }
            handler.open(paymentConfig);
        });
    },
    stripeTokenHandler(config, callback, token, billing_shipping) {
        if(config.billing) {
            var inputStripeBilling = document.createElement('input');
            inputStripeBilling.setAttribute('type', 'hidden');
            inputStripeBilling.setAttribute('name', '__stripe_billing_address_json');
            inputStripeBilling.setAttribute('value', JSON.stringify({
                'name': billing_shipping.billing_name,
                'address_line1' : billing_shipping.billing_address_line1,
                'address_city' : billing_shipping.billing_address_city,
                'address_zip': billing_shipping.billing_address_zip,
                'address_country': billing_shipping.billing_address_country
            }));
            // Delete if exists
            config.form.find('input[name=__stripe_billing_address_json]').remove();
            config.form.append(inputStripeBilling);
        }

        if(config.billing && config.shipping) {
            var inputStripeShipping = document.createElement('input');
            inputStripeShipping.setAttribute('type', 'hidden');
            inputStripeShipping.setAttribute('name', '__stripe_shipping_address_json');
            inputStripeShipping.setAttribute('value', JSON.stringify({
                'name': billing_shipping.shipping_name,
                'address_line1' : billing_shipping.shipping_address_line1,
                'address_city' : billing_shipping.shipping_address_city,
                'address_zip': billing_shipping.shipping_address_zip,
                'address_country': billing_shipping.shipping_address_country
            }));
            // Delete if exists
            config.form.find('input[name=__stripe_shipping_address_json]').remove();
            config.form.append(inputStripeShipping);
        }

        var hiddenInput = document.createElement('input');
        hiddenInput.setAttribute('type', 'hidden');
        hiddenInput.setAttribute('name', 'stripeToken');
        hiddenInput.setAttribute('value', token.id);
        config.form.append(hiddenInput);

        if(token.email) {
            var hiddenEmailInput = document.createElement('input');
            hiddenEmailInput.setAttribute('type', 'hidden');
            hiddenEmailInput.setAttribute('name', '__stripe_user_email');
            hiddenEmailInput.setAttribute('value', token.email);
            config.form.append(hiddenEmailInput);
        }

        callback();
    }
}

export default StripeCheckoutHandler;
