<?php

namespace WPPayForm\Classes\PaymentMethods\Stripe;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\GeneralSettings;
use WPPayForm\Classes\Models\Forms;
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
 * Handle Stripe Inline Card Payments
 * @since 1.3.0
 */
class StripeInlineHandler extends StripeHandler
{
    public $paymentMethod = 'stripe_inline';

    public function registerHooks()
    {
        add_filter('wppayform/wpf_before_submission_data_insert_' . $this->paymentMethod, array($this, 'validateMethodId'), 10, 4);
        add_filter('wppayform/form_submission_make_payment_' . $this->paymentMethod, array($this, 'makePaymentIntend'), 10, 5);
        add_action('wp_ajax_wppayform_sca_inline_confirm_payment', array($this, 'confirmScaPayment'));
        add_action('wp_ajax_nopriv_wppayform_sca_inline_confirm_payment', array($this, 'confirmScaPayment'));


        add_action('wp_ajax_wppayform_sca_inline_confirm_payment_setup_intents', array($this, 'confirmScaSetupIntentsPayment'));
        add_action('wp_ajax_nopriv_wppayform_sca_inline_confirm_payment_setup_intents', array($this, 'confirmScaSetupIntentsPayment'));


    }

    public function validateMethodId($submission, $form_data, $paymentItems, $subscriptionItems)
    {
        $paymentMethodId = ArrayHelper::get($form_data, '__stripe_payment_method_id');
        if (!$paymentMethodId) {
            wp_send_json_error([
                'message'    => __('Card token is not available. Please try again'),
                'card_error' => true
            ]);
            exit();
        }
    }

    public function makePaymentIntend($transactionId, $submissionId, $form_data, $form, $hasSubscriptions)
    {
        $transactionModel = new Transaction();
        $transaction = $transactionModel->getTransaction($transactionId);

        $hasTransaction = $transaction && $transaction->payment_total;

        if (!$hasTransaction && !$hasSubscriptions) {
            return;
        }

        $paymentMethodId = ArrayHelper::get($form_data, '__stripe_payment_method_id');
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId);


        if ($hasSubscriptions) {
            // @todo: We must have to handle subscription payment here
            // We will get back to here. Trust Me!!!
            $setupIntent = SCA::setupIntent([
                'usage' => 'off_session'
            ]);

            wp_send_json_success([
                'call_next_method' => 'stripeSetupItent',
                'submission_id'    => $submissionId,
                'client_secret'    => $setupIntent->client_secret,
                'message'          => __('Verifying your card details. Please wait...', 'wppayform')
            ], 200);
        }

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

        return $this->handlePaymentItentCharge($transaction, $submission, $intentArgs);
    }

    public function confirmScaPayment()
    {
        $formId = intval($_REQUEST['form_id']);
        $submissionId = intval($_REQUEST['submission_id']);
        $type = sanitize_text_field($_REQUEST['type']);
        $paymentMethod = sanitize_text_field($_REQUEST['payment_method']);
        $paymentMethodId = sanitize_text_field($_REQUEST['payemnt_method_id']);
        $intentId = sanitize_text_field($_REQUEST['payment_intent_id']);
        $form = Forms::getForm($formId);
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId);
        $transactionModel = new Transaction();

        $paymentIntentId = sanitize_text_field($_REQUEST['payment_intent_id']);

        $transaction = $transactionModel->getLatestIntentedTransaction($submissionId);


        do_action('wppayform/form_submission_activity_start', $submission->form_id);

        $confirmation = SCA::confirmPayment($paymentIntentId, [
            'payment_method' => $paymentMethod
        ]);

        if (!$confirmation->error && $confirmation->status == 'succeeded') {
            $this->handlePaymentSuccess($confirmation, $transaction, $submission, 'confirmation');
        } else {
            SubmissionActivity::createActivity(array(
                'form_id'       => $submission->form_id,
                'submission_id' => $submission->id,
                'type'          => 'activity',
                'created_by'    => 'PayForm BOT',
                'content'       => __('Payment confirmation failed. Stripe Error: ', 'wppayform') . $confirmation->error->message
            ));

            wp_send_json_error([
                'message'         => $confirmation->error->message,
                'vendor_response' => $confirmation
            ], 423);
        }

        $formHandler = new SubmissionHandler();
        $formHandler->sendSubmissionConfirmation($submission, $formId);
    }

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
        $stripe = new Stripe();
        $subscribedItems = $stripe->handleSubscriptions($customer->id, $submission, $form);

        // handle error for subscribed items
        if ($subscribedItems && is_wp_error($subscribedItems)) {
            $errorCode = $subscribedItems->get_error_code();
            $message = $subscribedItems->get_error_message($errorCode);
            return $stripe->handlePaymentChargeError($message, $submission, false, $form, false, 'charge');
        }

        $transactionModel = new Transaction();
        $transaction = $transactionModel->getLatestTransaction($submission->id);
        $charge = false;

        if($transaction) {
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
        }

        $submission = $submissionModel->getSubmission($submission->id);
        $transaction = $transactionModel->getLatestTransaction($submission->id);

        do_action('wppayform/form_payment_success_stripe', $submission, $transaction, $submission->form_id, $charge);
        do_action('wppayform/form_payment_success', $submission, $transaction, $submission->form_id, $charge);

        if($subscribedItems) {
            do_action('wppayform/form_recurring_subscribed_stripe', $submission, $subscribedItems, $submission->form_id);
            do_action('wppayform/form_recurring_subscribed', $submission, $subscribedItems, $submission->form_id);
        }

        $formHandler = new SubmissionHandler();
        $formHandler->sendSubmissionConfirmation($submission, $submission->form_id);
    }

    public function processInvoicePayment($submission, $customerId)
    {
        $form = Forms::getForm($submission->form_id);
        // Let's create the invoice items for this customer first
        $onetimeItem = SCA::createInvoiceItem([
            'currency'    => 'usd',
            'customer'    => $customerId,
            'amount'      => 1000,
            'description' => 'One time payment item for ' . $form->post_title
        ]);

        $subscriptionPaymentItem = SCA::createInvoiceItem([
            'currency'     => 'usd',
            'customer'     => $customerId,
            'subscription' => 'wpf_415_recurring_payment_item_1_9999_month_0_USD'
        ]);

        $invoice = SCA::createInvoice([
            'customer'          => $customerId,
            'collection_method' => 'charge_automatically',
            'description'       => 'Invoice for ' . $form->post_title
        ]);

        print_r($subscriptionPaymentItem);
        print_r($invoice);
        die();

    }

    public function handlePaymentItentCharge($transaction, $submission, $intentArgs)
    {
        if (GeneralSettings::isZeroDecimal($transaction->currency)) {
            $intentArgs['amount'] = intval($transaction->payment_total / 100);
        }

        $intent = SCA::createPaymentIntent($intentArgs);

        if ($intent->status == 'requires_source_action' &&
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
                'stripe_requires_action'              => true,
                'submission_id'                       => $submission->id,
                'stripe_payment_intent_client_secret' => $intent->client_secret,
                'message'                             => __('Strong Customer Authentication is required. Please complete 2 factor authentication.', 'wppayform')
            ], 200);
        } else if ($intent->status == 'succeeded') {
            // Payment is succcess here
            return $this->handlePaymentSuccess($intent, $transaction, $submission);
        } else {
            wp_send_json_error(array(
                'message'       => __('Payment Failed! Invalid PaymentIntent status', 'wppayform'),
                'payment_error' => true
            ), 423);
        }
    }

    public function handlePaymentSuccess($intend, $transaction, $submission, $type = 'intend')
    {
        $paymentMode = $this->getMode();
        $transactionModel = new Transaction();
        $transactionModel->update($transaction->id, array(
            'status'         => 'paid',
            'charge_id'      => $intend->id,
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
            'content'       => __('One time Payment Successfully made via stripe. Paymeny Intend ID: ', 'wppayform') . $intend->id
        ));

        SubmissionActivity::createActivity(array(
            'form_id'       => $submission->form_id,
            'submission_id' => $submission->id,
            'type'          => 'activity',
            'created_by'    => 'PayForm BOT',
            'content'       => __('Payment status changed from pending to paid', 'wppayform')
        ));

        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submission->id);
        $transaction = $transactionModel->getTransaction($transaction->id);

        do_action('wppayform/form_payment_success_stripe', $submission, $transaction, $submission->form_id, $intend, $type);
        do_action('wppayform/form_payment_success', $submission, $transaction, $submission->form_id, $intend, $type);

    }


    private function getIntentMetaData($submission)
    {
        $metadata = [
            'Submission ID' => $submission->id,
            'Form ID'       => $submission->form_id
        ];
        if ($submission->customer_email) {
            $paymentArgs['receipt_email'] = $submission->customer_email;
            $metadata['customer_email'] = $submission->customer_email;
        }
        if ($submission->customer_name) {
            $metadata['customer_name'] = $submission->customer_name;
        }

        return apply_filters('wppayform/stripe_onetime_payment_metadata', $metadata, $submission);
    }
}
