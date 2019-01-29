import formatMoney from 'accounting-js/lib/formatMoney.js'

var wpPayformApp = {};
(function( $ ) {
    wpPayformApp = {
        forms: {},
        general: window.wp_payform_general,
        formData: {},
        init() {
            let body = $(document.body);
            this.forms = body.find('.wpf_form');
            this.forms.each( function() {
                var form = $( this );
                wpPayformApp.initForm( form );
                body.trigger( 'wpPayFormProcessFormElements', [ form ] );
            } );
        },
        initForm(form) {
            let that = this;
            this.calculatePayments(form);
            let formId = form.data('wpf_form_id');
            let formSettings = window['wp_payform_'+formId];

            form.parent().find('.wpf_form_notices').hide();

            form.find('.wpf_payment_item, .wpf_item_qty').on('change', () => {
                this.calculatePayments(form);
            });
            // Handle Form Submit
            form.on('submit', function (event) {
                event.preventDefault();
                $.post(that.general.ajax_url, {
                    action: 'wpf_submit_form',
                    form_id: formId,
                    payment_total: form.data('payment_total'),
                    form_data: $(this).serialize()
                })
                    .then(response => {
                        $('#wpf_form_id_'+formId)[0].reset();
                        form.parent().find('.wpf_form_success').html(response.data.message).show();
                    })
                    .fail(error => {
                        let $errorDiv = form.parent().find('.wpf_form_errors');
                        $errorDiv.html('<p class="wpf_form_error_heading">'+error.responseJSON.data.message+'</p>').show();
                        $errorDiv.append('<ul class="wpf_error_items">');
                        $.each(error.responseJSON.data.errors, (errorId, errorText) => {
                            $errorDiv.append('<li class="error_item_'+errorId+'">'+errorText+'</li>');
                        });
                        $errorDiv.append('</ul>');
                    });
            });
        },
        calculatePayments(form) {
            let elements = form.find('.wpf_payment_item');
            let itemTotalValue = {};
            elements.each(function (index, elem) {
                let elementType = elem.type;
                let elementName = $(elem).attr('name');
                if(elementType == 'radio') {
                    let itemValue = form.find('input[name='+elementName+']:checked').data('price');
                    if(itemValue) {
                        itemTotalValue[elementName] = parseInt(itemValue);
                    }
                }
                else if(elementType == 'hidden') {
                    itemTotalValue[elementName] = parseInt($(elem).data('price'));
                } else if(elementType == 'number') {
                    itemTotalValue[elementName] =  parseInt(parseFloat($(this).val()) * 100);
                } else if(elementType == 'checkbox') {
                    let groupId = $(elem).data('group_id');
                    let groups = form.find('input[data-group_id="'+groupId+'"]:checked');
                    let groupTotal = 0;
                    groups.each((index, group) => {
                        groupTotal += parseInt($(group).data('price'));
                    });
                    itemTotalValue[groupId] = groupTotal;
                }
                else if(elementType == 'select-one') {
                    let itemValue = form.find('select[name='+elementName+'] option:selected').data('price');
                    if(itemValue) {
                        itemTotalValue[elementName] = parseInt(itemValue);
                    }
                }
            });

            let allTotalAmount = 0;
            // Get The Total Now
            jQuery.each(itemTotalValue, (itemName, itemValue) => {
                // check if there has a quantity
                let targetQuantity = form.find('.wpf_item_qty[data-target_product='+itemName+']');
                if(targetQuantity.length) {
                    let qty = $(targetQuantity).val();
                    if(parseInt(qty)) {
                        allTotalAmount +=  Math.abs(parseInt(qty)) * itemValue;
                    }
                } else {
                    allTotalAmount += itemValue;
                }
            });
            if(allTotalAmount) {
                form.find('.wpf_calc_payment_total').html(this.formatPrice(allTotalAmount, form));
            } else {
                form.find('.wpf_calc_payment_total').html(this.formatPrice(0, form));
            }

            form.data('payment_total', allTotalAmount);
        },
        formatPrice(allTotalAmount, form) {
            let amount =  allTotalAmount / 100;
            return formatMoney(amount, { symbol: "$", precision: 2, thousand: ",", decimal: "." });
        }
    };

    $( document ).ready( function( $ ) {
        wpPayformApp.init();
    } );

}(jQuery));