<?php

namespace WPPayForm\Classes\PaymentMethods\Stripe;

use WPPayForm\Classes\AccessControl;
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

        add_filter('wppayform/submitted_payment_items_stripe', array($this, 'maybeSignupFeeToPaymentItems'), 10, 4);

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
        $transactionModel = new Transaction();
        $transaction = $transactionModel->getTransaction($transactionId);

        $hasTransaction = $transaction && $transaction->payment_total;

        if (!$hasTransaction && !$hasSubscriptions) {
            return;
        }

        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId);
        $token = $form_data['stripeToken'];
        $currentUserId = get_current_user_id();

        $metadata = array(
            'form_id'        => $form->ID,
            'user_id'        => $currentUserId,
            'submission_id'  => $submissionId,
            'wppayform_tid'  => $transactionId,
            'wp_plugin_slug' => 'wppayform'
        );



        $stripeCustomerId = false;
        $isCreateCustomer = $this->needToCreateCustomer($submission);
        if ($hasSubscriptions) {
            $isCreateCustomer = true;
        }
        if ($isCreateCustomer) {
            $customer = $this->createCustomer($token, $submission, $transaction, $form, $metadata);
            if ($customer) {
                $stripeCustomerId = $customer->id;
            }
        }

        $subscribedItems = false;
        if ($hasSubscriptions && $stripeCustomerId) {
            $subscribedItems = $this->handleSubscriptions($stripeCustomerId, $submission, $form);
        }

        if ($stripeCustomerId) {
            $paymentArgs['customer'] = $stripeCustomerId;
        } else {
            $paymentArgs['source'] = $token;
        }

        $charge = false;
        if ($hasTransaction) {
            $charge = $this->handleOnetimePayment($submission, $transaction, $form, $paymentArgs);
        }

        // handle error for one time paymeng
        if (is_wp_error($charge)) {
            $errorCode = $charge->get_error_code();
            $message = $charge->get_error_message($errorCode);
            return $this->handlePaymentChargeError($message, $submission, $transaction, $form, $charge, 'charge');
        }

        // handle error for subscribed items
        if ($subscribedItems && is_wp_error($subscribedItems)) {
            $errorCode = $subscribedItems->get_error_code();
            $message = $subscribedItems->get_error_message($errorCode);
            return $this->handlePaymentChargeError($message, $submission, $transaction, $form, $charge, 'charge');
        }

        $transaction = $transactionModel->getTransaction($transactionId);
        do_action('wppayform/form_payment_success_stripe', $submission, $transaction, $submission->form_id, $charge);
        do_action('wppayform/form_payment_success', $submission, $transaction, $submission->form_id, $charge);

        if ($subscribedItems) {
            do_action('wppayform/form_recurring_subscribed_stripe', $submission, $subscribedItems, $submission->form_id);
            do_action('wppayform/form_recurring_subscribed', $submission, $subscribedItems, $submission->form_id);
        }

    }

    public function handleOnetimePayment($submission, $transaction, $form, $tokenArgs)
    {
        $paymentArgs = array(
            'currency'             => $transaction->currency,
            'amount'               => $transaction->payment_total,
            'description'          => $form->post_title,
            'statement_descriptor' => $form->post_title
        );
        $paymentArgs = wp_parse_args($paymentArgs, $tokenArgs);

        $metadata = [
            'Submission ID' => $submission->id,
            'Form ID' => $submission->form_id,
            'Details URL' => admin_url('admin.php?page=wppayform.php#/edit-form/'.$submission->form_id.'/entries/'.$submission->id.'/view'),
        ];
        if ($submission->customer_email) {
            $paymentArgs['receipt_email'] = $submission->customer_email;
            $metadata['customer_email'] = $submission->customer_email;
        }
        if ($submission->customer_name) {
            $metadata['customer_name'] = $submission->customer_name;
        }

        $metadata = apply_filters('wppayform/stripe_onetime_payment_metadata', $metadata, $submission);

        $paymentArgs['metadata'] = $metadata;

        if (GeneralSettings::isZeroDecimal($paymentArgs['currency'])) {
            $paymentArgs['amount'] = intval($paymentArgs['amount'] / 100);
        }

        $charge = Charge::charge($paymentArgs);

        if (!is_wp_error($charge)) {
            $paymentMode = $this->getMode();
            $transactionModel = new Transaction();
            $transactionModel->update($transaction->id, array(
                'status'         => 'paid',
                'charge_id'      => $charge->id,
                'card_last_4'    => $charge->source->last4,
                'card_brand'     => $charge->source->brand,
                'payment_method' => 'stripe',
                'payment_mode'   => $submission->payment,
            ));

            $submissionUpdateData = array(
                'payment_status' => 'paid',
                'payment_method' => 'stripe',
                'payment_mode'   => $paymentMode,
            );
            if ($paymentArgs['customer']) {
                $submissionUpdateData['customer_id'] = $paymentArgs['customer'];
            }
            $submissionModel = new Submission();
            $submissionModel->update($submission->id, $submissionUpdateData);

            SubmissionActivity::createActivity(array(
                'form_id'       => $form->ID,
                'submission_id' => $submission->id,
                'type'          => 'activity',
                'created_by'    => 'PayForm BOT',
                'content'       => __('One time Payment Successfully made via stripe. Charge ID: ', 'wppayform') . $charge->id
            ));

            SubmissionActivity::createActivity(array(
                'form_id'       => $form->ID,
                'submission_id' => $submission->id,
                'type'          => 'activity',
                'created_by'    => 'PayForm BOT',
                'content'       => __('Payment status changed from pending to success', 'wppayform')
            ));
        }

        return $charge;
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
            ''
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
            'company_name'    => wp_unslash($settings['company_name']),
            'checkout_logo'   => sanitize_text_field($settings['checkout_logo']),
        );

        if(isset($settings['send_meta_data'])) {
            $data['send_meta_data'] = sanitize_text_field($settings['send_meta_data']);
        }

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
            'webhook_url'    => site_url() . '?wpf_stripe_listener=1',
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
            'checkout_logo'   => '',
            'send_meta_data' => 'no'
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

    private function handleSubscriptions($customer, $submission, $form)
    {
        $subscriptionModel = new Subscription();
        $subscriptionTransactionModel = new SubscriptionTransaction();
        $subscriptions = $subscriptionModel->getSubscriptions($submission->id);

        if (!$subscriptions) {
            return false;
        }

        $isOneSucceed = false;
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

            $subscriptionStatus = 'active';
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
            'payment_status' => 'paid'
        ]);

        return $subscriptionModel->getSubscriptions($submission->id);
    }

    public function maybeSignupFeeToPaymentItems($paymentItems, $formattedElements, $form_data, $subscriptionItems)
    {
        if (!$subscriptionItems) {
            return $paymentItems;
        }
        foreach ($subscriptionItems as $subscriptionItem) {
            if ($subscriptionItem['initial_amount']) {
                $signupLabel = __('Signup Fee for', 'wppayform');
                $signupLabel .= ' ' . $subscriptionItem['item_name'];
                $signupLabel = apply_filters('wppayform/signup_fee_label', $signupLabel, $subscriptionItem, $form_data);
                $paymentItems[] = array(
                    'type'          => 'signup_fee',
                    'parent_holder' => $subscriptionItem['element_id'],
                    'item_name'     => $signupLabel,
                    'quantity'      => 1,
                    'item_price'    => $subscriptionItem['initial_amount'],
                    'line_total'    => $subscriptionItem['initial_amount'],
                    'created_at'    => gmdate('Y-m-d H:i:s'),
                    'updated_at'    => gmdate('Y-m-d H:i:s'),
                );
            }
        }
        return $paymentItems;
    }
}