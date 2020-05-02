<?php

namespace WPPayForm\Classes\PaymentMethods\Stripe;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\GeneralSettings;
use WPPayForm\Classes\Models\Forms;
use WPPayForm\Classes\Models\Submission;
use WPPayForm\Classes\Models\SubmissionActivity;
use WPPayForm\Classes\Models\Subscription;
use WPPayForm\Classes\Models\Transaction;
use WPPayForm\Classes\SubmissionHandler;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Stripe Inline Card Payments
 * @since 1.3.0
 */
class StripeInlineHandler extends StripeHandler
{
    public $paymentMethod = 'stripe_inline';

    public function registerHooks()
    {
        /*
         * After form submission this hooks fire to start Making payment
         */
        add_filter('wppayform/form_submission_make_payment_stripe_inline', array($this, 'makePaymentIntent'), 10, 5);

        /*
         * Mainly for single payment items
         */
        add_action('wp_ajax_wppayform_sca_inline_confirm_payment', array($this, 'confirmScaPayment'));
        add_action('wp_ajax_nopriv_wppayform_sca_inline_confirm_payment', array($this, 'confirmScaPayment'));

        /*
         * For Subscription payment + maybe single payment items
         */
        add_action('wp_ajax_wppayform_sca_inline_confirm_payment_setup_intents', array($this, 'confirmScaSetupIntentsPayment'));
        add_action('wp_ajax_nopriv_wppayform_sca_inline_confirm_payment_setup_intents', array($this, 'confirmScaSetupIntentsPayment'));

    }

    /*
     *
     * Step: 1 - AJAX EndPoint Here
     * In this step, We are creating a payment intent
     * Steps:
     *     1. If $hasSubscriptions creating setupIntent and let frontend to handle
     *     2. IF Single Payment then we creating $intentArgs and let @handlePaymentItentCharge
     *        to handle the payment.
     *     3. We are not handling anything else here
     */
    public function makePaymentIntent($transactionId, $submissionId, $form_data, $form, $hasSubscriptions)
    {
        $transactionModel = new Transaction();
        $transaction = $transactionModel->getTransaction($transactionId);

        $hasTransaction = $transaction && $transaction->payment_total;

        if (!$hasTransaction && !$hasSubscriptions) {
            return;
        }

        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId);


        if ($hasSubscriptions) {
            $this->handleSetupIntent($submission, $form_data);
        }

        $paymentMethodId = ArrayHelper::get($form_data, '__stripe_payment_method_id');


        // Let's create the one time payment first
        // We will handle One-Time Payment Here only
        $intentArgs = [
            'payment_method' => $paymentMethodId,
            'amount' => $transaction->payment_total,
            'currency' => $transaction->currency,
            'confirmation_method' => 'manual',
            'confirm' => 'true',
            'description' => $form->post_title,
            'metadata' => $this->getIntentMetaData($submission)
        ];

        $this->handlePaymentItentCharge($transaction, $submission, $intentArgs);

        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submission->id);
        $transactionModel = new Transaction();
        $transaction = $transactionModel->getTransactions($transaction->id);
        do_action('wppayform/form_payment_success', $submission, $transaction, $submission->form_id, false);
        do_action('wppayform/form_payment_success_stripe', $submission, $transaction, $submission->form_id, false);
        return true;
    }

    public function handleSetupIntent($submission, $formData)
    {
        $paymentMethodId = ArrayHelper::get($formData, '__stripe_payment_method_id');

        $form = Forms::getForm($submission->form_id);
        /*
         * Step 1 Create the customer first
         */
        $customerArgs = [
            'payment_method' => $paymentMethodId,
            'invoice_settings' => [
                'default_payment_method' => $paymentMethodId
            ],
            'metadata' => [
                'payform_id' => $submission->form_id,
                'submission_id' => $submission->id,
                'form_name' => strip_tags($form->post_title)
            ]
        ];
        if ($submission->customer_email) {
            $customerArgs['email'] = $submission->customer_email;
            $customerArgs['description'] = $submission->customer_email;
        }
        if ($submission->customer_name) {
            $customerArgs['name'] = $submission->customer_name;
            $customerArgs['description'] = $submission->customer_name;
        }

        $customer = Customer::createCustomer($customerArgs);
        $transaction = (new Transaction())->getLatestTransaction($submission->id);

        if (is_wp_error($customer)) {
            $this->handlePaymentChargeError($customer->get_error_message(), $submission, $transaction, $form, false, 'customer');
        }
        if ($transaction) {
            Invoice::createItem([
                'currency' => $transaction->currency,
                'customer' => $customer->id,
                'amount' => $transaction->payment_total,
                'description' => $form->post_title
            ]);
        }

        $subscriptionPlans = (new StripeHandler())->getSubmissionPlans($submission);

        $items = [];
        foreach ($subscriptionPlans as $subscriptionPlan) {
            $items[] = [
                'plan' => $subscriptionPlan['plan_id'],
                'quantity' => $subscriptionPlan['quantity'],
                'metadata' => [
                    'submission_id' => $submission->id
                ]
            ];
        }

        $subscriptionArgs = [
            'customer' => $customer->id,
            'items' => $items,
            'metadata' => (new StripeHostedHandler())->getIntentMetaData($submission)
        ];

        if (count($subscriptionPlans) == 1) {
            $plan = $subscriptionPlans[0];
            if (!empty($plan['trial_expiration_at'])) {
                $subscriptionArgs['trial_end'] = $plan['trial_expiration_at'];
            }
            if (!empty($plan['subscription_cancel_at'])) {
                $subscriptionArgs['cancel_at'] = $plan['subscription_cancel_at'];
            }
        }

        $stripeSubscription = PlanSubscription::subscribe($subscriptionArgs);


        if (is_wp_error($stripeSubscription)) {
            $this->handlePaymentChargeError($stripeSubscription->get_error_message(), $submission, $transaction, $form, false, 'subscription');
        }

        $invoice = Invoice::retrive($stripeSubscription->latest_invoice, [
            'expand' => ['payment_intent']
        ]);

        if (is_wp_error($invoice)) {
            $this->handlePaymentChargeError($invoice->get_error_message(), $submission, $transaction, $form, false, 'invoice');
        }


        if ($invoice->payment_intent && $invoice->payment_intent->status == 'requires_action' &&
            $invoice->payment_intent->next_action->type == 'use_stripe_sdk') {
            // We need to factor authentication now
            wp_send_json_success([
                'stripe_subscription_id' => $stripeSubscription->id,
                'payment_method_id' => $paymentMethodId,
                'call_next_method' => 'stripeSetupItent',
                'intent' => $invoice->payment_intent,
                'submission_id' => $submission->id,
                'customer_name' => $submission->customer_name,
                'customer_email' => $submission->customer_email,
                'client_secret' => $invoice->payment_intent->client_secret,
                'message' => __('Verifying your card details. Please wait...', 'wppayform')
            ], 200);
        }
        // now this payment is successful. We don't need anything else
        $this->handlePaidSubscriptionInvoice($invoice, $submission);
    }

    /*
     * This is the next call for handleSetupIntent
     * */
    public function confirmScaSetupIntentsPayment()
    {
        $submissionId = intval($_REQUEST['submission_id']);
        $intentId = sanitize_text_field($_REQUEST['payment_intent_id']);
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId);

        do_action('wppayform/form_submission_activity_start', $submission->form_id);

        // Let's retrive the intent
        $intent = SCA::retrivePaymentIntent($intentId, [
            'expand' => [
                'invoice.payment_intent'
            ]
        ]);

        if (is_wp_error($intent)) {
            $form = Forms::getForm($submission->form_id);
            $this->handlePaymentChargeError($intent->get_error_message(), $submission, false, $form, false, 'payment_intent');
        }

        $invoice = $intent->invoice;
        $this->handlePaidSubscriptionInvoice($invoice, $submission);
    }

    public function handlePaidSubscriptionInvoice($invoice, $submission)
    {
        if ($invoice->status != 'paid') {

            wp_send_json_error([
                'message' => __('Payment Failed! Please try again', 'wppayform')
            ], 423);
        }

        // Submission status as paid
        $submissionModel = new Submission();
        $submissionModel->update($submission->id, [
            'payment_status' => 'paid',
            'payment_mode' => ($invoice->livemode) ? 'live' : 'test',
            'customer_id' => $invoice->customer,
            'updated_at' => current_time('mysql')
        ]);

        $subscriptionModel = new Subscription();
        $subscriptions = $subscriptionModel->getSubscriptions($submission->id);

        $paymentSuccessHandler = new PaymentSuccessHandler();
        $paymentSuccessHandler->processSubscriptionsSuccess($subscriptions, $invoice, $submission);

        $transactionModel = new Transaction();
        $transaction = $transactionModel->getLatestTransaction($submission->id);

        if ($transaction) {
            $paymentSuccessHandler->processOnetimeSuccess($transaction, $invoice, $submission);
        }

        $submissionModel->updateMeta($submission->id, 'stripe_checkout_hooked_fired', 'yes');

        $paymentSuccessFired = false;
        if ($transaction) {
            $transaction = $transactionModel->getTransaction($transaction->id);
            do_action('wppayform/form_payment_success_stripe', $submission, $transaction, $submission->form_id, $invoice);
            do_action('wppayform/form_payment_success', $submission, $transaction, $submission->form_id, $invoice);
            $paymentSuccessFired = true;
        }

        if ($subscriptions) {
            $subscriptions = $subscriptionModel->getSubscriptions($submission->id);
            do_action('wppayform/form_recurring_subscribed_stripe', $submission, $subscriptions, $submission->form_id);
            do_action('wppayform/form_recurring_subscribed', $submission, $subscriptions, $submission->form_id);

            if (!$paymentSuccessFired) {
                do_action('wppayform/form_payment_success_stripe', $submission, false, $submission->form_id, $invoice);
                do_action('wppayform/form_payment_success', $submission, false, $submission->form_id, $invoice);
            }
        }

        do_action('wppayform/after_form_submission_complete', $submission, $submission->form_id);

        $formHandler = new SubmissionHandler();
        $formHandler->sendSubmissionConfirmation($submission, $submission->form_id);
    }

    public function getFirstTimePaymentTotal($submission)
    {
        $subscriptions = (new Subscription())->getSubscriptions($submission->id);
        $transaction = (new Transaction())->getLatestTransaction($submission->id);

        $paymentTotal = 0;
        foreach ($subscriptions as $subscription) {
            if ($subscription->trial_days) {
                continue;
            }
            if ($submission->initial_amount) {
                $paymentTotal += $subscription->initial_amount * $subscription->quantity;
            } else {
                $paymentTotal += $subscription->recurring_amount * $subscription->quantity;
            }
        }
        if ($transaction) {
            $paymentTotal += $transaction->payment_total;
        }

        return $paymentTotal;
    }

    /*
     * AJAX EndPoint Here
     * Specially for only one time payment form
     * This function call via ajax if the form only has single payment option and requires SCA
     * This is mainly 1.2
     * Steps:
     *      1. Client (initStripeSCAModal) send us PM and intentID we use that to confirm the payment
     *      2. if confirmation succeeded then call handlePaymentSuccess() to make the submission as paid
     *      3. if fails we let the client that, We are sorry, really sorry
     *      4. Finally send confirmation message easily
     */
    public function confirmScaPayment()
    {
        $submissionId = intval($_REQUEST['submission_id']);
        $paymentMethod = sanitize_text_field($_REQUEST['payment_method']);
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId);
        $transactionModel = new Transaction();
        $paymentIntentId = sanitize_text_field($_REQUEST['payment_intent_id']);
        $transaction = $transactionModel->getLatestTransaction($submissionId);

        do_action('wppayform/form_submission_activity_start', $submission->form_id);

        $confirmation = SCA::confirmPayment($paymentIntentId, [
            'payment_method' => $paymentMethod
        ]);

        if (empty($confirmation->error) && $confirmation->status == 'succeeded') {
            $this->handlePaymentSuccess($confirmation, $transaction, $submission, 'confirmation');
        } else {
            $form = Forms::getForm($submission->form_id);
            $message = 'Payment has been failed. ' . $confirmation->error->message;
            $this->handlePaymentChargeError($message, $submission, $form, $confirmation, 'payment_error');
            return;
        }

        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submission->id);
        $transaction = $transactionModel->getTransaction($transaction->id);

        do_action('wppayform/form_payment_success_stripe', $submission, $transaction, $submission->form_id, $confirmation, 'confirmation');
        do_action('wppayform/form_payment_success', $submission, $transaction, $submission->form_id, $confirmation, 'confirmation');

        $formHandler = new SubmissionHandler();
        $formHandler->sendSubmissionConfirmation($submission, $submission->form_id);
    }

    /*
     * Step 2 for Single Payment Form
     * Step 3.1 for subscrion payment's single amount
     *
     * Steps:
     *     1. Create createPaymentIntent for SCA
     *     2. if requires 'requires_action' then response to client to open initStripeSCAModal
     *     3. if payment success then call handlePaymentSuccess to make the payment as paid
     *     4. If failed to createPaymentIntent then send error response
     */
    private function handlePaymentItentCharge($transaction, $submission, $intentArgs)
    {
        if (GeneralSettings::isZeroDecimal($transaction->currency)) {
            $intentArgs['amount'] = intval($transaction->payment_total / 100);
        }

        if ($submission->customer_email && apply_filters('wppayform/send_receipt_email', true, $submission)) {
            $intendArgs['receipt_email'] = $submission->customer_email;
        }

        $intent = SCA::createPaymentIntent($intentArgs);

        if (is_wp_error($intent)) {
            $form = Forms::getForm($submission->form_id);
            $this->handlePaymentChargeError($intent->get_error_message(), $submission, $transaction, $form, false, 'payment_intent');
        }

        if ($intent->status == 'requires_action' &&
            $intent->next_action &&
            $intent->next_action->type == 'use_stripe_sdk') {
            # Tell the client to handle the action
            $transactionModel = new Transaction();
            $transactionModel->update($transaction->id, array(
                'status' => 'intended',
                'charge_id' => $intent->id,
                'payment_mode' => $this->getMode()
            ));

            SubmissionActivity::createActivity(array(
                'form_id' => $submission->form_id,
                'submission_id' => $submission->id,
                'type' => 'activity',
                'created_by' => 'PayForm BOT',
                'content' => __('SCA is required for this payment. Payment status changed to pending to intended', 'wppayform')
            ));

            SubmissionActivity::createActivity(array(
                'form_id' => $submission->form_id,
                'submission_id' => $submission->id,
                'type' => 'activity',
                'created_by' => 'PayForm BOT',
                'content' => __('SCA is required for this payment. Requested SCA info from customer', 'wppayform')
            ));

            wp_send_json_success([
                'call_next_method' => 'initStripeSCAModal',
                'submission_id' => $submission->id,
                'stripe_payment_intent_client_secret' => $intent->client_secret,
                'message' => __('Strong Customer Authentication is required. Please complete 2 factor authentication.', 'wppayform')
            ], 200);

        } else if ($intent->status == 'succeeded') {
            // Payment is succcess here
            return $this->handlePaymentSuccess($intent, $transaction, $submission);
        } else {
            $message = __('Payment Failed! Your card may declined', 'wppayform');
            if (!empty($intent->error->message)) {
                $message = $intent->error->message;
            }
            $form = Forms::getForm($submission->form_id);
            $this->handlePaymentChargeError($message, $submission, $transaction, $form, false, 'payment_intent');
        }
    }

    /*
     * Step Final: if there has any single payment item exists
     *
     * Steps:
     *     1. Mae the single transaction and paid
     *     2. Make the submission as paid
     *     3. Fire wppayform/form_payment_success hook which is very imprtant for
     *        email notifications and other future integrations
     */
    public function handlePaymentSuccess($intend, $transaction, $submission, $type = 'intend')
    {
        $charge = $intend->charges->data[0];

        $paymentMode = $this->getMode();
        $transactionModel = new Transaction();
        $transactionModel->update($transaction->id, array(
            'status' => 'paid',
            'charge_id' => $charge->id,
            'payment_method' => 'stripe',
            'payment_mode' => $paymentMode,
        ));

        $submissionUpdateData = array(
            'payment_status' => 'paid',
            'payment_method' => 'stripe',
            'payment_mode' => $paymentMode,
        );
        $submissionModel = new Submission();
        $submissionModel->update($submission->id, $submissionUpdateData);

        SubmissionActivity::createActivity(array(
            'form_id' => $submission->form_id,
            'submission_id' => $submission->id,
            'type' => 'activity',
            'created_by' => 'PayForm BOT',
            'content' => __('One time Payment Successfully made via stripe. Charge ID: ', 'wppayform') . $charge->id
        ));

        SubmissionActivity::createActivity(array(
            'form_id' => $submission->form_id,
            'submission_id' => $submission->id,
            'type' => 'activity',
            'created_by' => 'PayForm BOT',
            'content' => __('Payment status changed from pending to paid', 'wppayform')
        ));

        return true;
    }


    private function getIntentMetaData($submission)
    {
        $metadata = [
            'Submission ID' => $submission->id,
            'Form ID' => $submission->form_id
        ];
        if ($submission->customer_email) {
            $metadata['customer_email'] = $submission->customer_email;
        }
        if ($submission->customer_name) {
            $metadata['customer_name'] = $submission->customer_name;
        }

        return apply_filters('wppayform/stripe_onetime_payment_metadata', $metadata, $submission);
    }
}
