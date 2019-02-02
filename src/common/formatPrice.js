import formatMoney from "accounting-js/lib/formatMoney";
// Total amount is in cents
function formatPrice(allTotalAmount, currency_settings)
{
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
    return formatMoney( amount, {
        symbol: currency_settings.currency_sign,
        precision: precision,
        thousand: thousandSeparator,
        decimal: decimalSeparator
    } );
}

export default formatPrice;