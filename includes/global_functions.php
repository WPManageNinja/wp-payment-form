<?php

function wpfFomatPrice($price, $formId = false)
{
    return '$' . $price;
}

function wpfGetStripePaymentMode()
{
    $paymentSettings = wpfGetStripePaymentSettings();
    return ($paymentSettings['payment_mode'] == 'live') ? 'live' : 'test';
}

function wpfGetStripePubKey()
{
    $paymentSettings = wpfGetStripePaymentSettings();
    if($paymentSettings['payment_mode'] == 'live') {
        if (wpfIsStripeKeysDefined()) {
            return WP_PAY_FORM_STRIPE_PUB_KEY;
        } else {
            return $paymentSettings['live_pub_key'];
        }
    }
    return $paymentSettings['test_pub_key'];
}

function wpfGetStripeSecretKey()
{
    $paymentSettings = wpfGetStripePaymentSettings();
    if($paymentSettings['payment_mode'] == 'live') {
        if (wpfIsStripeKeysDefined()) {
            return WP_PAY_FORM_STRIPE_SECRET_KEY;
        } else {
            return $paymentSettings['live_secret_key'];
        }
    }
    return $paymentSettings['test_secret_key'];
}

function wpfIsStripeKeysDefined()
{
    return defined('WP_PAY_FORM_STRIPE_SECRET_KEY') && defined('WP_PAY_FORM_STRIPE_PUB_KEY');
}

function wpfGetStripePaymentSettings() {
    $settings = get_option('wpf_stripe_payment_settings', array());
    $defaults = array(
        'payment_mode' => 'test',
        'live_pub_key' =>  '',
        'live_secret_key' => '',
        'test_pub_key' =>  '',
        'test_secret_key' => '',
        'company_name' =>  get_bloginfo('name'),
        'checkout_logo' =>  ''
    );
    return wp_parse_args($settings, $defaults);
}

function wpPayformDB()
{
    if (!function_exists('wpFluent')) {
        include WPPAYFORM_DIR . 'includes/libs/wp-fluent/wp-fluent.php';
    }
    return wpFluent();
}

function wpfFormattedMoney($amountInCents, $currencySettings)
{
    $symbol = $currencySettings['currency_sign'];
    $position = $currencySettings['currency_sign_position'];
    $decmalSeparator = '.';
    $thousandSeparator = ',';
    if($currencySettings['currency_separator'] != 'dot_comma') {
        $decmalSeparator = ',';
        $thousandSeparator = '.';
    }
    $decimalPoints = 2;
    if($amountInCents % 100 == 0 && $currencySettings['decimal_points'] == 0) {
        $decimalPoints = 0;
    }

    $amount = number_format(  $amountInCents / 100, $decimalPoints, $decmalSeparator, $thousandSeparator );

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