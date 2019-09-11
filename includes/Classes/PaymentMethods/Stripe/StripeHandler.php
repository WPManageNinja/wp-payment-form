<?php

namespace WPPayForm\Classes\PaymentMethods\Stripe;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\Models\Submission;
use WPPayForm\Classes\Models\Transaction;

if (!defined('ABSPATH')) {
    exit;
}

/**
 *  Stripe Base Class Handler where stripe payment methods
 * will extend this class
 * @since 1.3.0
 */
class StripeHandler
{
    public $parnentPamentMethod = 'stripe';


    public function getMode()
    {
        $paymentSettings = $this->getStripeSettings();
        return ($paymentSettings['payment_mode'] == 'live') ? 'live' : 'test';
    }


    // wpfGetStripePaymentSettings
    private function getStripeSettings()
    {
        $settings = get_option('wppayform_stripe_payment_settings', array());
        $defaults = array(
            'payment_mode'    => 'test',
            'live_pub_key'    => '',
            'live_secret_key' => '',
            'test_pub_key'    => '',
            'test_secret_key' => '',
            'company_name'    => get_bloginfo('name'),
            'checkout_logo'   => '',
            'send_meta_data' => 'no'
        );
        return wp_parse_args($settings, $defaults);
    }
}