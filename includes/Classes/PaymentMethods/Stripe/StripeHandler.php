<?php

namespace WPPayForm\Classes\PaymentMethods\Stripe;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\GeneralSettings;
use WPPayForm\Classes\Models\Submission;
use WPPayForm\Classes\Models\SubmissionActivity;
use WPPayForm\Classes\Models\Subscription;
use WPPayForm\Classes\Models\SubscriptionTransaction;
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
            'send_meta_data'  => 'no'
        );
        return wp_parse_args($settings, $defaults);
    }

    public function handleInlineSubscriptions($customer, $submission, $form)
    {
        $subscriptionModel = new Subscription();
        $subscriptionTransactionModel = new SubscriptionTransaction();
        $subscriptions = $subscriptionModel->getSubscriptions($submission->id);

        if (!$subscriptions) {
            return false;
        }

        $isOneSucceed = false;
        $subscriptionStatus = 'active';
        foreach ($subscriptions as $subscriptionItem) {
            $subscription = PlanSubscription::create($subscriptionItem, $customer, $submission);

            if (!$subscription || is_wp_error($subscription)) {
                $subscriptionModel->update($subscriptionItem->id, [
                    'status' => 'failed',
                ]);
                if ($isOneSucceed) {
                    $message = __('Stripe error when creating subscription plan for you. Your card might be charged for atleast one subscription. Please contact site admin to resolve the issue', 'wppayform');
                } else {
                    $message = __('Stripe error when creating subscription plan for you. Please contact site admin', 'wppayform');
                }
                $errorCode = 400;
                if (is_wp_error($subscription)) {
                    $errorCode = $subscription->get_error_code();
                    $message = $subscription->get_error_message($errorCode);
                }
                return new \WP_Error($errorCode, $message, $subscription);
            }

            $isOneSucceed = true;
            if ($subscriptionItem->trial_days) {
                $subscriptionStatus = 'trialling';
            }

            $subscriptionModel->update($subscriptionItem->id, [
                'status'                 => $subscriptionStatus,
                'vendor_customer_id'     => $subscription->customer,
                'vendor_subscriptipn_id' => $subscription->id,
                'vendor_plan_id'         => $subscription->plan->id,
                'vendor_response'        => maybe_serialize($subscription),
            ]);

            if (!$subscriptionItem->trial_days) {
                // Let's create the Subscription Transaction
                $latestInvoice = $subscription->latest_invoice;
                if ($latestInvoice->total) {
                    $totalAmount = $latestInvoice->total;
                    if (GeneralSettings::isZeroDecimal($submission->currency)) {
                        $totalAmount = intval($latestInvoice->total * 100);
                    }
                    $transactionItem = [
                        'form_id'          => $submission->form_id,
                        'user_id'          => $submission->user_id,
                        'submission_id'    => $submission->id,
                        'subscription_id'  => $subscriptionItem->id,
                        'transaction_type' => 'subscription',
                        'payment_method'   => 'stripe',
                        'charge_id'        => $latestInvoice->charge,
                        'payment_total'    => $totalAmount,
                        'status'           => $latestInvoice->status,
                        'currency'         => $latestInvoice->currency,
                        'payment_mode'     => ($latestInvoice->livemode) ? 'live' : 'test',
                        'payment_note'     => maybe_serialize($latestInvoice),
                        'created_at'       => gmdate('Y-m-d H:i:s', $latestInvoice->created),
                        'updated_at'       => gmdate('Y-m-d H:i:s', $latestInvoice->created)
                    ];
                    $subscriptionTransactionModel->maybeInsertCharge($transactionItem);
                }
            }
        }

        SubmissionActivity::createActivity(array(
            'form_id'       => $form->ID,
            'submission_id' => $submission->id,
            'type'          => 'activity',
            'created_by'    => 'PayForm BOT',
            'content'       => __('Stripe recurring subscription successfully initiated', 'wppayform')
        ));

        $submissionModel = new Submission();

        $submissionModel->update($submission->id, [
            'payment_status' => 'paid',
            'status'         => $subscriptionStatus
        ]);

        SubmissionActivity::createActivity(array(
            'form_id'       => $form->ID,
            'submission_id' => $submission->id,
            'type'          => 'activity',
            'created_by'    => 'PayForm BOT',
            'content'       => __('Subscription status changed to : ', 'wppayform') . $subscriptionStatus
        ));

        return $subscriptionModel->getSubscriptions($submission->id);
    }


    public function handlePaymentChargeError($message, $submission, $transaction, $form, $charge = false, $type = 'general')
    {
        $paymentMode = $this->getMode();
        do_action('wppayform/form_payment_stripe_failed', $submission, $transaction, $form, $charge, $type);
        do_action('wppayform/form_payment_failed', $submission, $transaction, $form, $charge, $type);

        $submissionModel = new Submission();

        if ($transaction) {
            $transactionModel = new Transaction();
            $transactionModel->update($transaction->id, array(
                'status'         => 'failed',
                'payment_method' => 'stripe',
                'payment_mode'   => $paymentMode,
            ));
        }


        $submissionModel->update($submission->id, array(
            'payment_status' => 'failed',
            'payment_method' => 'stripe',
            'payment_mode'   => $paymentMode,
        ));

        SubmissionActivity::createActivity(array(
            'form_id'       => $form->ID,
            'submission_id' => $submission->id,
            'type'          => 'activity',
            'created_by'    => 'PayForm BOT',
            'content'       => __('Payment Failed via stripe. Status changed from Pending to Failed.', 'wppayform')
        ));

        if ($message) {
            SubmissionActivity::createActivity(array(
                'form_id'       => $form->ID,
                'submission_id' => $submission->id,
                'type'          => 'error',
                'created_by'    => 'PayForm BOT',
                'content'       => $message
            ));
        }

        wp_send_json_error(array(
            'message'       => $message,
            'payment_error' => true,
            'type'          => $type,
            'form_events'   => [
                'payment_failed'
            ]
        ), 423);
    }



}