<?php

namespace WPPayForm\Classes\PaymentMethods\Stripe;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\GeneralSettings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Payment Charge Via Stripe
 * @since 1.2.0
 */
class Plan
{
    public static function retirive($planId)
    {
        try {
            $stripe = new Stripe();
            ApiRequest::set_secret_key($stripe->getSecretKey());
            $response = ApiRequest::request([], 'plans/' . $planId, 'GET');
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
        } catch (\Exception $e) {
            // Something else happened, completely unrelated to Stripe
            return self::errorHandler('non_stripe', esc_html__('General Error', 'wppayform') . ': ' . $e->getMessage());
        }
        return false;
    }

    public static function create($plan)
    {
        $stripe = new Stripe();
        ApiRequest::set_secret_key($stripe->getSecretKey());
        $response = ApiRequest::request($plan, 'plans', 'POST');
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

    public static function getOrCreatePlan($subscription, $submission)
    {
        if (GeneralSettings::isZeroDecimal($submission->currency)) {
            $subscription->recurring_amount = intval($subscription->recurring_amount / 100);
        }

        // Generate The subscription ID Here
        $subscriptionId = self::getGeneratedSubscriptionId($subscription, $submission->currency);
        $stripePlan = self::retirive($subscriptionId);
        if ($stripePlan && !is_wp_error($stripePlan)) {
            return $stripePlan;
        }

        // We don't have this plan yet. Now we have to create the plan from subscription
        $plan = array(
            'id'                => $subscriptionId,
            'currency'          => $submission->currency,
            'interval'          => $subscription->billing_interval,
            'amount'            => $subscription->recurring_amount,
            'trial_period_days' => $subscription->trial_days,
            'product'           => array(
                'id'   => $subscriptionId,
                'name' => $subscription->item_name . ' (' . $subscription->plan_name . ')',
                'type' => 'service'
            ),
            'metadata'          => array(
                'form_id'    => $subscription->form_id,
                'element_id' => $subscription->element_id,
                'wp_plugin'  => 'wppayform'
            )
        );
        return self::create($plan);
    }

    private static function validate($args)
    {
        $errors = array();
        // check if the currency is right or not
        if (isset($args['currency'])) {
            $supportedCurrncies = GeneralSettings::getCurrencies();
            if (!isset($supportedCurrncies[$args['currency']])) {
                $errors['currency'] = __('Invalid currency', 'wppayform');
            }
        } else {
            $errors['currency'] = __('Currency is required', 'wppayform');
        }
        // Validate the token
        if (empty($args['source']) && empty($args['customer'])) {
            $errors['source'] = __('Stripe Token is required', 'wppayform');
        }

        // Validate Amount
        if (empty($args['amount'])) {
            $errors['amount'] = __('Payment Amount can not be 0', 'wppayform');
        }

        return $errors;
    }

    private static function errorHandler($code, $message, $data = array())
    {
        return new \WP_Error($code, $message, $data);
    }

    public static function getGeneratedSubscriptionId($subscription, $currency = 'USD')
    {
        return $subscriptionId = 'wpf_' . $subscription->form_id . '_' . $subscription->element_id . '_' . $subscription->recurring_amount . '_' . $subscription->billing_interval . '_' . $subscription->trial_days . '_' . $currency;;
    }

}