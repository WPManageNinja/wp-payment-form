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
        add_action('wp_ajax_wpf_save_stripe_settings', array($this, 'savePaymentSettings'));
        add_action('wp_ajax_wpf_get_stripe_settings', array($this, 'getPaymentSettings'));
        add_filter('wpf_payment_method_for_submission', array($this, 'choosePaymentMethod'), 10, 4);
        add_action('wpf_before_form_submission_stripe', array($this, 'validateStripeToken'), 10, 2);
    }

    public function validateStripeToken($submission, $form_data)
    {
        if($submission['payment_total'] && empty($form_data['stripeToken'])) {
            wp_send_json_error(array(
                'message' => __('Stripe payment token is missing. Please input your card details', 'wppayform'),
                'errors'  => array()
            ), 423);
        }
    }

    public function choosePaymentMethod($paymentMethod, $elements, $formId, $form_data)
    {
        if($paymentMethod) {
            // Already someone choose that it's their payment method
            return $paymentMethod;
        }
        // Now We have to analyze the elements and return our payment method
        foreach ($elements as $element) {
            if(isset($element['type']) && $element['type'] == 'stripe_card_element') {
                return 'stripe';
            }
        }
        return $paymentMethod;
    }

    public function makeFormPayment($transactionId, $submissionId, $form_data, $form)
    {
        $paymentMode = wpfGetStripePaymentMode();
        $transactionModel = new Transaction();
        $transaction = $transactionModel->getTransaction($transactionId);

        if(!$transaction->payment_total) {
            return;
        }

        $token = $form_data['stripeToken'];
        $currentUserId = get_current_user_id();

        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId);

        $paymentArgs = array(
            'currency'             => $transaction->currency,
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
            do_action('wpf_stripe_charge_failed', $transactionId, $charge, $form, $paymentArgs);
            do_action('wpf_form_payment_failed', $transactionId, $charge, $form, $paymentArgs);
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

        do_action('wpf_stripe_charge_success', $transactionId, $charge, $form, $paymentArgs);
        do_action('wpf_form_payment_success', $transactionId, $charge, $form, $paymentArgs);
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
        if (isset($formData['__stripe_billing_address_json'])) {
            $billingAddressDetails = $formData['__stripe_billing_address_json'];
            $inputItems['__checkout_billing_address_details'] = json_decode($billingAddressDetails, true);
        }

        if (isset($formData['__stripe_shipping_address_json'])) {
            $shippingAddressDetails = $formData['__stripe_shipping_address_json'];
            $inputItems['__checkout_shipping_address_details'] = json_decode($shippingAddressDetails, true);
        }

        return $inputItems;
    }

    public function addAddressToView($parsed, $submission)
    {
        $fomattedData = $submission->form_data_formatted;
        if (isset($fomattedData['__checkout_billing_address_details'])) {
            $address = $fomattedData['__checkout_billing_address_details'];
            $parsed['__checkout_billing_address_details'] = array(
                'label' => 'Billing Address',
                'value' => $this->formatAddress($address),
                'type'  => '__checkout_address_details'
            );
        }

        if (isset($fomattedData['__checkout_shipping_address_details'])) {
            $address = $fomattedData['__checkout_shipping_address_details'];
            $parsed['__checkout_shipping_address_details'] = array(
                'label' => 'Shipping Address',
                'value' => $this->formatAddress($address),
                'type'  => '__checkout_shipping_address_details'
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

    public function savePaymentSettings()
    {
        $settings = $_REQUEST['settings'];
        // Validate the data first
        $mode = $settings['payment_mode'];
        if($mode == 'test') {
            // We require test keys
            if(empty($settings['test_pub_key']) || empty($settings['test_secret_key'])) {
                wp_send_json_error(array(
                    'message' => __('Please provide Test Publishable key and Test Secret Key', 'wppayform')
                ), 423);
            }
        }

        if($mode == 'live' && !wpfIsStripeKeysDefined()) {
            if(empty($settings['live_pub_key']) || empty($settings['live_secret_key'])) {
                wp_send_json_error(array(
                    'message' => __('Please provide Live Publishable key and Live Secret Key', 'wppayform')
                ), 423);
            }
        }

        // Validation Passed now let's make the data
        $data = array(
            'payment_mode' => sanitize_text_field($mode),
            'live_pub_key' =>  sanitize_text_field($settings['live_pub_key']),
            'live_secret_key' => sanitize_text_field($settings['live_secret_key']),
            'test_pub_key' =>  sanitize_text_field($settings['test_pub_key']),
            'test_secret_key' => sanitize_text_field($settings['test_secret_key']),
            'company_name' =>  sanitize_text_field($settings['company_name']),
            'checkout_logo' =>  sanitize_text_field($settings['checkout_logo'])
        );
        update_option('wpf_stripe_payment_settings', $data, false);

        wp_send_json_success(array(
            'message' => __('Settings successfully updated', 'wppayform')
        ), 200);
    }

    public function getPaymentSettings()
    {
        wp_send_json_success(array(
            'settings' => wpfGetStripePaymentSettings(),
            'is_key_defined' => wpfIsStripeKeysDefined()
        ), 200);
    }
}