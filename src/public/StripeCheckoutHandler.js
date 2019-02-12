let StripeCheckoutHandler = {
    form: '',
    elementId: '',
    config: {},
    style: false,
    init(config, callback) {
        var that = this;
        this.config = config;
        this.form = config.form;
        this.style = config.style;
        this.callback = callback;
        let pubKey = this.form.data('stripe_pub_key');


        var handler = StripeCheckout.configure({
            key: pubKey,
            image: that.config.form_settings.checkout_logo,
            locale: 'auto',
            token: function (token, billing_shipping) {
                that.stripeTokenHandler(token, billing_shipping);
            }
        });

        // Close Checkout on page navigation:
        window.addEventListener('popstate', function () {
            handler.close();
        });

        this.form.on('stripe_payment_submit', function (event) {
            event.preventDefault();
            if(!that.form.data('payment_total')) {
                that.callback();
                return;
            }
            // Open Checkout with further options:
            handler.open({
                name: that.config.form_settings.checkout_title,
                description: that.config.form_settings.checkout_description,
                amount: that.form.data('payment_total'),
                currency: that.config.form_settings.currency_settings.currency,
                zipCode: config.verify_zip,
                email: that.form.find('input.wpf_customer_email').val(),
                billingAddress: config.billing,
                shippingAddress: config.billing && config.shipping,
                allowRememberMe: config.allowRememberMe
            });
        });
    },
    stripeTokenHandler(token, billing_shipping) {
        if(this.config.billing) {
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
            this.form.find('input[name=__stripe_billing_address_json]').remove();
            this.form.append(inputStripeBilling);
        }

        if(this.config.billing && this.config.shipping) {
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
            this.form.find('input[name=__stripe_shipping_address_json]').remove();
            this.form.append(inputStripeShipping);
        }

        var hiddenInput = document.createElement('input');
        hiddenInput.setAttribute('type', 'hidden');
        hiddenInput.setAttribute('name', 'stripeToken');
        hiddenInput.setAttribute('value', token.id);
        this.form.append(hiddenInput);

        if(token.email) {
            var hiddenEmailInput = document.createElement('input');
            hiddenEmailInput.setAttribute('type', 'hidden');
            hiddenEmailInput.setAttribute('name', '__stripe_user_email');
            hiddenEmailInput.setAttribute('value', token.email);
            this.form.append(hiddenEmailInput);
        }

        this.callback();
    }
}
export default StripeCheckoutHandler;

