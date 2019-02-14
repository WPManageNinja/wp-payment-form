let cardElementHandler = {
    form: '',
    elementId: '',
    style: false,
    init(config, callback) {
        var that = this;
        this.form = config.form;
        this.elementId = config.element_id;
        this.style = config.style;
        this.callback = callback;
        var stripe = Stripe(config.pub_key);
        // Create an instance of Elements.
        var elements = stripe.elements();

        if (!this.style) {
            this.style = {
                base: {
                    color: '#32325d',
                    lineHeight: '18px',
                    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                    fontSmoothing: 'antialiased',
                    fontSize: '16px',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                },
                invalid: {
                    color: '#fa755a',
                    iconColor: '#fa755a'
                }
            };
        }

        let elementId = '#' + that.elementId;
        // Create an instance of the card Element.
        var card = elements.create('card', {
            style: this.style,
            hidePostalCode: ! jQuery(elementId).data('verify_zip') == 'yes'
        });
        // Add an instance of the card Element into the `card-element` <div>.
        card.mount(elementId);
        // Handle real-time validation errors from the card Element.
        card.addEventListener('change', function (event) {
            if (event.error) {
                that.form.find('.wpf_card-errors').html(event.error.message);
            } else {
                that.form.find('.wpf_card-errors').html('');
            }
        });
        this.card = card;
        this.form.on('stripe_payment_submit', function (event) {
            event.preventDefault();
            if(!that.form.data('payment_total')) {
                that.callback();
                return;
            }
            stripe.createToken(card).then(function (result) {
                if (result.error) {
                    // Inform the user if there was an error.
                    that.form.find('.wpf_card-errors').html(result.error.message);
                } else {
                    // Send the token to your server.
                    that.stripeTokenHandler(result.token);
                }
            });
        });
        this.form.on('stripe_clear',  () => {
            this.card.clear();
        });
    },
    stripeTokenHandler(token) {
        var hiddenInput = document.createElement('input');
        hiddenInput.setAttribute('type', 'hidden');
        hiddenInput.setAttribute('name', 'stripeToken');
        hiddenInput.setAttribute('value', token.id);
        this.form.append(hiddenInput);
        this.callback();
    }
}
export default cardElementHandler;

