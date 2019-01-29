<?php

function wpfFomatPrice($price, $formId = false)
{
    return '$' . $price;
}

function wpfGetStripePubKey()
{
    if (defined('WP_PAY_FORM_STRIPE_PUB_KEY') && WP_PAY_FORM_STRIPE_PUB_KEY) {
        return WP_PAY_FORM_STRIPE_PUB_KEY;
    }
    return '';
}

function wpfGetStripeSecretKey()
{
    if (defined('WP_PAY_FORM_STRIPE_SECRET_KEY') && WP_PAY_FORM_STRIPE_SECRET_KEY) {
        return WP_PAY_FORM_STRIPE_SECRET_KEY;
    }
    return '';
}

function wpfIsStripeKeysDefined()
{
    return defined('WP_PAY_FORM_STRIPE_SECRET_KEY') && defined('WP_PAY_FORM_STRIPE_PUB_KEY');
}