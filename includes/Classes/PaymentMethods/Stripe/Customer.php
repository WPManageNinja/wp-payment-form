<?php
namespace WPPayForm\Classes\PaymentMethods\Stripe;
if (!defined('ABSPATH')) {
    exit;
}
/**
 * Handle Payment Charge Via Stripe
 * @since 1.0.0
 */
class Customer
{
    public static function createCustomer($customerArgs)
    {
        $errors = self::validate($customerArgs);
        if($errors) {
            return self::errorHandler('validation_failed', __('Payment data validation failed', 'wppayform'), $errors);
        }
        try {
            $stripe = new Stripe();
            ApiRequest::set_secret_key($stripe->getSecretKey());
            $response = ApiRequest::request($customerArgs, 'customers');
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
                do_action( 'wppayform/stripe_customer_created', $response, $customerArgs );
                return $response;
            }
        } catch ( \Exception $e ) {
            // Something else happened, completely unrelated to Stripe
            return self::errorHandler( 'non_stripe', esc_html__( 'General Error', 'wppayform' ) . ': ' . $e->getMessage() );
        }
        return false;
    }

    public static function validate($args)
    {
        $errors = array();
        // check if the currency is right or not
        if(empty($args['source'])) {
            $errors['source'] = __('Stripe token is required', 'wppayform');
        }
        return $errors;
    }

    private static function errorHandler($code, $message, $data = array()) {
        return new \WP_Error($code, $message, $data);
    }

}