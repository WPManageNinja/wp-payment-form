<?php

if(!function_exists('wpFluent')) {
    include WPPAYFORM_DIR . 'includes/libs/wp-fluent/wp-fluent.php';
}

function wpPayFormDB()
{
    if (!function_exists('wpFluent')) {
        include WPPAYFORM_DIR . 'includes/libs/wp-fluent/wp-fluent.php';
    }
    return wpFluent();
}


function wpPayFormFormatMoney($amountInCents, $formId = false)
{
    if(!$formId) {
        $currencySettings = \WPPayForm\Classes\GeneralSettings::getGlobalCurrencySettings();
    } else {
        $currencySettings = \WPPayForm\Classes\Models\Forms::getCurrencySettings($formId);
    }
    if(empty($currencySettings['currency_sign'])) {
        $currencySettings['currency_sign'] = \WPPayForm\Classes\GeneralSettings::getCurrencySymbol( $currencySettings['currency']);
    }
    return wpPayFormFormattedMoney($amountInCents, $currencySettings);
}

function wpPayFormFormattedMoney($amountInCents, $currencySettings)
{
    $symbol = $currencySettings['currency_sign'];
    $position = $currencySettings['currency_sign_position'];
    $decmalSeparator = '.';
    $thousandSeparator = ',';
    if ($currencySettings['currency_separator'] != 'dot_comma') {
        $decmalSeparator = ',';
        $thousandSeparator = '.';
    }
    $decimalPoints = 2;
    if ($amountInCents % 100 == 0 && $currencySettings['decimal_points'] == 0) {
        $decimalPoints = 0;
    }

    $amount = number_format($amountInCents / 100, $decimalPoints, $decmalSeparator, $thousandSeparator);

    if ('left' === $position) {
        return $symbol . $amount;
    } elseif ('left_space' === $position) {
        return $symbol . ' ' . $amount;
    } elseif ('right' === $position) {
        return $amount . $symbol;
    } elseif ('right_space' === $position) {
        return $amount . ' ' . $symbol;
    }
    return $amount;
}

function wpPayFormConverToCents($amount)
{
    if (!$amount) {
        return 0;
    }
    $amount = floatval($amount);
    return round($amount * 100, 0);
}