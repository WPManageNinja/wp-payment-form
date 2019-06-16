<?php

namespace WPPayForm\Classes;

use WPPayForm\Classes\Models\Forms;
use WPPayForm\Classes\Models\OrderItem;
use WPPayForm\Classes\Models\Submission;
use WPPayForm\Classes\Models\SubmissionActivity;
use WPPayForm\Classes\Models\Subscription;
use WPPayForm\Classes\Models\Transaction;
use WPPayForm\Classes\PaymentMethods\Stripe\Plan;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Form Submission Handler
 * @since 1.0.0
 */
class SubmissionHandler
{
    private $customerName = '';
    private $customerEmail = '';
    private $selectedPaymentMethod = '';


    public function handeSubmission()
    {

        parse_str($_REQUEST['form_data'], $form_data);
        // Now Validate the form please
        $formId = absint($_REQUEST['form_id']);
        // Get Original Form Elements Now

        do_action('wppayform/form_submission_activity_start', $formId);

        $form = Forms::getForm($formId);

        if (!$form) {
            wp_send_json_error(array(
                'message' => __('Invalid request. Please try again', 'wppayform')
            ), 423);
        }

        $formattedElements = Forms::getFormattedElements($formId);
        $this->validate($form_data, $formattedElements, $form);

        // Extract Payment Items Here
        $paymentItems = array();
        $subscriptionItems = array();

        foreach ($formattedElements['payment'] as $paymentId => $payment) {
            $quantity = $this->getItemQuantity($formattedElements['item_quantity'], $paymentId, $form_data);

            if ($payment['type'] == 'recurring_payment_item') {
                $subscription = $this->getSubscriptionLine($payment, $paymentId, $quantity, $form_data, $formId);
                $subscriptionItems = array_merge($subscriptionItems, $subscription);
            }

            $lineItems = $this->getPaymentLine($payment, $paymentId, $quantity, $form_data);
            if($lineItems) {
                $paymentItems = array_merge($paymentItems, $lineItems);
            }
        }

        $paymentItems = apply_filters('wppayform/submitted_payment_items', $paymentItems, $formattedElements, $form_data);
        $subscriptionItems = apply_filters('wppayform/submitted_subscription_items', $subscriptionItems, $formattedElements, $form_data);

        // Extract Input Items Here
        $inputItems = array();
        foreach ($formattedElements['input'] as $inputName => $inputElement) {
            $value = ArrayHelper::get($form_data, $inputName);
            $inputItems[$inputName] = apply_filters('wppayform/submitted_value_' . $inputElement['type'], $value, $inputElement, $form_data);
        }

        // Calculate Payment Total Now
        $paymentTotal = 0;
        foreach ($paymentItems as $paymentItem) {
            $paymentTotal += $paymentItem['line_total'];
        }

        $currentUserId = get_current_user_id();
        if (!$this->customerName && $currentUserId) {
            $currentUser = get_user_by('ID', $currentUserId);
            $this->customerName = $currentUser->display_name;
        }

        if (!$this->customerEmail && $currentUserId) {
            $currentUser = get_user_by('ID', $currentUserId);
            $this->customerEmail = $currentUser->user_email;
        }

        $paymentMethod = apply_filters('wppayform/choose_payment_method_for_submission', '', $formattedElements['payment_method_element'], $formId, $form_data);

        if ($formattedElements['payment_method_element'] && !$paymentMethod) {
            wp_send_json_error(array(
                'message' => __('Validation failed, because selected payment method could not be found', 'wppayform')
            ), 423);
            exit;
        }

        $currencySetting = Forms::getCurrencySettings($formId);
        $currency = $currencySetting['currency'];
        $inputItems = apply_filters('wppayform/submission_data_formatted', $inputItems, $form_data, $formId);

        if (isset($form_data['__stripe_billing_address_json'])) {
            $address = json_decode($form_data['__stripe_billing_address_json'], true);
            if (!$this->customerName && isset($address['name'])) {
                $this->customerName = $address['name'];
            }
        }

        if (!$this->customerEmail && isset($form_data['__stripe_user_email'])) {
            $this->customerEmail = $address['__stripe_user_email'];
        }

        $submission = array(
            'form_id'             => $formId,
            'user_id'             => $currentUserId,
            'customer_name'       => $this->customerName,
            'customer_email'      => $this->customerEmail,
            'form_data_raw'       => maybe_serialize($form_data),
            'form_data_formatted' => maybe_serialize(wp_unslash($inputItems)),
            'currency'            => $currency,
            'payment_method'      => $paymentMethod,
            'payment_status'      => 'pending',
            'submission_hash'     => $this->getHash(),
            'payment_total'       => $paymentTotal,
            'status'              => 'new',
            'created_at'          => gmdate('Y-m-d H:i:s'),
            'updated_at'          => gmdate('Y-m-d H:i:s')
        );

        $ipLoggingStatus = GeneralSettings::ipLoggingStatus(true);

        if (apply_filters('wppayform/record_client_info', $ipLoggingStatus, $form)) {
            $browser = new Browser();
            $submission['ip_address'] = $browser->getIp();
            $submission['browser'] = $browser->getBrowser();
            $submission['device'] = $browser->getPlatform();
        }

        $submission = apply_filters('wppayform/create_submission_data', $submission, $formId, $form_data);

        do_action('wppayform/wpf_before_submission_data_insert_' . $paymentMethod, $submission, $form_data);
        do_action('wppayform/wpf_before_submission_data_insert', $submission, $form_data);

        // Insert Submission
        $submissionModel = new Submission();
        $submissionId = $submissionModel->create($submission);
        do_action('wppayform/after_submission_data_insert', $submissionId, $formId);
        $submission = $submissionModel->getSubmission($submissionId);

        if ($paymentItems || $subscriptionItems) {
            // Insert Payment Items
            $itemModel = new OrderItem();
            foreach ( $paymentItems as $payItem ) {
                $payItem['submission_id'] = $submissionId;
                $payItem['form_id'] = $formId;
                $itemModel->create($payItem);
            }

            // insert subscription items
            $subscription = new Subscription();
            foreach ($subscriptionItems as $subscriptionItem) {
                $subscriptionItem['submission_id'] = $submissionId;
                $subscription->create($subscriptionItem);
            }

            $hasSubscriptions = (bool) $subscriptionItems;

            $transactionId = false;

            if($paymentItems) {
                // Insert Transaction Item Now
                $transaction = array(
                    'form_id'        => $formId,
                    'user_id'        => $currentUserId,
                    'submission_id'  => $submissionId,
                    'charge_id'      => '',
                    'payment_method' => $paymentMethod,
                    'payment_total'  => $paymentTotal,
                    'currency'       => $currency,
                    'status'         => 'pending',
                    'created_at'     => gmdate('Y-m-d H:i:s'),
                    'updated_at'     => gmdate('Y-m-d H:i:s')
                );

                $transaction = apply_filters('wppayform/submission_transaction_data', $transaction, $formId, $form_data);

                $transactionModel = new Transaction();
                $transactionId = $transactionModel->create($transaction);
                do_action('wppayform/after_transaction_data_insert', $transactionId, $transaction);
            }

            SubmissionActivity::createActivity(array(
                'form_id'       => $form->ID,
                'submission_id' => $submissionId,
                'type'          => 'activity',
                'created_by'    => 'PayForm BOT',
                'content'       => 'After payment actions processed.'
            ));

            if ($paymentMethod) {
                do_action( 'wppayform/form_submission_make_payment_' . $paymentMethod, $transactionId, $submissionId, $form_data, $form, $hasSubscriptions );
            }
        }

        do_action('wppayform/after_form_submission_complete', $submission, $formId);
        $confirmation = Forms::getConfirmationSettings($formId);
        $confirmation = $this->parseConfirmation($confirmation, $submission);
        $confirmation = apply_filters('wppayform/form_confirmation', $confirmation, $submissionId, $formId);
        wp_send_json_success(array(
            'message'       => __('Form is successfully submitted', 'wppayform'),
            'submission_id' => $submissionId,
            'confirmation'  => $confirmation
        ), 200);
    }

    private function validate($form_data, $formattedElements, $form)
    {
        $errors = array();
        $formId = $form->ID;
        $customerName = '';
        $customerEmail = '';

        // Validate Normal Inputs
        foreach ($formattedElements['input'] as $elementId => $element) {
            $error = false;
            if (ArrayHelper::get($element, 'options.required') == 'yes' && empty($form_data[$elementId])) {
                $error = $this->getErrorLabel($element, $formId);
            }
            $error = apply_filters('wppayform/validate_data_on_submission_' . $element['type'], $error, $elementId, $element, $form_data);
            if ($error) {
                $errors[$elementId] = $error;
            }

            if ($element['type'] == 'customer_name' && !$customerName && isset($form_data[$elementId])) {
                $customerName = $form_data[$elementId];
            } else if ($element['type'] == 'customer_email' && !$customerEmail && isset($form_data[$elementId])) {
                $customerEmail = $form_data[$elementId];
            }
        }

        // Validate Payment Fields
        foreach ($formattedElements['payment'] as $elementId => $element) {
            if (ArrayHelper::get($element, 'options.required') == 'yes' && !isset($form_data[$elementId])) {
                $errors[$elementId] = $this->getErrorLabel($element, $formId);
            }
        }
        // Validate Item Quanity Elements
        foreach ($formattedElements['item_quantity'] as $elementId => $element) {
            if (isset($form_data[ArrayHelper::get($element, 'options.target_product')])) {
                if (ArrayHelper::get($element, 'options.required') == 'yes' && empty($form_data[$elementId])) {
                    $errors[$elementId] = $this->getErrorLabel($element, $formId);
                }
            }
        }

        $errors = apply_filters('wppayform/form_submission_validation_errors', $errors, $formId, $formattedElements);

        if ($errors) {
            wp_send_json_error(array(
                'message' => __('Form Validation failed', 'wppayform'),
                'errors'  => $errors
            ), 423);
        }

        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;

        return;
    }

    private function getItemQuantity($quantityElements, $tragetItemId, $formData)
    {
        if (!$quantityElements) {
            return 1;
        }
        foreach ($quantityElements as $key => $element) {
            if (ArrayHelper::get($element, 'options.target_product') == $tragetItemId) {
                return absint($formData[$key]);
            }
        }
        return 1;
    }

    private function getPaymentLine($payment, $paymentId, $quantity, $formData)
    {
        if (!isset($formData[$paymentId])) {
            return array();
        }
        $label = ArrayHelper::get($payment, 'options.label');
        if (!$label) {
            $label = $paymentId;
        }
        $payItem = array(
            'type'          => 'single',
            'parent_holder' => $paymentId,
            'item_name'     => $label,
            'quantity'      => $quantity,
            'created_at'    => gmdate('Y-m-d H:i:s'),
            'updated_at'    => gmdate('Y-m-d H:i:s'),
        );

        if ($payment['type'] == 'payment_item') {
            $priceDetailes = ArrayHelper::get($payment, 'options.pricing_details');
            $payType = ArrayHelper::get($priceDetailes, 'one_time_type');
            if ($payType == 'choose_single') {
                $pricings = $priceDetailes['multiple_pricing'];
                $price = $pricings[$formData[$paymentId]];
                $payItem['item_name'] = $price['label'];
                $payItem['item_price'] = absint($price['value'] * 100);
                $payItem['line_total'] = $payItem['item_price'] * $quantity;
            } else if ($payType == 'choose_multiple') {
                $selctedItems = $formData[$paymentId];
                $pricings = $priceDetailes['multiple_pricing'];
                $payItems = array();
                foreach ($selctedItems as $itemIndex => $selctedItem) {
                    $itemClone = $payItem;
                    $itemClone['item_name'] = $pricings[$itemIndex]['label'];
                    $itemClone['item_price'] = absint($pricings[$itemIndex]['value'] * 100);
                    $itemClone['line_total'] = $itemClone['item_price'] * $quantity;
                    $payItems[] = $itemClone;
                }
                return $payItems;
            } else {
                $payItem['item_price'] = absint(ArrayHelper::get($priceDetailes, 'payment_amount') * 100);
                $payItem['line_total'] = absint($payItem['item_price']) * $quantity;
            }
        }
        else if ($payment['type'] == 'custom_payment_input') {
            $payItem['item_price'] = absint(floatval($formData[$paymentId]) * 100);
            $payItem['line_total'] = absint($payItem['item_price'] * $quantity);
        }
        else if ($payment['type'] == 'recurring_payment_item') {
            $planIndex = $formData[$paymentId];
            $plan = ArrayHelper::get($payment, 'options.recurring_payment_options.pricing_options.'.$planIndex);
            if(!$plan || !$plan['signup_fee']) {
                return array();
            }
            $payItem['type'] = 'signup_fee';
            $signupLabel = __('Signup Fee for', 'wppayform');
            $signupLabel .= ' '.$label;
            $signupLabel = apply_filters('wppayform/signup_fee_label', $signupLabel, $payment, $formData);
            $payItem['item_name'] = $signupLabel;
            $payItem['item_price'] = absint($plan['signup_fee'] * 100);
            $payItem['line_total'] = $payItem['item_price'] * $quantity;
            return array($payItem);
        }
        else {
            return array();
        }
        return array($payItem);
    }

    private function getSubscriptionLine($payment, $paymentId, $quantity, $formData, $formId)
    {
        if ($payment['type'] != 'recurring_payment_item') {
            return array();
        }
        if (!isset($formData[$paymentId])) {
            return array();
        }
        $label = ArrayHelper::get($payment, 'options.label');
        if (!$label) {
            $label = $paymentId;
        }


        $pricings = ArrayHelper::get($payment, 'options.recurring_payment_options.pricing_options');
        $plan = $pricings[$formData[$paymentId]];

        if (!$plan) {
            return array();
        }

        $daysToExpiration = absint($plan['billing_days_period']);
        if ($plan['trial_days']) {
            $daysToExpiration = absint($daysToExpiration);
        }

        $expirationDate = gmdate('Y-m-d H:i:s', time() + $daysToExpiration * 86400);

        $subscription = array(
            'element_id'          => $paymentId,
            'item_name'           => $label,
            'form_id'             => $formId,
            'plan_name'           => $plan['name'],
            'billing_interval' => 'month',
            'trial_days'          => $plan['trial_days'],
            'recurring_amount'    => absint($plan['subscription_amount'] * 100),
            'bill_times'          => $plan['bill_times'],
            'initial_amount'      => absint($plan['signup_fee'] * 100),
            'status'              => 'pending',
            'original_plan'       => maybe_serialize($plan),
            'expiration_at'       => $expirationDate,
            'created_at'          => gmdate('Y-m-d H:i:s'),
            'updated_at'          => gmdate('Y-m-d H:i:s'),
        );

        $allSubscriptions = [$subscription];

        if ($quantity > 1) {
            for ($i = 1; $i < $quantity; $i++) {
                $allSubscriptions[] = $subscription;
            }
        }

        return $allSubscriptions;

    }

    private function getErrorLabel($element, $formId)
    {
        $label = ArrayHelper::get($element, 'options.label');
        if (!$label) {
            $label = ArrayHelper::get($element, 'options.placeholder');
            if (!$label) {
                $label = $element['id'];
            }
        }
        $label = $label . __(' is required', 'wppayform');
        return apply_filters('wppayform/error_label_text', $label, $element, $formId);
    }

    public function parseConfirmation($confirmation, $submission)
    {
        // add payment hash to the url
        if (
            ($confirmation['redirectTo'] == 'customUrl' && $confirmation['customUrl']) ||
            ($confirmation['redirectTo'] == 'customPage' && $confirmation['customPage'])
        ) {
            if ($confirmation['redirectTo'] == 'customUrl') {
                $url = $confirmation['customUrl'];
            } else {
                $url = get_permalink(intval($confirmation['customPage']));
            }
            $confirmation['redirectTo'] = 'customUrl';
            $url = add_query_arg('wpf_submission', $submission->submission_hash, $url);
            $confirmation['customUrl'] = PlaceholderParser::parse($url, $submission);
        } else if ($confirmation['redirectTo'] == 'samePage') {
            do_action('wppayform/require_entry_html');
            $confirmation['messageToShow'] = PlaceholderParser::parse($confirmation['messageToShow'], $submission);
            do_action('wppayform/require_entry_html_done');
        }
        return $confirmation;
    }

    private function getHash()
    {
        $prefix = 'wpf_' . time();
        $uid = uniqid($prefix);
        // now let's make a unique number from 1 to 999
        $uid .= mt_rand(1, 999);
        $uid = str_replace(array("'", '/', '?', '#', "\\"), '', $uid);
        return $uid;
    }
}