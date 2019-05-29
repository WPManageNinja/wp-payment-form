const cardElementHandler = {
    init(config, callback) {
        var $this = this;
        var stripe = Stripe(config.pub_key);
        // Create an instance of Elements.
        var elements = stripe.elements();

        if (!config.style) {
            config.style = {
                base: {
                    color: '#32325d',
                    lineHeight: '18px',
                    fontSmoothing: 'antialiased',
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

        let elementId = '#' + config.elementId;
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
                config.form.find('.wpf_card-errors').html(event.error.message);
            } else {
                config.form.find('.wpf_card-errors').html('');
            }
        });

        config.form.on('stripe_payment_submit', function (event) {
            event.preventDefault();
            
            if(!config.form.data('payment_total')) {
                callback();
                return;
            }

            stripe.createToken(card).then(function (result) {
                if (result.error) {
                    // Inform the user if there was an error.
                    config.form.find('.wpf_card-errors').html(result.error.message);
                } else {
                    // Send the token to your server.
                    $this.stripeTokenHandler(config, callback, result.token);
                }
            });
        });

        config.form.on('stripe_clear',  () => {
            card.clear();
        });
    },
    stripeTokenHandler(config,callback, token) {
        var hiddenInput = document.createElement('input');
        hiddenInput.setAttribute('type', 'hidden');
        hiddenInput.setAttribute('name', 'stripeToken');
        hiddenInput.setAttribute('value', token.id);
        config.form.append(hiddenInput);
        callback();
    }
}

export default cardElementHandler;
