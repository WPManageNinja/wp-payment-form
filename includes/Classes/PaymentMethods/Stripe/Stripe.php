<?php

namespace WPPayForm\Classes\PaymentMethods\Stripe;

use WPPayForm\Classes\AccessControl;
use WPPayForm\Classes\GeneralSettings;
use WPPayForm\Classes\Models\Submission;
use WPPayForm\Classes\Models\SubmissionActivity;
use WPPayForm\Classes\Models\Subscription;
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
        add_filter('wppayform/parsed_entry', array($this, 'addAddressToView'), 10, 2);
        add_filter('wppayform/submission_data_formatted', array($this, 'pushAddressToInput'), 10, 3);
        add_action('wppayform/form_submission_make_payment_stripe', array($this, 'makeFormPayment'), 10, 5);
        add_filter('wppayform/entry_transactions', array($this, 'addTransactionUrl'), 10, 2);
        add_filter('wppayform/choose_payment_method_for_submission', array($this, 'choosePaymentMethod'), 10, 4);
        add_action('wppayform/wpf_before_submission_data_insert_stripe', array($this, 'validateStripeToken'), 10, 2);

        // ajax endpoints
        add_action('wp_ajax_wpf_save_stripe_settings', array($this, 'savePaymentSettings'));
        add_action('wp_ajax_wpf_get_stripe_settings', array($this, 'getPaymentSettings'));

        add_filter('wppayform/checkout_vars', array($this, 'addLocalizeVars'));

    }

    public function addLocalizeVars($vars)
    {
        $paymentSettings = $this->getStripeSettings();
        $vars['stripe_checkout_title'] = $paymentSettings['company_name'];
        $vars['stripe_checkout_logo'] = $paymentSettings['checkout_logo'];
        $vars['stripe_pub_key'] = $this->getPubKey();
        return $vars;
    }

    public function validateStripeToken($submission, $form_data)
    {
        if ($submission['payment_total'] && empty($form_data['stripeToken'])) {
            wp_send_json_error(array(
                'message' => __('Stripe payment token is missing. Please input your card details', 'wppayform'),
                'errors'  => array()
            ), 423);
        }
    }

    public function choosePaymentMethod($paymentMethod, $elements, $formId, $form_data)
    {
        if ($paymentMethod) {
            // Already someone choose that it's their payment method
            return $paymentMethod;
        }
        // Now We have to analyze the elements and return our payment method
        foreach ($elements as $element) {
            if (isset($element['type']) && $element['type'] == 'stripe_card_element') {
                return 'stripe';
            }
        }
        return $paymentMethod;
    }

    public function makeFormPayment($transactionId, $submissionId, $form_data, $form, $hasSubscriptions)
    {
        $paymentMode = $this->getMode();
        $transactionModel = new Transaction();
        $transaction = $transactionModel->getTransaction($transactionId);

        if (!$transaction->payment_total && !$hasSubscriptions) {
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


        $isCreateCustomer = $this->needToCreateCustomer($submission);
        if ($hasSubscriptions) {
            $isCreateCustomer = true;
        }
        if ($isCreateCustomer) {
            $customer = $this->createCustomer($token, $submission, $transaction, $form, $metadata);
            if($customer) {
                $paymentArgs['customer'] = $customer->id;
                unset($paymentArgs['source']);
            }
        }

        if (GeneralSettings::isZeroDecimal($paymentArgs['currency'])) {
            $paymentArgs['amount'] = intval($paymentArgs['amount'] / 100);
        }

        if ($hasSubscriptions && $paymentArgs['customer']) {
            $this->handleSubscriptions($paymentArgs['customer'], $submission, $transaction, $form);
        }

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
            return $this->handlePaymentChargeError($message, $submission, $transaction, $form, $charge, 'charge');
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

        $submissionUpdateData = array(
            'payment_status' => 'paid',
            'payment_method' => 'stripe',
            'payment_mode'   => $paymentMode,
        );
        if ($customer) {
            $submissionUpdateData['customer_id'] = $customer->id;
        }
        $submissionModel->update($submissionId, $submissionUpdateData);

        SubmissionActivity::createActivity(array(
            'form_id'       => $form->ID,
            'submission_id' => $submissionId,
            'type'          => 'activity',
            'created_by'    => 'PayForm BOT',
            'content'       => __('Payment status changed from pending to success', 'wppayform')
        ));

        $transaction = $transactionModel->getTransaction($transactionId);

        do_action('wppayform/form_payment_success_stripe', $submission, $transaction, $transaction->form_id, $charge);
        do_action('wppayform/form_payment_success', $submission, $transaction, $transaction->form_id, $charge);
    }

    public function handlePaymentChargeError($message, $submission, $transaction, $form, $charge = false, $type = 'general')
    {
        $paymentMode = $this->getMode();
        do_action('wppayform/form_payment_stripe_failed', $submission, $transaction, $form, $charge, $type);
        do_action('wppayform/form_payment_failed', $submission, $transaction, $form, $charge, $type);

        $submissionModel = new Submission();
        $transactionModel = new Transaction();

        $transactionModel->update($transaction->id, array(
            'status'         => 'failed',
            'payment_method' => 'stripe',
            'payment_mode'   => $paymentMode,
        ));
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

        if($message) {
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
            'payment_error' => true
        ), 423);
    }


    public function addTransactionUrl($transactions, $formId)
    {
        foreach ($transactions as $transaction) {
            if ($transaction->payment_method == 'stripe' && $transaction->charge_id) {
                if ($transaction->payment_mode == 'test') {
                    $transactionUrl = 'https://dashboard.stripe.com/test/payments/' . $transaction->charge_id;
                } else {
                    $transactionUrl = 'https://dashboard.stripe.com/payments/' . $transaction->charge_id;
                }
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
        AccessControl::checkAndPresponseError('set_payment_settings', 'global');
        $settings = $_REQUEST['settings'];
        // Validate the data first
        $mode = $settings['payment_mode'];
        if ($mode == 'test') {
            // We require test keys
            if (empty($settings['test_pub_key']) || empty($settings['test_secret_key'])) {
                wp_send_json_error(array(
                    'message' => __('Please provide Test Publishable key and Test Secret Key', 'wppayform')
                ), 423);
            }
        }

        if ($mode == 'live' && !$this->isStripeKeysDefined()) {
            if (empty($settings['live_pub_key']) || empty($settings['live_secret_key'])) {
                wp_send_json_error(array(
                    'message' => __('Please provide Live Publishable key and Live Secret Key', 'wppayform')
                ), 423);
            }
        }

        // Validation Passed now let's make the data
        $data = array(
            'payment_mode'    => sanitize_text_field($mode),
            'live_pub_key'    => sanitize_text_field($settings['live_pub_key']),
            'live_secret_key' => sanitize_text_field($settings['live_secret_key']),
            'test_pub_key'    => sanitize_text_field($settings['test_pub_key']),
            'test_secret_key' => sanitize_text_field($settings['test_secret_key']),
            'company_name'    => sanitize_text_field($settings['company_name']),
            'checkout_logo'   => sanitize_text_field($settings['checkout_logo'])
        );
        do_action('wppayform/before_save_stripe_settings', $data);
        update_option('wppayform_stripe_payment_settings', $data, false);
        do_action('wppayform/after_save_stripe_settings', $data);

        wp_send_json_success(array(
            'message' => __('Settings successfully updated', 'wppayform')
        ), 200);
    }

    public function getPaymentSettings()
    {
        AccessControl::checkAndPresponseError('get_payment_settings', 'global');
        wp_send_json_success(array(
            'settings'       => $this->getStripeSettings(),
            'is_key_defined' => $this->isStripeKeysDefined()
        ), 200);
    }


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
            'checkout_logo'   => ''
        );
        return wp_parse_args($settings, $defaults);
    }

    public function getPubKey()
    {
        $paymentSettings = $this->getStripeSettings();
        if ($paymentSettings['payment_mode'] == 'live') {
            if ($this->isStripeKeysDefined()) {
                return WP_PAY_FORM_STRIPE_PUB_KEY;
            } else {
                return $paymentSettings['live_pub_key'];
            }
        }
        return $paymentSettings['test_pub_key'];
    }

    public function getSecretKey()
    {
        $paymentSettings = $this->getStripeSettings();
        if ($paymentSettings['payment_mode'] == 'live') {
            if ($this->isStripeKeysDefined()) {
                return WP_PAY_FORM_STRIPE_SECRET_KEY;
            } else {
                return $paymentSettings['live_secret_key'];
            }
        }
        return $paymentSettings['test_secret_key'];
    }

    public function isStripeKeysDefined()
    {
        return defined('WP_PAY_FORM_STRIPE_SECRET_KEY') && defined('WP_PAY_FORM_STRIPE_PUB_KEY');
    }

    // Decide if stripe will create customer or not
    public function needToCreateCustomer($submission)
    {
        // @todo: need to make it configarable
        $status = defined('WPPAYFORM_CREATE_CUSTOMER') && WPPAYFORM_CREATE_CUSTOMER;
        return apply_filters('wppayform/stripe_create_customer', $status, $submission);
    }

    private function createCustomer($token, $submission, $transaction, $form, $metadata)
    {
        // We have to create customer and then make the payment
        $description = 'Customer for Submission ID: ' . $submission->id;
        $customerEmail = null;
        $customerMeta = array();
        if ($submission->customer_email) {
            $description = 'Customer for ' . $submission->customer_email;
            $customerEmail = $submission->customer_email;
            $customerMeta['email'] = $submission->customer_email;
        }
        if (isset($metadata['customer_name'])) {
            $customerMeta['name'] = $metadata['customer_name'];
        }
        $customerMeta['payform_id'] = $form->ID;

        $customerArgs = array(
            'description' => $description,
            'email'       => $customerEmail,
            'metadata'    => $customerMeta,
            'source'      => $token
        );
        $customerArgs = apply_filters('wppayform/stripe_customer_args', $customerArgs, $metadata, $submission);
        $customer = Customer::createCustomer($customerArgs);

        $customerStatus = true;

        if (is_wp_error($customer)) {
            $customerStatus = false;
            $errorCode = $customer->get_error_code();
            $message = $customer->get_error_message($errorCode);
        } else if (!$customer) {
            $customerStatus = false;
            $message = __('Customer Create Failed via stripe. Please try again', 'wppayform');
        }

        if (!$customerStatus) {
            return $this->handlePaymentChargeError($message, $submission, $transaction, $form, false, 'customer_create');
        }

        SubmissionActivity::createActivity(array(
            'form_id'       => $form->ID,
            'submission_id' => $submission->id,
            'type'          => 'activity',
            'created_by'    => 'PayForm BOT',
            'content'       => __('Stripe Customer created. Customer ID: ', 'wppayform') . $customer->id
        ));

        return $customer;
    }

    private function handleSubscriptions($customer, $submission, $transaction, $form)
    {
        $subscriptionModel = new Subscription();
        $subscriptions = $subscriptionModel->getSubscriptions($submission->id);
        $subscription = PlanSubscription::create($subscriptions, $customer, $submission);

        if(!$subscription || is_wp_error($subscription)) {

            foreach ($subscriptions as $subscriptionItem) {
                $subscriptionModel->update($subscriptionItem->id, [
                    'status' => 'failed',
                ]);
            }

            $message = __('Stripe error when creating subscription plan for you. Please contact site admin', 'wppayform');
            if(is_wp_error($subscription)) {
                $errorCode = $subscription->get_error_code();
                $message = $subscription->get_error_message($errorCode);
            }
            $this->handlePaymentChargeError($message, $submission, $transaction, $form, false, 'subscription_error');
        }

        // Now we have to do the maths for recurring payments
        // Update Subscription Payment Status
        foreach ($subscriptions as $subscriptionItem) {
            $subscriptionModel->update($subscriptionItem->id, [
                'status' => 'active',
            ]);
        }

        // Let's create the Subscription Transaction
        print_r($subscription);
        die();

        SubmissionActivity::createActivity(array(
            'form_id'       => $form->ID,
            'submission_id' => $submission->id,
            'type'          => 'activity',
            'created_by'    => 'PayForm BOT',
            'content'       => __('Stripe recurring payment subscription successfully initiated', 'wppayform')
        ));


    }
}