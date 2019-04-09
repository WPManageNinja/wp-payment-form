<?php
namespace WPPayForm\Classes\PaymentMethods\Stripe;
use WPPayForm\Classes\GeneralSettings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Payment Charge Via Stripe
 * @since 1.0.0
 */
class Charge
{
    public static function charge($paymentArgs)
    {
        $errors = self::validate($paymentArgs);
        if($errors) {
            return self::errorHandler('validation_failed', __('Payment data validation failed', 'wppayform'), $errors);
        }
        if ( ! empty( $paymentArgs['statement_descriptor'] ) ) {
            $illegal = array( '<', '>', '"', "'" );
            // Remove slashes
            $descriptor = stripslashes( $paymentArgs['statement_descriptor'] );
            // Remove illegal characters
            $descriptor = str_replace( $illegal, '', $descriptor );
            // Trim to 22 characters max
            $descriptor = substr( $descriptor, 0, 22 );
            $paymentArgs['statement_descriptor'] = $descriptor;
        }
        try {
            $stripe = new Stripe();
            ApiRequest::set_secret_key($stripe->getSecretKey());
            $response = ApiRequest::request($paymentArgs, 'charges');
            if ( !empty($response->error) ) {
                $errotType = 'general';
                if(!empty($response->error->type)) {
                    $errotType = $response->error->type;
                }
                $errorCode = '';
                if(!empty($response->error->code)) {
                    $errorCode = $response->error->code.' : ';
                }
                return self::errorHandler($errotType, $errorCode. $response->error->message);
            }
            if ( false !== $response ) {
                do_action( 'wppayform/stripe_charge_created', $response, $paymentArgs );
                return $response;
            }
        } catch ( \Exception $e ) {
            // Something else happened, completely unrelated to Stripe
            return self::errorHandler( 'non_stripe', esc_html__( 'General Error', 'wppayform' ) . ': ' . $e->getMessage() );
        }
        return false;
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