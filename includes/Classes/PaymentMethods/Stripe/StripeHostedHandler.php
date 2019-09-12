<?php

namespace WPPayForm\Classes\PaymentMethods\Stripe;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\GeneralSettings;
use WPPayForm\Classes\Models\Forms;
use WPPayForm\Classes\Models\OrderItem;
use WPPayForm\Classes\Models\Submission;
use WPPayForm\Classes\Models\SubmissionActivity;
use WPPayForm\Classes\Models\Subscription;
use WPPayForm\Classes\Models\SubscriptionTransaction;
use WPPayForm\Classes\Models\Transaction;
use WPPayForm\Classes\SubmissionHandler;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Stripe Hosted Checkout Payments
 * @since 1.3.0
 */
class StripeHostedHandler extends StripeHandler
{
    public $paymentMethod = 'stripe_hosted';

    public function registerHooks()
    {
        add_filter('wppayform/form_submission_make_payment_' . $this->paymentMethod, array($this, 'redirectToStripe'), 10, 5);
        add_action('wppayform/frameless_pre_render_page_stripe_hosted_success', array($this, 'markPaymentSuccess'), 10, 1);
        add_action('wppayform/frameless_body_stripe_hosted_success', array($this, 'showSuccessMessage'), 10, 1);
    }

    /*
     * This payment method is bit easy than inline stripe
     * As Stripe handle all the things. We have to just feed the right data and
     * make the redirection. Then we will be done here.
     *
     */
    public function redirectToStripe($transactionId, $submissionId, $form_data, $form, $hasSubscriptions)
    {
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId);

        $cancelUrl = site_url() . '?wpf_page=frameless&wpf_action=stripe_hosted_cancel&wpf_hash=' . $submission->submission_hash;
        $successUrl = site_url() . '?wpf_page=frameless&wpf_action=stripe_hosted_success&wpf_hash=' . $submission->submission_hash;

        $paymentMethodElements = Forms::getPaymentMethodElements($form->ID);

        $requireBilling = ArrayHelper::get($paymentMethodElements, 'stripe_card_element.options.checkout_display_style.require_billing_info') == 'yes';

        $checkoutArgs = [
            'cancel_url'                 => $cancelUrl,
            'success_url'                => $successUrl,
            'payment_method_types'       => ['card'],
            'client_reference_id'        => $submissionId,
            'billing_address_collection' => 'required'
        ];

        if ($requireBilling) {
            $checkoutArgs['billing_address_collection'] = 'required';
        } else {
            $checkoutArgs['billing_address_collection'] = 'auto';
        }

        if ($submission->customer_email) {
            $checkoutArgs['customer_email'] = $submission->customer_email;
        }

        if ($lineItems = $this->getLineItems($submission)) {
            $checkoutArgs['line_items'] = $lineItems;
        }

        if ($hasSubscriptions) {
            $subscriptionArgs = $this->getSubscriptionArgs($submission);
            if ($subscriptionArgs) {
                $checkoutArgs['subscription_data'] = $subscriptionArgs;
            }
        }

        if (!isset($checkoutArgs['subscription_data'])) {
            $checkoutArgs['submit_type'] = 'auto';
            $checkoutArgs['payment_intent_data'] = [
                'capture_method' => 'automatic',
                'description'    => $form->post_title,
            ];
        }

        $checkoutArgs = apply_filters('wppayform/stripe_checkout_session_args', $checkoutArgs, $submission);
        $checkoutSession = CheckoutSession::create($checkoutArgs);

        if ($checkoutSession->error) {
            wp_send_json_error([
                'message'       => $checkoutSession->error->message,
                'payment_error' => true
            ], 423);
        }

        $paymentIntent = $checkoutSession->id;
        $transactionModel = new Transaction();
        $transactionModel->update($transactionId, array(
            'status'       => 'intented',
            'payment_mode' => $this->getMode()
        ));

        $submissionModel->updateMeta($submission->id, 'stripe_intended_session', $paymentIntent);

        // Redirect to
        wp_send_json_success([
            'message'          => __('Please wait... You are redirecting to Secure Payment page powered by Stripe', 'wppayform'),
            'call_next_method' => 'stripeRedirectToCheckout',
            'session_id'       => $checkoutSession->id
        ], 200);

    }

    private function getLineItems($submission)
    {
        $orderItemsModel = new OrderItem();
        $items = $orderItemsModel->getOrderItems($submission->id);
        $formattedItems = [];
        foreach ($items as $item) {
            $price = $item->item_price;
            if (GeneralSettings::isZeroDecimal($submission->currency)) {
                $price = intval($price / 100);
            }

            $formattedItems[] = [
                'amount'   => $price,
                'currency' => $submission->currency,
                'name'     => $item->item_name,
                'quantity' => ($item->quantity) ? $item->quantity : 1,
            ];
        }
        return $formattedItems;
    }

    private function getSubscriptionArgs($submission)
    {
        $subscriptionModel = new Subscription();
        $subscriptions = $subscriptionModel->getSubscriptions($submission->id);

        if (!$subscriptions) {
            return [];
        }

        $subscriptionItems = [];

        $maxTrialDays = 0;

        foreach ($subscriptions as $subscriptionItem) {
            if ($subscriptionItem->trial_days && $maxTrialDays < $subscriptionItem->trial_days) {
                $maxTrialDays = $subscriptionItem->trial_days;
                $subscriptionItem->trial_days = 0;
            }
            $subscription = Plan::getOrCreatePlan($subscriptionItem, $submission);
            $subscriptionItems[] = [
                'plan'     => $subscription->id,
                'quantity' => ($subscriptionItem->quantity) ? $subscriptionItem->quantity : 1
            ];
            $subscriptionModel->update($subscriptionItem->id, [
                'status'          => 'intented',
                'vendor_plan_id'  => $subscription->id,
                'vendor_response' => maybe_serialize($subscription),
            ]);
        }

        $args = [];
        if ($subscriptionItems) {
            $args = [
                'items' => $subscriptionItems
            ];
            if ($maxTrialDays) {
                $subscriptionModel->updateBySubmissionId($submission->id, [
                    'trial_days' => $maxTrialDays,
                    'updated_at' => gmdate('Y-m-d H:i:s')
                ]);
                $args['trial_period_days'] = $maxTrialDays;
            }
        }

        return $args;
    }

    /*
     * This function will be called after stripe hosted payment success
     * It's basically called by the frameless page action
     */
    public function markPaymentSuccess($action = '')
    {
        $submissionHash = sanitize_text_field($_REQUEST['wpf_hash']);
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmissionByHash($submissionHash);

        if (!$submission) {
            return;
        }

        // Payment Status pending so let's try to make the payment now
        //   print_r($submission);
        $sessionId = $submissionModel->getMeta($submission->id, 'stripe_intended_session');

        $session = CheckoutSession::retrive($sessionId, [
            'expand' => [
                'subscription.latest_invoice',
                'payment_intent'
            ]
        ]);


        if (!$session || !$session->customer) {
            // For failed payment customer will not exist
            return;
        }

        // Check If the hooks already fired and data updated
        if ($submissionModel->getMeta($submission->id, 'stripe_checkout_hooked_fired') == 'yes') {
              return;
        }

        // Collect the Onetime not-paid transation and intented transactions
        $transactionModel = new Transaction();
        $intentedOneTimeTransaction = $transactionModel->getLatestIntentedTransaction($submission->id);

        // Handle One time payment success
        if ($intentedOneTimeTransaction) {
            $this->processOnetimeIntentedSuccess($intentedOneTimeTransaction, $session, $submission);
            $submission = $submissionModel->getSubmission($submission->id); // We are just getting the latest data
        }

        /*
        * Handle Subscription Transaction Entry on Success
        * First of all we have to check if this submission has any subscription payment
        */
        // Lets fetch the subscription for this submission
        $subscriptionModel = new Subscription();
        $intentedSubscriptions = $subscriptionModel->getIntentedSubscriptions($submission->id);
        if ($intentedSubscriptions) {
            $this->processSubscriptionsItentSuccess($intentedSubscriptions, $session->subscription, $submission);
        }

        $submissionModel->updateMeta($submission->id, 'stripe_checkout_hooked_fired', 'yes');
        // Fire Action Hooks to make the payment
        $submissionModel->update($submission->id, [
            'payment_status' => 'paid',
            'payment_method' => 'stripe',
            'payment_mode'   => $this->getMode()
        ]);

        $submission = $submissionModel->getSubmission($submission->id);

        if ($intentedOneTimeTransaction) {
            $transaction = $transactionModel->getTransaction($intentedOneTimeTransaction->id);
            do_action('wppayform/form_payment_success_stripe', $submission, $transaction, $submission->form_id, $session);
            do_action('wppayform/form_payment_success', $submission, $transaction, $submission->form_id, $session);
        }

        if ($intentedSubscriptions) {
            $subscriptions = $subscriptionModel->getSubscriptions($submission->id);
            do_action('wppayform/form_recurring_subscribed_stripe', $submission, $subscriptions, $submission->form_id);
            do_action('wppayform/form_recurring_subscribed', $submission, $subscriptions, $submission->form_id);
        }
    }

    /*
     * This method will be called if stripe hosted checkout page made a payment
     * which has one time payment only or one time payment with a subscription payment
     */
    public function processOnetimeIntentedSuccess($transaction, $session, $submission)
    {
        $updateDate = [
            'status' => 'paid'
        ];
        if ($session->payment_intent) {
            // This is mostly for only one time payment. If subscription payment exists
            // Then we will not get charge and payment itent which is annoying
            if (!empty($session->payment_intent->charges->data[0])) {
                $charge = $session->payment_intent->charges->data[0];
                $updateDate['charge_id'] = $charge->id;

                if (!empty($charge->payment_method_details->card)) {
                    $card = $charge->payment_method_details->card;
                    $updateDate['card_brand'] = $card->brand;
                    $updateDate['card_last_4'] = $card->last4;
                }
                if (!empty($charge->billing_details->address)) {
                    $billingDetails = $charge->billing_details;

                    $formDataFormatted = $submission->form_data_formatted;
                    $formDataFormatted['__stripe_address'] = $billingDetails->address;
                    $submissionUpdateData = [
                        'form_data_formatted' => maybe_serialize($formDataFormatted)
                    ];
                    if (!$submission->customer_name && $billingDetails->name) {
                        $submissionUpdateData['customer_name'] = $billingDetails->name;
                    }
                    if (!$submission->customer_email && $billingDetails->email) {
                        $submissionUpdateData['customer_email'] = $billingDetails->email;
                    }
                    $submissionModel = new Submission();
                    $submissionModel->update($submission->id, $submissionUpdateData);
                }

            } else {
                $updateDate['charge_id'] = $session->payment_intent->id;
                $updateDate['created_at'] = gmdate('Y-m-d H:i:s', $session->payment_intent->created);
            }
        } else if (!empty($session->subscription->latest_invoice->charge)) {
            $updateDate['charge_id'] = $session->subscription->latest_invoice->charge;
            $updateDate['created_at'] = gmdate('Y-m-d H:i:s', $session->subscription->latest_invoice->date);
        }

        $transactionModel = new Transaction();
        $transactionModel->update($transaction->id, $updateDate);

        SubmissionActivity::createActivity(array(
            'form_id'       => $submission->form_id,
            'submission_id' => $submission->id,
            'type'          => 'activity',
            'created_by'    => 'PayForm BOT',
            'content'       => __('Stripe One time payment has marked as paid.', 'wppayform')
        ));
    }

    /*
     * This method will be called if stripe hosted checkout
     * has subscription payment
   */
    public function processSubscriptionsItentSuccess($subscriptions, $stripeResponse, $submission)
    {
        $subscriptionModel = new Subscription();
        $subscriptionTransactionModel = new SubscriptionTransaction();

        foreach ($subscriptions as $subscription) {
            $subscriptionStatus = 'active';
            if ($subscription->trial_days) {
                $subscriptionStatus = 'trialling';
            }

            $subscriptionModel->update($subscription->id, [
                'status'                 => $subscriptionStatus,
                'vendor_customer_id'     => $stripeResponse->customer,
                'vendor_subscriptipn_id' => $stripeResponse->id,
                'vendor_plan_id'         => $subscription->vendor_plan_id,
                'vendor_response'        => maybe_serialize($stripeResponse),
            ]);

            if($subscriptionStatus == 'trialling') {
                continue;
            }

            $totalAmount = $subscription->initial_amount + $subscription->recurring_amount;
            // We have to calculate the payment total

            $transactionItem = [
                'form_id'          => $submission->form_id,
                'user_id'          => $submission->user_id,
                'submission_id'    => $submission->id,
                'subscription_id'  => $subscription->id,
                'transaction_type' => 'subscription',
                'payment_method'   => 'stripe',
                'charge_id'        => $stripeResponse->latest_invoice->charge,
                'payment_total'    => $totalAmount,
                'status'           => $stripeResponse->latest_invoice->status,
                'currency'         => $stripeResponse->latest_invoice->currency,
                'payment_mode'     => ($stripeResponse->latest_invoice->livemode) ? 'live' : 'test',
                'payment_note'     => maybe_serialize($stripeResponse->latest_invoice),
                'created_at'       => gmdate('Y-m-d H:i:s', $stripeResponse->created),
                'updated_at'       => gmdate('Y-m-d H:i:s', $stripeResponse->created)
            ];

            $subscriptionTransactionModel->maybeInsertCharge($transactionItem);
        }

        SubmissionActivity::createActivity(array(
            'form_id'       => $submission->form_id,
            'submission_id' => $submission->id,
            'type'          => 'activity',
            'created_by'    => 'PayForm BOT',
            'content'       => __('Stripe recurring subscription successfully initiated', 'wppayform')
        ));
    }

    public function showSuccessMessage($action)
    {
        $submissionHash = sanitize_text_field($_REQUEST['wpf_hash']);
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmissionByHash($submissionHash);

        if (!$submission) {
            echo __('Sorry! no associate submission found', 'wppayform');
            return;
        }
        $submissionHandler = new SubmissionHandler();
        $confirmation = $submissionHandler->getFormConfirmation($submission->form_id, $submission);

        if ($confirmation['redirectTo'] == 'customUrl' && $confirmation['customUrl']) {
            echo '<script type="text/javascript">window.location.href = "' . $confirmation['customUrl'] . '";</script>';
            return;
        }
        $title = __('Payment has been successfully completed', 'wppayform');

        $paymentHeader = apply_filters('wppayform/payment_success_title', $title, $submission);
        echo '<div class="frameless_body_header">' . $paymentHeader . '</div>';
        echo $confirmation['messageToShow'];
        return;
    }

}
