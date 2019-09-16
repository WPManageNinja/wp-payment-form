<?php

namespace WPPayForm\Classes\PaymentMethods\Stripe;

use WPPayForm\Classes\AccessControl;
use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\Models\Submission;

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

        add_filter('wppayform/entry_transactions', array($this, 'addTransactionUrl'), 10, 2);
        add_filter('wppayform/choose_payment_method_for_submission', array($this, 'choosePaymentMethod'), 10, 4);


        /*
         * This is rquired
         */
        add_action('wppayform/after_submission_data_insert_stripe', array($this, 'addPaymentMethodStyle'), 10, 3);
        add_action('wppayform/form_submission_make_payment_stripe', array($this, 'routePaymentHandler'), 10, 5);

        // ajax endpoints for admin
        add_action('wp_ajax_wpf_save_stripe_settings', array($this, 'savePaymentSettings'));
        add_action('wp_ajax_wpf_get_stripe_settings', array($this, 'getPaymentSettings'));

        add_filter('wppayform/checkout_vars', array($this, 'addLocalizeVars'));

        /*
         * Push signup fees to single payment item
         */
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

    public function addPaymentMethodStyle($submissionId, $formId, $paymentMethodElement)
    {
        $style = $this->getStripePaymentMethodByElement($paymentMethodElement);
        $submissionModel = new Submission();
        $submissionModel->updateMeta($submissionId, 'stripe_payment_style', $style);
    }

    public function routePaymentHandler($transactionId, $submissionId, $form_data, $form, $hasSubscriptions)
    {
        $submissionModel = new Submission();
        $handler = $submissionModel->getMeta($submissionId, 'stripe_payment_style', 'stripe_hosted');
        do_action('wppayform/form_submission_make_payment_' . $handler, $transactionId, $submissionId, $form_data, $form, $hasSubscriptions);
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
                'label' => __('Billing Address (From Stripe)', 'wppayform'),
                'value' => $this->formatAddress($address),
                'type'  => '__checkout_billing_address_details'
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

        if (!empty($fomattedData['__stripe_phone'])) {
            $parsed['__stripe_phone'] = array(
                'label' => __('Phone (From Stripe)', 'wppayform'),
                'value' => $fomattedData['__stripe_phone'],
                'type'  => '__stripe_phone'
            );
        }


        if (!empty($fomattedData['__stripe_name'])) {
            $parsed['__stripe_name'] = array(
                'label' => __('Name on Card (From Stripe)', 'wppayform'),
                'value' => $fomattedData['__stripe_name'],
                'type'  => '__stripe_name'
            );
        }


        return $parsed;
    }

    private function formatAddress($address)
    {
        $addressSerials = [
            'line1',
            'line2',
            'city',
            'state',
            'postal_code',
            'country'
        ];
        $formattedAddress = [];
        $address = (array)$address;

        foreach ($addressSerials as $addressSerial) {
            if (!empty($address[$addressSerial])) {
                $formattedAddress[] = $address[$addressSerial];
            }
        }

        if ($formattedAddress) {
            return implode(', ', $formattedAddress);
        }

        return implode(', ', array_filter($address));
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

        if (isset($settings['send_meta_data'])) {
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
            'send_meta_data'  => 'no'
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

    public function getStripePaymentMethodByElement($paymentMethodElement)
    {
        $method = ArrayHelper::get($paymentMethodElement, 'stripe_card_element.options.checkout_display_style.style');
        if (!$method) {
            $method = ArrayHelper::get($paymentMethodElement, 'choose_payment_method.options.method_settings.payment_settings.stripe.checkout_display_style.style');
        }
        if ($method == 'embeded_form') {
            return 'stripe_inline';
        }
        return 'stripe_hosted';
    }
}