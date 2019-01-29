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
            $paymentItems[] = $this->getPaymentLine($payment, $paymentId, $quantity, $form_data);
        }
        $inputItems = array();
        foreach ($formattedElements['input'] as $inputId => $input) {
            $inputItems[$inputId] = ArrayHelper::get($form_data, $inputId);
        }

        $paymentTotal = 0;
        foreach ($paymentItems as $paymentItem) {
            $paymentTotal += $paymentItem['line_total'];
        }
        $currency = 'usd';

        $currentUserId = get_current_user_id();
        if(!$customerName && $currentUserId) {
            $currentUser = get_user_by('ID', $currentUserId);
            $customerName = $currentUser->display_name;
        }

        if(!$customerEmail && $currentUserId) {
            $currentUser = get_user_by('ID', $currentUserId);
            $customerEmail = $currentUser->user_email;
        }

        $submission = array(
            'form_id' => $formId,
            'user_id' => $currentUserId,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'form_data_raw' => maybe_serialize($form_data),
            'form_data_formatted' => maybe_serialize($inputItems),
            'currency' => $currency,
            'payment_status' => 'draft',
            'payment_total' => $paymentTotal,
            'status' => 'unread',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        );

        // Insert Submission
        $submissionModel = new Submission();
        $submissionId = $submissionModel->create($submission);

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
            'payment_method' => 'stripe',
            'charge_id' => '',
            'payment_total' => $paymentTotal,
            'status' => 'draft',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        );

        $transactionModel = new Transaction();
        $transactionId = $transactionModel->create($transaction);

        // Now We can charge the customer
        // We will do this later
        // @todo: Charge The Customer now and then update corresponding records

        wp_send_json_success(array(
            'message' => __('Your Payment is successfully recorded', 'wppayform'),
            'submission_id' =>  $submissionId
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
        $priceDetailes = ArrayHelper::get($payment, 'options.pricing_details');
        $payType = ArrayHelper::get($priceDetailes, 'one_time_type');
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
        if($payType == 'choose_single') {
            $pricings = $priceDetailes['multiple_pricing'];
            $price = $pricings[$formData[$paymentId]];
            $payItem['item_name'] = $price['label'];
            $payItem['item_price'] = absint($price['value'] * 100);
            $payItem['line_total'] = $payItem['item_price'] * $quantity;
        } else {
            $payItem['item_price'] = absint(ArrayHelper::get($priceDetailes, 'payment_amount') * 100);
            $payItem['line_total'] = absint($payItem['item_price']) * $quantity;
        }
        return $payItem;
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