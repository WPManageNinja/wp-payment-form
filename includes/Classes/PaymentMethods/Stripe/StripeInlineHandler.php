<?php

namespace WPPayForm\Classes\PaymentMethods\Stripe;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\GeneralSettings;
use WPPayForm\Classes\Models\Forms;
use WPPayForm\Classes\Models\Submission;
use WPPayForm\Classes\Models\SubmissionActivity;
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
            $setupIntent = SCA::setupIntent([
                'usage' => 'off_session'
            ]);

            wp_send_json_success([
                'call_next_method' => 'stripeSetupItent',
                'submission_id'    => $submissionId,
                'customer_name' => $submission->customer_name,
                'customer_email' => $submission->customer_email,
                'client_secret'    => $setupIntent->client_secret,
                'message'          => __('Verifying your card details. Please wait...', 'wppayform')
            ], 200);
        }

        $paymentMethodId = ArrayHelper::get($form_data, '__stripe_payment_method_id');

        // Let's create the one time payment first
        // We will handle One-Time Payment Here only
        $intentArgs = [
            'payment_method'      => $paymentMethodId,
            'amount'              => $transaction->payment_total,
            'currency'            => $transaction->currency,
            'confirmation_method' => 'manual',
            'confirm'             => 'true',
            'description'         => $form->post_title,
            'metadata'            => $this->getIntentMetaData($submission)
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

        if (!$confirmation->error && $confirmation->status == 'succeeded') {
            $this->handlePaymentSuccess($confirmation, $transaction, $submission, 'confirmation');
        } else {
            $form = Forms::getForm($submission->form_id);
            $message = 'Payment has been failed. '.$confirmation->error->message;
            return $this->handlePaymentChargeError($message, $submission, $form, $confirmation, 'payment_error');
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

        $intent = SCA::createPaymentIntent($intentArgs);

        if ($intent->status == 'requires_action' &&
            $intent->next_action->type == 'use_stripe_sdk') {
            # Tell the client to handle the action
            $transactionModel = new Transaction();
            $transactionModel->update($transaction->id, array(
                'status'       => 'intended',
                'charge_id'    => $intent->id,
                'payment_mode' => $this->getMode()
            ));

            SubmissionActivity::createActivity(array(
                'form_id'       => $submission->form_id,
                'submission_id' => $submission->id,
                'type'          => 'activity',
                'created_by'    => 'PayForm BOT',
                'content'       => __('SCA is required for this payment. Payment status changed to pending to intended', 'wppayform')
            ));

            SubmissionActivity::createActivity(array(
                'form_id'       => $submission->form_id,
                'submission_id' => $submission->id,
                'type'          => 'activity',
                'created_by'    => 'PayForm BOT',
                'content'       => __('SCA is required for this payment. Requested SCA info from customer', 'wppayform')
            ));

            wp_send_json_success([
                'call_next_method'                    => 'initStripeSCAModal',
                'submission_id'                       => $submission->id,
                'stripe_payment_intent_client_secret' => $intent->client_secret,
                'message'                             => __('Strong Customer Authentication is required. Please complete 2 factor authentication.', 'wppayform')
            ], 200);

        } else if ($intent->status == 'succeeded') {
            // Payment is succcess here
            return  $this->handlePaymentSuccess($intent, $transaction, $submission);
        } else {
            wp_send_json_error(array(
                'message'       => __('Payment Failed! Invalid PaymentIntent status', 'wppayform'),
                'payment_error' => true
            ), 423);
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
            'status'         => 'paid',
            'charge_id'      => $charge->id,
            'payment_method' => $this->getMode(),
            'payment_mode'   => $paymentMode,
        ));

        $submissionUpdateData = array(
            'payment_status' => 'paid',
            'payment_method' => 'stripe',
            'payment_mode'   => $paymentMode,
        );
        $submissionModel = new Submission();
        $submissionModel->update($submission->id, $submissionUpdateData);

        SubmissionActivity::createActivity(array(
            'form_id'       => $submission->form_id,
            'submission_id' => $submission->id,
            'type'          => 'activity',
            'created_by'    => 'PayForm BOT',
            'content'       => __('One time Payment Successfully made via stripe. Charge ID: ', 'wppayform') . $charge->id
        ));

        SubmissionActivity::createActivity(array(
            'form_id'       => $submission->form_id,
            'submission_id' => $submission->id,
            'type'          => 'activity',
            'created_by'    => 'PayForm BOT',
            'content'       => __('Payment status changed from pending to paid', 'wppayform')
        ));

        return true;
    }

    /*
     * Step 2 for Subscrion Payment where we have to create the customer and make the payments and subscriptions
     *
     * This function calls from client side function stripeSetupItent. We have PM now to create payment_intents confirmation
     *
     * Steps:
     *     1. Create Customer using provided PM from Client Side
     *     2. If there has $subscribedItems then call handleSubscriptions to handle the subscription payment
     *     3. If there has single payments then call handlePaymentItentCharge [which is step 2 again]
     *     4. Now finally fire the hooks and send confirmations using SubmissionHandler class
     */
    public function confirmScaSetupIntentsPayment()
    {
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($_REQUEST['submission_id']);
        do_action('wppayform/form_submission_activity_start', $submission->form_id);

        $paymentMethod = $_REQUEST['payment_method'];
        $form = Forms::getForm($submission->form_id);

        $customerArgs = [
            'payment_method'   => $paymentMethod,
            'invoice_settings' => [
                'default_payment_method' => $paymentMethod
            ],
            'metadata'         => [
                'payform_id'    => $submission->form_id,
                'submission_id' => $submission->id,
                'form_name'     => $form->post_title
            ]
        ];

        if ($submission->customer_email) {
            $customerArgs['email'] = $submission->customer_email;
            $customerArgs['description'] = $submission->customer_email;
        }
        if ($submission->customer_name) {
            $customerArgs['name'] = $submission->customer_name;
        }
        $customer = Customer::createCustomer($customerArgs);

        /*
         * It's same as old api now
         * Do whatever you want with this now
         */
        $subscribedItems = $this->handleInlineSubscriptions($customer->id, $submission, $form);


        // handle error for subscribed items
        if ($subscribedItems && is_wp_error($subscribedItems)) {
            $errorCode = $subscribedItems->get_error_code();
            $message = $subscribedItems->get_error_message($errorCode);
            return $this->handlePaymentChargeError($message, $submission, false, $form, false, 'charge');
        }

        $transactionModel = new Transaction();
        $transaction = $transactionModel->getLatestTransaction($submission->id);

        if ($transaction) {
            $intendArgs = [
                'payment_method'      => $paymentMethod,
                'customer'            => $customer->id,
                'amount'              => $transaction->payment_total,
                'currency'            => $transaction->currency,
                'confirmation_method' => 'manual',
                'confirm'             => 'true',
                'off_session'         => 'true',
                'description'         => $form->post_title,
                'metadata'            => $this->getIntentMetaData($submission)
            ];
            $this->handlePaymentItentCharge($transaction, $submission, $intendArgs);
            $transaction = $transactionModel->getLatestTransaction($submission->id);
        }

        $submission = $submissionModel->getSubmission($submission->id);

        do_action('wppayform/form_payment_success', $submission, $transaction, $submission->form_id, false);
        do_action('wppayform/form_payment_success_stripe', $submission, $transaction, $submission->form_id, false);

        if ($subscribedItems) {
            do_action('wppayform/form_recurring_subscribed', $submission, $subscribedItems, $submission->form_id);
            do_action('wppayform/form_recurring_subscribed_stripe', $submission, $subscribedItems, $submission->form_id);
        }

        $formHandler = new SubmissionHandler();
        $formHandler->sendSubmissionConfirmation($submission, $submission->form_id);
    }

    private function getIntentMetaData($submission)
    {
        $metadata = [
            'Submission ID' => $submission->id,
            'Form ID'       => $submission->form_id
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
