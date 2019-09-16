// Total amount is in cents
function formatMoney(n, decimals, decimal_sep, thousands_sep)
{
        var c = isNaN(decimals) ? 2 : Math.abs(decimals), //if decimal is zero we must take it, it means user does not want to show any decimal
        d = decimal_sep || '.', //if no decimal separator is passed we use the dot as default decimal separator (we MUST use a decimal separator)
        /*
        according to [https://stackoverflow.com/questions/411352/how-best-to-determine-if-an-argument-is-not-sent-to-the-javascript-function]
        the fastest way to check for not defined parameter is to use typeof value === 'undefined'
        rather than doing value === undefined.
        */
        t = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep, //if you don't want to use a thousands separator you can pass empty string as thousands_sep value

        sign = (n < 0) ? '-' : '',

        //extracting the absolute value of the integer part of the number and converting to string
        i = parseInt(n = Math.abs(n).toFixed(c)) + '',

        j = ((j = i.length) > 3) ? j % 3 : 0;
        return sign + (j ? i.substr(0, j) + t : '') + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : '');
}

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

    let symbol = currency_settings.currency_sign || '';
    let money = formatMoney(amount, precision, decimalSeparator, thousandSeparator);

    if(currency_settings.currency_sign_position == 'right') {
        return money+''+symbol;
    } else if(currency_settings.currency_sign_position == 'left_space') {
        return symbol+' '+money;
    } else if(currency_settings.currency_sign_position == 'right_space') {
        return money+' '+symbol;
    }
    return symbol+''+money;
}

export default formatPrice;