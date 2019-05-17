import formatMoney from "accounting-js/lib/formatMoney";
// Total amount is in cents
function formatPrice(allTotalAmount, currency_settings)
{
    if(!allTotalAmount) {
        allTotalAmount = 0;
    }
    let amount =  parseInt(allTotalAmount) / 100;
    let precision = 2;
    if(allTotalAmount % 100 == 0 && currency_settings.decimal_points == 0) {
        precision = 0;
    }
    let thousandSeparator = ',';
    let decimalSeparator = '.';
    if(currency_settings.currency_separator != 'dot_comma') {
        thousandSeparator = '.';
        decimalSeparator = ',';
    }

    let format =  "%s%v";

    if(currency_settings.currency_sign_position == 'right') {
        format =  "%v%s";
    } else if(currency_settings.currency_sign_position == 'left_space') {
        format =  "%s %v";
    } else if(currency_settings.currency_sign_position == 'right_space') {
        format =  "%v %s";
    }

    return formatMoney( amount, {
        symbol: currency_settings.currency_sign || '',
        precision: precision,
        thousand: thousandSeparator,
        decimal: decimalSeparator,
        format: format
    } );
}

export default formatPrice;