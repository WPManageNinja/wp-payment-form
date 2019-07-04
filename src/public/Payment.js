export default class Payment {
    constructor(form) {
        this.form = form;
        this.hasMultiple = null;
    }

    isMultiple() {
        if (this.hasMultiple === null) {
            let multiple = this.form.find(
                'input[name=__wpf_valid_payment_methods_count]'
            );
            this.hasMultiple = multiple.length && multiple.val() > 1;
        }
        
        return this.hasMultiple;
    }

    getSinglePayment() {
        if (!this.hasMultiple) {
            return this.form.find(
                '[data-wpf_payment_method]'
            ).data('wpf_payment_method');
        }

        return false;
    }

    getMultiplePayment() {
        let val;

        if (this.isMultiple) {
            val = this.form.find(
                'input[name=__wpf_selected_payment_method]:checked'
            ).val();
        }

        return val ? val : undefined;
    }

    getSelectedMethod() {
        return this.form.data('selected_payment_method');
    }

    handlePaymentMethodChange(method) {
        this.form.data('selected_payment_method', method);
        
        if (!method) {
            this.form.find('.wpf_all_payment_methods_wrapper').hide();
            return;
        }

        this.form.find('.wpf_all_payment_methods_wrapper').show();
        this.form.find('.wpf_all_payment_methods_wrapper .wpf_payment_method_element').hide();
        this.form.find('.wpf_all_payment_methods_wrapper .wpf_payment_method_element_' + method).show();
    }
};
