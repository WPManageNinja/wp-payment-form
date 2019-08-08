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
            'confirm' => 'true'
       ];

       $args = wp_parse_args($args, $argsDefault);

        $stripe = new Stripe();
        ApiRequest::set_secret_key($stripe->getSecretKey());


       return ApiRequest::request($args, 'payment_intents');

    }

    private static function validate($args)
    {
        $errors = array();
        // check if the currency is right or not
        if(isset($args['currency'])) {
            $supportedCurrncies = GeneralSettings::getCurrencies();
            if(!isset($supportedCurrncies[$args['currency']])) {
                $errors['currency'] = __('Invalid currency', 'wppayform');
            }
        } else {
            $errors['currency'] = __('Currency is required', 'wppayform');
        }
        // Validate the token
        if(empty($args['source'])  && empty($args['customer'])) {
            $errors['source'] = __('Stripe Token is required', 'wppayform');
        }

        // Validate Amount
        if(empty($args['amount'])) {
            $errors['amount'] = __('Payment Amount can not be 0', 'wppayform');
        }

        return $errors;
    }

    private static function errorHandler($code, $message, $data = array()) {
        return new \WP_Error($code, $message, $data);
    }

}