<?php

namespace WPPayForm\Classes\PaymentMethods\Stripe;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Stripe Checkout Session
 * @since 1.0.0
 */
class CheckoutSession
{
    public static function create($args)
    {
        $argsDefault = [
            'payment_method_types' => ['card'],
            'locale'               => 'auto'
        ];

        $args = wp_parse_args($args, $argsDefault);

        $stripe = new Stripe();
        ApiRequest::set_secret_key($stripe->getSecretKey());
        return ApiRequest::request($args, 'checkout/sessions');
    }

    public static function retrive($sessionId, $args = [])
    {
        $stripe = new Stripe();
        ApiRequest::set_secret_key($stripe->getSecretKey());
        return ApiRequest::request($args, 'checkout/sessions/' . $sessionId, 'GET');
    }
}