<?php

namespace WPPayForm\Classes;

use WPPayForm\Classes\Models\Forms;
use WPPayForm\Classes\Models\OrderItem;
use WPPayForm\Classes\Models\Submission;
use WPPayForm\Classes\Models\SubmissionActivity;
use WPPayForm\Classes\Models\Subscription;
use WPPayForm\Classes\Models\Transaction;
use WPPayForm\Classes\PaymentMethods\Stripe\Stripe;

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

        $paymentMethod = apply_filters('wppayform/choose_payment_method_for_submission', '', $formattedElements['payment_method_element'], $formId, $form_data);

        $this->selectedPaymentMethod = $paymentMethod;


        // Extract Payment Items Here
        $paymentItems = array();
        $subscriptionItems = array();

        foreach ($formattedElements['payment'] as $paymentId => $payment) {
            $quantity = $this->getItemQuantity($formattedElements['item_quantity'], $paymentId, $form_data);
            if ($quantity == 0) {
                continue;
            }
            if ($payment['type'] == 'recurring_payment_item') {
                $subscription = $this->getSubscriptionLine($payment, $paymentId, $quantity, $form_data, $formId);
                if(!empty($subscription['type']) && $subscription['type'] == 'single') {
                    // We converted this as one time payment
                    $paymentItems[] = $subscription;
                } else {
                    $subscriptionItems = array_merge($subscriptionItems, $subscription);
                }
            } else {
                $lineItems = $this->getPaymentLine($payment, $paymentId, $quantity, $form_data);
                if ($lineItems) {
                    $paymentItems = array_merge($paymentItems, $lineItems);
                }
            }
        }

        $paymentItems = apply_filters('wppayform/submitted_payment_items', $paymentItems, $formattedElements, $form_data);
        $subscriptionItems = apply_filters('wppayform/submitted_subscription_items', $subscriptionItems, $formattedElements, $form_data);

        /*
         * providing filter hook for payment method to push some payment data
         *  from $subscriptionItems
         * Some PaymentGateway like stripe may add signup fee as one time fee
         */
        if ($subscriptionItems) {
            $paymentItems = apply_filters('wppayform/submitted_payment_items_' . $paymentMethod, $paymentItems, $formattedElements, $form_data, $subscriptionItems);
        }

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

        if ($formattedElements['payment_method_element'] && !$paymentMethod) {
            wp_send_json_error(array(
                'message' => __('Validation failed, because selected payment method could not be found', 'wppayform')
            ), 423);
            exit;
        }

        if ($formattedElements['payment_method_element'] && $paymentMethod == 'stripe' && ($paymentTotal || $subscriptionItems)) {
            // do verification for stripe stripe_inline
            // We have to see if __stripe_payment_method_id has value or not
            $stripe = new Stripe();
            $methodStyle = $stripe->getStripePaymentMethodByElement($formattedElements['payment_method_element']);
            if ($methodStyle == 'stripe_inline') {
                if (empty($form_data['__stripe_payment_method_id'])) {
                    wp_send_json_error(array(
                        'message' => __('Validation failed, Please fill up card details', 'wppayform')
                    ), 423);
                    exit;
                }
            }
        }

        $currencySetting = Forms::getCurrencySettings($formId);
        $currency = $currencySetting['currency'];
        $inputItems = apply_filters('wppayform/submission_data_formatted', $inputItems, $form_data, $formId);

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


        do_action('wppayform/wpf_before_submission_data_insert_' . $paymentMethod, $submission, $form_data, $paymentItems, $subscriptionItems);
        do_action('wppayform/wpf_before_submission_data_insert', $submission, $form_data, $paymentItems, $subscriptionItems);

        // Insert Submission
        $submissionModel = new Submission();
        $submissionId = $submissionModel->create($submission);
        do_action('wppayform/after_submission_data_insert', $submissionId, $formId);

        /*
         * Dear Payment method developers,
         * Please do't use this hook to process the payment
         * The order items is not porcessed yet!
         */
        do_action('wppayform/after_submission_data_insert_' . $paymentMethod, $submissionId, $formId, $formattedElements['payment_method_element']);


        $submission = $submissionModel->getSubmission($submissionId);

        if ($paymentItems || $subscriptionItems) {
            // Insert Payment Items
            $itemModel = new OrderItem();
            foreach ($paymentItems as $payItem) {
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

            $hasSubscriptions = (bool)$subscriptionItems;

            $transactionId = false;

            if ($paymentItems) {
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
                do_action('wppayform/form_submission_make_payment_' . $paymentMethod, $transactionId, $submissionId, $form_data, $form, $hasSubscriptions);
            }
        }

        $this->sendSubmissionConfirmation($submission, $formId);
    }

    public function sendSubmissionConfirmation($submission, $formId)
    {
        $confirmation = $this->getFormConfirmation($formId, $submission);
        do_action('wppayform/after_form_submission_complete', $submission, $formId);

        wp_send_json_success(array(
            'message'       => __('Form is successfully submitted', 'wppayform'),
            'submission_id' => $submission->id,
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

        // Maybe validate recatcha
        $formEvents = [];
        if (!$errors) {
            $recaptchaType = Forms::recaptchaType($formId);
            if ($recaptchaType == 'v2_visible' || $recaptchaType == 'v3_invisible') {
                // let's validate recaptcha here
                $recaptchaSettings = GeneralSettings::getRecaptchaSettings();
                $ip_address = $this->getIp();
                $response = wp_remote_get(add_query_arg(array(
                    'secret'   => $recaptchaSettings['secret_key'],
                    'response' => isset($form_data['g-recaptcha-response']) ? $form_data['g-recaptcha-response'] : '',
                    'remoteip' => $ip_address
                ), 'https://www.google.com/recaptcha/api/siteverify'));

                if (is_wp_error($response) || empty($response['body']) || !($json = json_decode($response['body'])) || !$json->success) {
                    $errors['g-recaptcha-response'] = __('reCaptcha validation failed. Please try again.', 'wppayform');
                    $formEvents[] = 'refresh_recaptcha';
                }

            }
        }
        $errors = apply_filters('wppayform/form_submission_validation_errors', $errors, $formId, $formattedElements);
        if ($errors) {
            wp_send_json_error(array(
                'message'     => __('Form Validation failed', 'wppayform'),
                'errors'      => $errors,
                'form_events' => $formEvents
            ), 423);
        }

        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;

        return;
    }

    private function getIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'];
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
                $payItem['item_price'] = wpPayFormConverToCents($price['value']);
                $payItem['line_total'] = $payItem['item_price'] * $quantity;
            } else if ($payType == 'choose_multiple') {
                $selctedItems = $formData[$paymentId];
                $pricings = $priceDetailes['multiple_pricing'];
                $payItems = array();
                foreach ($selctedItems as $itemIndex => $selctedItem) {
                    $itemClone = $payItem;
                    $itemClone['item_name'] = $pricings[$itemIndex]['label'];
                    $itemClone['item_price'] = wpPayFormConverToCents($pricings[$itemIndex]['value']);
                    $itemClone['line_total'] = $itemClone['item_price'] * $quantity;
                    $payItems[] = $itemClone;
                }
                return $payItems;
            } else {
                $payItem['item_price'] = wpPayFormConverToCents(ArrayHelper::get($priceDetailes, 'payment_amount'));
                $payItem['line_total'] = $payItem['item_price'] * $quantity;
            }
        } else if ($payment['type'] == 'custom_payment_input') {
            $payItem['item_price'] = wpPayFormConverToCents(floatval($formData[$paymentId]));
            $payItem['line_total'] = $payItem['item_price'] * $quantity;
        } else {
            return array();
        }
        return array($payItem);
    }

    private function getSubscriptionLine($payment, $paymentId, $quantity, $formData, $formId)
    {

        if (!defined('WPPAYFORM_PRO_INSTALLED')) {
            return [];
        }

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

        $paymentIndex = $formData[$paymentId];
        $plan = $pricings[$paymentIndex];

        if (!$plan) {
            return array();
        }

        if (ArrayHelper::get($plan, 'user_input') == 'yes') {
            $plan['subscription_amount'] = ArrayHelper::get($formData, $paymentId . '__' . $paymentIndex);
        }


        if ($plan['bill_times'] == 1) {
            // We can convert this as one time payment
            // This plan should not have trial
            if ($plan['has_trial_days'] != 'yes') {
                $signupFee = 0;
                if($plan['has_signup_fee'] == 'yes') {
                    $signupFee = wpPayFormConverToCents($plan['signup_fee']);
                }
                $onetimeTotal = $signupFee + wpPayFormConverToCents($plan['subscription_amount']);
                return [
                    'type' => 'single',
                    'parent_holder' => $paymentId,
                    'item_name' => $label,
                    'quantity' => $quantity,
                    'item_price' => $onetimeTotal,
                    'line_total' => $quantity * $onetimeTotal,
                    'created_at'       => gmdate('Y-m-d H:i:s'),
                    'updated_at'       => gmdate('Y-m-d H:i:s')
                ];
            }
        }

        $subscription = array(
            'element_id'       => $paymentId,
            'item_name'        => $label,
            'form_id'          => $formId,
            'plan_name'        => $plan['name'],
            'billing_interval' => $plan['billing_interval'],
            'trial_days'       => 0,
            'recurring_amount' => wpPayFormConverToCents($plan['subscription_amount']),
            'bill_times'       => $plan['bill_times'],
            'initial_amount'   => 0,
            'status'           => 'pending',
            'original_plan'    => maybe_serialize($plan),
            'created_at'       => gmdate('Y-m-d H:i:s'),
            'updated_at'       => gmdate('Y-m-d H:i:s'),
        );

        if (ArrayHelper::get($plan, 'has_signup_fee') == 'yes' && ArrayHelper::get($plan, 'signup_fee')) {
            $subscription['initial_amount'] = wpPayFormConverToCents($plan['signup_fee']);
        }

        if (ArrayHelper::get($plan, 'has_trial_days') == 'yes' && ArrayHelper::get($plan, 'trial_days')) {
            $subscription['trial_days'] = $plan['trial_days'];
            $expirationDate = gmdate('Y-m-d H:i:s', time() + absint($plan['trial_days']) * 86400);
            $subscription['expiration_at'] = $expirationDate;
        }

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

            if (strpos($confirmation['messageToShow'], '[wppayform_reciept]') !== false) {
                $modifiedShortcode = '[wppayform_reciept hash="' . $submission->submission_hash . '"]';
                $confirmation['messageToShow'] = str_replace('[wppayform_reciept]', $modifiedShortcode, $confirmation['messageToShow']);
            }

            $confirmation['messageToShow'] = do_shortcode($confirmation['messageToShow']);
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

    public function getFormConfirmation($formId, $submission)
    {
        $confirmation = Forms::getConfirmationSettings($formId);
        $confirmation = $this->parseConfirmation($confirmation, $submission);
        return apply_filters('wppayform/form_confirmation', $confirmation, $submission->id, $formId);
    }
}