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
class Events
{
    public static function getEvents($args = [])
    {
        $stripe = new Stripe();
        ApiRequest::set_secret_key($stripe->getSecretKey());
        return ApiRequest::request($args, 'events', 'GET');
    }
}