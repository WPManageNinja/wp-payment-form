<?php

namespace WPPayForm\Classes\PaymentMethods\Stripe;

use WPPayForm\Classes\Models\Submission;
use WPPayForm\Classes\Models\SubmissionActivity;
use WPPayForm\Classes\Models\Transaction;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Stripe Specific Actions Here
 * @since 1.0.0
 */
class Stripe
{
    public function registerHooks()
    {
        // Register The Component
        new StripeCardElementComponent();

        // Register The Action and Filters
        add_filter('wpf_parse_submission', array($this, 'addAddressToView'), 10, 2);
        add_filter('wpf_form_data_formatted_input', array($this, 'pushAddressToInput'), 10, 3);
        add_action('wpf_form_submission_make_payment_stripe', array($this, 'makeFormPayment'), 10, 4);
        add_filter('wpf_form_transactions', array($this, 'addTransactionUrl'), 10, 2);
    }

    public function makeFormPayment($transactionId, $submissionId, $form_data, $form)
    {
        // @todo: We have to make it dynamic
        $paymentMode = 'test';
        $transactionModel = new Transaction();
        $transaction = $transactionModel->getTransaction($transactionId);
        $token = $form_data['stripeToken'];
        $currentUserId = get_current_user_id();

        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId);

        $paymentArgs = array(
            'currency'             => 'USD',
            'amount'               => $transaction->payment_total,
            'source'               => $token,
            'description'          => $form->post_title,
            'statement_descriptor' => $form->post_title
        );
        $metadata = array(
            'form_id'        => $form->ID,
            'user_id'        => $currentUserId,
            'submission_id'  => $submissionId,
            'wppayform_tid'  => $transactionId,
            'wp_plugin_slug' => 'wppayform'
        );
        if ($submission->customer_email) {
            $paymentArgs['receipt_email'] = $submission->customer_email;
            $metadata['customer_email'] = $submission->customer_email;
        }
        if ($submission->customer_name) {
            $metadata['customer_name'] = $submission->customer_name;
        }
        $paymentArgs['metadata'] = $metadata;
        $charge = Charge::charge($paymentArgs);

        $paymentStatus = true;

        $message = 'Unknown error';
        if (is_wp_error($charge)) {
            $paymentStatus = false;
            $errorCode = $charge->get_error_code();
            $message = $charge->get_error_message($errorCode);
        } else if (!$charge) {
            $paymentStatus = false;
        }


        if (!$paymentStatus) {
            do_action('wpf_stripe_charge_failed', $transactionId, $charge, $paymentArgs);
            do_action('wpf_form_payment_failed', $transactionId, $charge, $paymentArgs);
            $transactionModel->update($transactionId, array(
                'status'         => 'failed',
                'payment_method' => 'stripe',
                'payment_mode'   => $paymentMode,
            ));
            $submissionModel->update($submissionId, array(
                'payment_status' => 'failed',
                'payment_method' => 'stripe',
                'payment_mode'   => $paymentMode,
            ));

            SubmissionActivity::createActivity( array(
                'form_id'       => $form->ID,
                'submission_id' => $submissionId,
                'type'          => 'activity',
                'created_by'    => 'PayForm BOT',
                'content'       => 'Payment Failed via stripe. Status changed from Pending to Failed.'
            ) );

            wp_send_json_error(array(
                'message'       => $message,
                'payment_error' => true
            ), 423);
        }
        // We are good here. The charge is successfull and We are ready to go.
        $transactionModel->update($transactionId, array(
            'status'         => 'paid',
            'charge_id'      => $charge->id,
            'card_last_4'    => $charge->source->last4,
            'card_brand'     => $charge->source->brand,
            'payment_method' => 'stripe',
            'payment_mode'   => $paymentMode,
        ));
        $submissionModel->update($submissionId, array(
            'payment_status' => 'paid',
            'payment_method' => 'stripe',
            'payment_mode'   => $paymentMode,
        ));

        SubmissionActivity::createActivity( array(
            'form_id'       => $form->ID,
            'submission_id' => $submissionId,
            'type'          => 'activity',
            'created_by'    => 'PayForm BOT',
            'content'       => 'Payment status changed from pending to success'
        ) );

    }

    public function addTransactionUrl($transactions, $formId)
    {
        foreach ($transactions as $transaction) {
            if ($transaction->payment_method == 'stripe' && $transaction->charge_id) {
                $transactionUrl = 'https://dashboard.stripe.com/payments/' . $transaction->charge_id;
                $transaction->transaction_url = $transactionUrl;
            }
        }
        return $transactions;
    }

    public function pushAddressToInput($inputItems, $formData, $formId)
    {
        if (isset($formData['__stripe_address_json'])) {
            $addressDetails = $formData['__stripe_address_json'];
            $inputItems['__stripe_checkout_address_details'] = json_decode($addressDetails, true);
        }
        return $inputItems;
    }

    public function addAddressToView($parsed, $submission)
    {
        $fomattedData = $submission->form_data_formatted;
        if (isset($fomattedData['__stripe_checkout_address_details'])) {
            $address = $fomattedData['__stripe_checkout_address_details'];

            $parsed['__stripe_checkout_address_details'] = array(
                'label' => 'Billing / Shipping Address',
                'value' => $this->formatAddress($address),
                'type'  => '__stripe_checkout_address_details'
            );
        }
        return $parsed;
    }

    public function formatAddress($address)
    {
        $validValues = array();
        foreach ($address as $addressLine) {
            if ($addressLine) {
                $validValues[] = $addressLine;
            }
        }
        return implode(', ', $validValues);
    }
}