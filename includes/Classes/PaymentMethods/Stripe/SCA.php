<?php

namespace WPPayForm\Classes\PaymentMethods\Stripe;

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
        $response = ApiRequest::request($args, 'payment_intents');
        if (!empty($response->error)) {
            $errotType = 'general';
            if (!empty($response->error->type)) {
                $errotType = $response->error->type;
            }
            $errorCode = '';
            if (!empty($response->error->code)) {
                $errorCode = $response->error->code . ' : ';
            }
            return self::errorHandler($errotType, $errorCode . $response->error->message);
        }
        if (false !== $response) {
            return $response;
        }
        return false;
    }

    public static function retrivePaymentIntent($intentId, $args = [])
    {
        $stripe = new Stripe();
        ApiRequest::set_secret_key($stripe->getSecretKey());
        $response =  ApiRequest::request($args, 'payment_intents/'.$intentId);

        if (!empty($response->error)) {
            $errotType = 'general';
            if (!empty($response->error->type)) {
                $errotType = $response->error->type;
            }
            $errorCode = '';
            if (!empty($response->error->code)) {
                $errorCode = $response->error->code . ' : ';
            }
            return self::errorHandler($errotType, $errorCode . $response->error->message);
        }
        if (false !== $response) {
            return $response;
        }
        return false;
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
        return ApiRequest::request($args, 'payment_intents/' . $intendId . '/confirm');
    }


    public static function createInvoice($args = [])
    {
        return ApiRequest::request($args, 'invoices', 'POST');
    }

    public static function getInvoice($invoiceId)
    {
        return ApiRequest::request([], 'invoices/' . $invoiceId, 'GET');
    }

    public static function createInvoiceItem($args = [])
    {
        return ApiRequest::request($args, 'invoiceitems', 'POST');
    }

    private static function errorHandler($code, $message, $data = array())
    {
        return new \WP_Error($code, $message, $data);
    }
}