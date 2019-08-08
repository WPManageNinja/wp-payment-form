export function stripCardInit(elementId, pubKey, form) {
    const stripe = Stripe(pubKey);
    const elements = stripe.elements();
    const style = {
        base: {
            // Add your base input styles here. For example:
            fontSize: '16px',
            color: "#32325d",
        },
    };
    const card = elements.create('card', {style});
    card.mount('#' + elementId);

    // Error Handling
    card.addEventListener('change', ({error}) => {
        const displayError = form.find('.wpf_card-errors');
        if (error) {
            displayError.html(error.message);
        } else {
            displayError.html('');
        }
    });

    return {card, stripe};
};


export async function stripeElementHandleToken(card, stripe, form) {

    const {paymentMethod, error} =  await stripe.createPaymentMethod(
        'card',
        card
    );

    const displayError = form.find('.wpf_card-errors');

    if (error) {
        displayError.html(error.message);
        return Promise.reject(error.message);
    } else {
        displayError.html('');
        var hiddenInput = document.createElement('input');
        hiddenInput.setAttribute('type', 'hidden');
        hiddenInput.setAttribute('name', '__stripePaymentMethodId');
        hiddenInput.setAttribute('value', paymentMethod.id);
        form.append(hiddenInput);
        return Promise.resolve(paymentMethod.id);
    }
}


export default { stripCardInit, stripeElementHandleToken };