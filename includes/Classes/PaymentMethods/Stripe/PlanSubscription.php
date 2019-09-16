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
    public static function create($subscription, $customer, $submission)
    {
        $plan = Plan::getOrCreatePlan($subscription, $submission);
        if ($plan && is_wp_error($plan)) {
            return $plan;
        }

        $billablePlan[] = array(
            'plan'     => $plan->id,
            'quantity' => $subscription->quantity,
            'metadata' => array(
                'wpf_subscription_id' => $subscription->id
            )
        );

        $subscriptionArgs = array(
            'customer' => $customer,
            'billing'  => 'charge_automatically',
            'items'    => $billablePlan,
            'metadata' => array(
                'submission_id' => $submission->id,
                'wpf_subscription_id' => $subscription->id,
                'form_id' => $submission->form_id
            ),
            'expand' => [
                'latest_invoice',
            ],
            'off_session' => 'true'
        );

        if($subscription->trial_days) {
            $subscriptionArgs['trial_end'] = time() + $subscription->trial_days * 86400;
        }

        return self::subscribe($subscriptionArgs);
    }

    public static function subscribe($subscriptionArgs)
    {
        $stripe = new Stripe();
        ApiRequest::set_secret_key($stripe->getSecretKey());
        return ApiRequest::request($subscriptionArgs, 'subscriptions', 'POST');
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