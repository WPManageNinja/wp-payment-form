<?php

namespace WPPayForm\Classes;

use WPPayForm\Classes\Models\Forms;
use WPPayForm\Classes\Models\OrderItem;
use WPPayForm\Classes\Models\Submission;
use WPPayForm\Classes\Models\Transaction;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Form Submission Handler
 * @since 1.0.0
 */
class SubmissionHandler
{
    public function registerActions()
    {
        add_action('wp_ajax_wpf_submit_form', array($this, 'handeSubmission'));
        add_action('wp_ajax_nopriv_wpf_submit_form', array($this, 'handeSubmission'));
    }

    public function handeSubmission()
    {
        parse_str($_REQUEST['form_data'], $form_data);
        // Now Validate the form please
        $formId = absint($_REQUEST['form_id']);
        // Get Original Form Elements Now
        $elements = Forms::getBuilderSettings($formId);
        $form = Forms::getForm($formId);

        $formattedElements = array(
            'input'         => array(),
            'payment'       => array(),
            'item_quantity' => array()
        );

        foreach ($elements as $element) {
            $formattedElements[$element['group']][$element['id']] = array(
                'options' => $element['field_options'],
                'type'    => $element['type']
            );
        }
        $errors = array();

        $customerName = '';
        $customerEmail = '';

        // Validate Normal Inputs
        foreach ($formattedElements['input'] as $elementId => $element) {
            if (ArrayHelper::get($element, 'options.required') == 'yes' && empty($form_data[$elementId])) {
                $errors[$elementId] = $this->getErrorLabel($element, $formId);
            }
            if($element['type'] == 'customer_name' && !$customerName && isset($form_data[$elementId])) {
                $customerName = $form_data[$elementId];
            } else if($element['type'] == 'customer_email' && !$customerEmail && isset($form_data[$elementId])) {
                $customerEmail = $form_data[$elementId];
            }
        }

        // Validate Payment Fields
        foreach ($formattedElements['payment'] as $elementId => $element) {
            if (ArrayHelper::get($element, 'options.required') == 'yes' && !isset($form_data[$elementId])) {
                $errors[$elementId] = $this->getErrorLabel($element, $formId);
            }
        }

        foreach ($formattedElements['item_quantity'] as $elementId => $element) {
            if (isset($form_data[ArrayHelper::get($element, 'options.target_product')])) {
                if (ArrayHelper::get($element, 'options.required') == 'yes' && empty($form_data[$elementId])) {
                    $errors[$elementId] = $this->getErrorLabel($element, $formId);
                }
            }
        }

        $errors = apply_filters('wpf_submission_form_validation', $errors, $formId, $formattedElements);

        if ($errors) {
            wp_send_json_error(array(
                'message' => __('Form Validation failed', 'wppayform'),
                'errors'  => $errors
            ), 423);
        }

        // Calculare Payment total Now
        $paymentTotal = 0;
        $paymentItems = array();
        foreach ($formattedElements['payment'] as $paymentId => $payment) {
            $quantity = $this->getItemQuantity($formattedElements['item_quantity'], $paymentId, $form_data);
            $lineItems = $this->getPaymentLine($payment, $paymentId, $quantity, $form_data);
            $paymentItems = array_merge($paymentItems, $lineItems);
        }
        $inputItems = array();
        foreach ($formattedElements['input'] as $inputId => $input) {
            $inputItems[$inputId] = ArrayHelper::get($form_data, $inputId);
        }

        $paymentTotal = 0;
        foreach ($paymentItems as $paymentItem) {
            $paymentTotal += $paymentItem['line_total'];
        }

        $currentUserId = get_current_user_id();
        if(!$customerName && $currentUserId) {
            $currentUser = get_user_by('ID', $currentUserId);
            $customerName = $currentUser->display_name;
        }

        if(!$customerEmail && $currentUserId) {
            $currentUser = get_user_by('ID', $currentUserId);
            $customerEmail = $currentUser->user_email;
        }

        // We have to make these dynamic
        $paymentMode = 'test';
        $paymentMethod = 'stripe';
        $currency = 'usd';
        
        $inputItems = apply_filters('wpf_form_data_formatted_input', $inputItems, $form_data, $formId);

        $submission = array(
            'form_id' => $formId,
            'user_id' => $currentUserId,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'form_data_raw' => maybe_serialize($form_data),
            'form_data_formatted' => maybe_serialize(wp_unslash($inputItems)),
            'currency' => $currency,
            'payment_status' => 'pending',
            'payment_total' => $paymentTotal,
            'payment_method' => $paymentMethod,
            'payment_mode' => $paymentMode,
            'status' => 'unread',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        );

        // Insert Submission
        $submissionModel = new Submission();
        $submissionId = $submissionModel->create($submission);

        if($paymentItems) {
            // Insert Payment Items
            $itemModel = new OrderItem();
            foreach ($paymentItems as $payItem) {
                $payItem['submission_id'] = $submissionId;
                $payItem['form_id'] = $formId;
                $itemModel->create($payItem);
            }
            // Insert Transaction Item Now
            $transaction = array(
                'form_id' => $formId,
                'user_id' => $currentUserId,
                'submission_id' => $submissionId,
                'payment_method' => $paymentMethod,
                'payment_mode' => $paymentMode,
                'charge_id' => '',
                'payment_total' => $paymentTotal,
                'currency' => $currency,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            );

            $transactionModel = new Transaction();
            $transactionId = $transactionModel->create($transaction);
            do_action('wpf_form_submission_make_payment_'.$paymentMethod, $transactionId, $submissionId, $form_data, $form);
        }

        wp_send_json_success(array(
            'message' => __('Your Payment is successfully recorded', 'wppayform'),
            'submission_id' =>  $submissionId,
            'submission' => (new Submission())->getSubmission($submissionId, array('transactions', 'order_items'))
        ), 200);
    }

    private function getItemQuantity($quantityElements, $tragetItemId, $formData)
    {
          if(!$quantityElements) {
              return 1;
          }
          foreach ($quantityElements as $key => $element) {
              if(ArrayHelper::get($element, 'options.target_product') == $tragetItemId) {
                  return absint($formData[$key]);
              }
          }
          return 1;
    }

    private function getPaymentLine($payment, $paymentId, $quantity, $formData)
    {
        if(!isset($formData[$paymentId])) {
            return array();
        }
        $label = ArrayHelper::get($payment, 'options.label');
        if(!$label) {
            $label = $paymentId;
        }
        $payItem = array(
            'type' => 'single',
            'item_name' => $label,
            'quantity' => $quantity,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        );

        if($payment['type'] == 'payment_item') {
            $priceDetailes = ArrayHelper::get($payment, 'options.pricing_details');
            $payType = ArrayHelper::get($priceDetailes, 'one_time_type');
            if($payType == 'choose_single') {
                $pricings = $priceDetailes['multiple_pricing'];
                $price = $pricings[$formData[$paymentId]];
                $payItem['item_name'] = $price['label'];
                $payItem['item_price'] = absint($price['value'] * 100);
                $payItem['line_total'] = $payItem['item_price'] * $quantity;
            } else if($payType == 'choose_multiple') {
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
        } else if($payment['type'] == 'custom_payment_input') {
            $payItem['item_price'] = absint($formData[$paymentId]) * 100;
            $payItem['line_total'] = absint($payItem['item_price']) * $quantity;
        }
        return array($payItem);
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
        return apply_filters('wpf_error_label_text', $label, $element, $formId);
    }

}