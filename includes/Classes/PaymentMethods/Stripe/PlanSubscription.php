<?php

namespace WPPayForm\Classes\PaymentMethods\Stripe;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\GeneralSettings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Plan Subscription Via Stripe
 * @since 1.2.0
 */
class PlanSubscription
{
    public static function create($subscriptions, $customer, $submission)
    {
        $billablePlans = [];
        foreach ($subscriptions as $subscription) {
            $plan = Plan::getOrCreatePlan($subscription, $submission);
            if ($plan && is_wp_error($plan)) {
                return $plan;
            }
            $billablePlans[] = array(
                'plan'     => $plan->id,
                'quantity' => $subscription->quantity,
                'metadata' => array(
                    'wpf_subscription_id' => $subscription->id
                )
            );
        }

        $subscriptionArgs = array(
            'customer' => $customer,
            'billing'  => 'charge_automatically',
            'items'    => $billablePlans
        );
        return self::subscribe($subscriptionArgs);
    }

    private static function subscribe($subscriptionArgs)
    {
        $stripe = new Stripe();
        ApiRequest::set_secret_key($stripe->getSecretKey());
        $response = ApiRequest::request($subscriptionArgs, 'subscriptions', 'POST');
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

    private static function errorHandler($code, $message, $data = array())
    {
        return new \WP_Error($code, $message, $data);
    }

}