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
            image: 'https://stripe.com/img/documentation/checkout/marketplace.png',
            locale: 'auto',
            token: function (token) {
                that.stripeTokenHandler(token);
            }
        });

        // Close Checkout on page navigation:
        window.addEventListener('popstate', function () {
            handler.close();
        });

        this.form.on('submit', function (event) {
            event.preventDefault();
            if(!that.form.data('payment_total')) {
                that.callback();
                return;
            }
            // Open Checkout with further options:
            handler.open({
                name: 'WPManageNinja Ex1',
                description: '2 widgets',
                amount: that.form.data('payment_total'),
                zipCode: config.verify_zip,
                email: that.form.find('input.wpf_customer_email').val(),
                billingAddress: config.billing,
                shippingAddress: config.shipping,
                allowRememberMe: config.allowRememberMe
            });
        });
    },
    stripeTokenHandler(token) {
        if(this.config.billing || this.config.shipping) {
            var inputStripeBilling = document.createElement('input');
            inputStripeBilling.setAttribute('type', 'hidden');
            inputStripeBilling.setAttribute('name', '__stripe_address_json');
            let card = token.card;
            inputStripeBilling.setAttribute('value', JSON.stringify({
                'name': card.name,
                'address_line1' : card.address_line1,
                'address_line2': card.address_line2,
                'address_city' : card.address_city,
                'address_zip': card.address_zip,
                'address_country': card.address_country
            }));
            // Delete if exists
            this.form.find('input[name=__stripe_address_json]').remove();
            this.form.append(inputStripeBilling);
        }
        var hiddenInput = document.createElement('input');
        hiddenInput.setAttribute('type', 'hidden');
        hiddenInput.setAttribute('name', 'stripeToken');
        hiddenInput.setAttribute('value', token.id);
        this.form.append(hiddenInput);
        this.callback();
    }
}
export default StripeCheckoutHandler;

