<?php

namespace WPPayForm\Classes\PaymentMethods\Stripe;

use WPPayForm\Classes\GeneralSettings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Strong Csutomer Authentication here
 * @since 1.0.0
 */
class SCA
{
    public static function createPaymentIntent($args)
    {
        $argsDefault = [
            'confirmation_method' => 'manual',
            'confirm'             => 'true'
        ];

        $args = wp_parse_args($args, $argsDefault);

        $stripe = new Stripe();
        ApiRequest::set_secret_key($stripe->getSecretKey());
        return ApiRequest::request($args, 'payment_intents');
    }


    public static function setupIntent($args = [])
    {
        return ApiRequest::request($args, 'setup_intents', 'POST');
    }

    public static function confirmPayment($intendId, $args)
    {
        $argsDefault = [
            'payment_method' => ''
        ];
        $args = wp_parse_args($args, $argsDefault);
        $stripe = new Stripe();
        ApiRequest::set_secret_key($stripe->getSecretKey());
        return ApiRequest::request($args, 'payment_intents/'.$intendId.'/confirm');
    }


    public static function createInvoice($args = [])
    {
        return ApiRequest::request($args, 'invoices', 'POST');
    }

    public static function getInvoice($invoiceId)
    {
        return ApiRequest::request([], 'invoices/'.$invoiceId, 'GET');
    }

    public static function createInvoiceItem($args = [])
    {
        return ApiRequest::request($args, 'invoiceitems', 'POST');
    }
}