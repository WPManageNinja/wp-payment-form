<?php
namespace WPPayForm\Classes\StripePayments;
use WPPayForm\Classes\GeneralSettings;
use Stripe\Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Payment Charge Via Stripe
 * @since 1.0.0
 */
class Charge
{
    public function charge($paymentArgs)
    {
        $errors = $this->validate($paymentArgs);
        if($errors) {
            return $this->errorHandler('validation_failed', __('Payment data validation failed', 'wppayform'), $errors);
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
            \Stripe\Stripe::setApiKey(wpfGetStripeSecretKey());
            $charge = \Stripe\Charge::create($paymentArgs);
            if ( false !== $charge ) {
                do_action( 'wpf_stripe_charge_created', $charge, $paymentArgs );
                return $charge;
            }
            return $this->errorHandler('general', __('Charge failed', 'wppayform'));
        } catch ( Error\Card $e ) {
            // Card declined
            return $this->errorHandler( 'card_error', esc_html__( 'Card Error', 'wppayform' ) . ': ' . $e->getMessage() );
        } catch ( Error\RateLimit $e ) {
            // Too many requests made to the API too quickly
            return $this->errorHandler( 'rate_limit', esc_html__( 'Rate Limit Error', 'wppayform' ) . ': ' . $e->getMessage() );

        } catch ( Error\InvalidRequest $e ) {
            // Invalid parameters were supplied to Stripe's API
            return $this->errorHandler( 'invalid_request', esc_html__( 'Invalid Request Error', 'wppayform' ) . ': ' . $e->getMessage() );

        } catch ( Error\Authentication $e ) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            return $this->errorHandler( 'authentication', esc_html__( 'Authentication Error', 'wppayform' ) . ': ' . $e->getMessage() );

        } catch ( Error\ApiConnection $e ) {
            // Network communication with Stripe failed
            return $this->errorHandler( 'api_connection', esc_html__( 'Stripe API Connection Error', 'wppayform' ) . ': ' . $e->getMessage() );

        } catch ( Error\Base $e ) {
            // Display a very generic error to the user, and maybe send
            return $this->errorHandler( 'generic', esc_html__( 'Stripe Error', 'wppayform' ) . ': ' . $e->getMessage() );
        } catch ( \Exception $e ) {
            // Something else happened, completely unrelated to Stripe
            return $this->errorHandler( 'non_stripe', esc_html__( 'General Error', 'wppayform' ) . ': ' . $e->getMessage() );
        }
    }

    private function validate($args)
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
        if(empty($args['source'])) {
            $errors['source'] = __('Stripe Token is required', 'wppayform');
        }

        // Validate Amount
        if(empty($args['amount'])) {
            $errors['amount'] = __('Payment Amount can not be 0', 'wppayform');
        }

        return $errors;
    }

    private function errorHandler($code, $message, $data = array()) {
        return new \WP_Error($code, $message, $data);
    }

}