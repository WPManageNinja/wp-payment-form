<?php

namespace WPPayForm\Classes\Builder;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\Entry\Entry;
use WPPayForm\Classes\Models\Forms;
use WPPayForm\Classes\Models\Submission;
use WPPayForm\Classes\PlaceholderParser;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Recept Shortcode Handler
 * @since 1.0.0
 */
class PaymentReceipt
{
    public function render($submissionId)
    {
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId, array('transactions', 'order_items', 'tax_items', 'subscriptions'));

        if (!$submission) {
            return '<p class="wpf_invalid_receipt">' . __('Invalid subission. No receipt found', 'wppayform') . '</p>';
        }

        $receiptSettings = Forms::getReceiptSettings($submission->form_id);
        $receiptSettings['receipt_header'] = PlaceholderParser::parse($receiptSettings['receipt_header'], $submission);
        $receiptSettings['receipt_footer'] = PlaceholderParser::parse($receiptSettings['receipt_footer'], $submission);

        $submission->parsedData = $submissionModel->getParsedSubmission($submission);

        $html = $this->beforePaymentReceipt($submission, $receiptSettings);
        $html .= $this->paymentReceptHeader($submission, $receiptSettings);
        $html .= $this->paymentInfo($submission, $receiptSettings);
        $html .= $this->recurringPaymentInfo($submission, $receiptSettings);
        $html .= $this->itemDetails($submission, $receiptSettings);
        $html .= $this->submissionDetails($submission, $receiptSettings);
        $html .= $this->paymentReceptFooter($submission, $receiptSettings);
        $html .= $this->afterPaymentReceipt($submission, $receiptSettings);
        $html .= $this->loadCss($submission);
        return $html;
    }

    private function beforePaymentReceipt($submission, $receiptSettings)
    {
        ob_start();
        echo '<div class="wpf_payment_receipt">';
        do_action('wppayform/payment_receipt/before_content', $submission, $receiptSettings);
        return ob_get_clean();
    }

    private function afterPaymentReceipt($submission, $receiptSettings)
    {
        ob_start();
        do_action('wppayform/payment_receipt/after_content', $submission, $receiptSettings);
        echo '</div>';
        return ob_get_clean();
    }

    private function paymentReceptHeader($submission, $receiptSettings)
    {
        $preRender = apply_filters('wppayform/payment_receipt/pre_render_header', '', $submission, $receiptSettings);
        if ($preRender) {
            // We are returning the header if someone want to render the recept. peace!!!
            return $preRender;
        }
        return $this->loadView('receipt/header', array(
            'submission'     => $submission,
            'header_content' => $receiptSettings['receipt_header']
        ));
    }

    private function paymentReceptFooter($submission, $receiptSettings)
    {
        $preRender = apply_filters('wppayform/payment_receipt/pre_render_footer', '', $submission, $receiptSettings);
        if ($preRender) {
            // We are returning the header if someone want to render the recept. peace!!!
            return $preRender;
        }

        if(!$receiptSettings['receipt_footer']) {
            return '';
        }

        return '<div class="wpf_receipt_footer">'.$receiptSettings['receipt_footer'].'</div>';
    }

    private function paymentInfo($submission, $receiptSettings)
    {
        $preRender = apply_filters('wppayform/payment_receipt/pre_render_payment_info', '', $submission);
        if ($preRender) {
            return $preRender;
        }

        if (ArrayHelper::get($receiptSettings, 'info_modules.payment_info') != 'yes') {
            return;
        }

        if(!$submission->order_items) {
            return;
        }

        return $this->loadView('receipt/payment_info', array(
            'submission' => $submission
        ));

        return '';
    }

    private function itemDetails($submission, $receiptSettings)
    {
        $preRender = apply_filters('wppayform/payment_receipt/pre_render_item_details', '', $submission, $receiptSettings);
        if ($preRender) {
            return $preRender;
        }

        if (ArrayHelper::get($receiptSettings, 'info_modules.payment_info') != 'yes') {
            return;
        }

        $header = '<h4>' . __('Items Details', 'wppayform') . '</h4>';
        $html = $this->loadView('elements/order_items_table', array(
            'submission' => $submission
        ));

        if (!$html) {
            return '';
        }
        return $header . $html;
    }

    private function submissionDetails($submission, $receiptSettings)
    {
        $preRender = apply_filters('wppayform/payment_receipt/pre_render_submission_details', '', $submission, $receiptSettings);
        if ($preRender) {
            return $preRender;
        }

        if (ArrayHelper::get($receiptSettings, 'info_modules.input_details') != 'yes') {
            return;
        }

        $entry = new Entry($submission);

        return $this->loadView('receipt/customer_details', array(
            'submission' => $submission,
            'submission_details' => $entry->getInputFieldsHtmlTable()
        ));
    }

    private function recurringPaymentInfo($submission, $receiptSettings)
    {

        if (ArrayHelper::get($receiptSettings, 'info_modules.payment_info') != 'yes') {
            return;
        }

        if (property_exists($submission, 'subscriptions') && $submission->subscriptions) {
            $preRender = apply_filters('wppayform/payment_receipt/pre_render_subscription_details', '', $submission);
            if ($preRender) {
                return $preRender;
            }
            $header = '<h4>' . __('Subscription Details', 'wppayform') . '</h4>';
            $html = $this->loadView('elements/subscriptions_info', array(
                'submission'     => $submission,
                'load_table_css' => false
            ));
            return $header . $html;
        }
    }

    private function loadCss($submission)
    {
        return $this->loadView('receipt/custom_css', array('submission' => $submission));
    }

    public function loadView($fileName, $data)
    {
        // normalize the filename
        $fileName = str_replace(array('../', './'), '', $fileName);
        $basePath = apply_filters('wppayform/receipt_template_base_path', WPPAYFORM_DIR . 'includes/views/', $fileName, $data);
        $filePath = $basePath . $fileName . '.php';
        extract($data);
        ob_start();
        include $filePath;
        return ob_get_clean();
    }
}