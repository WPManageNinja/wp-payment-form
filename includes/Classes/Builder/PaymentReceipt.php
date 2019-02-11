<?php
namespace WPPayForm\Classes\Builder;
use WPPayForm\Classes\Models\Submission;

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
        $submission = $submissionModel->getSubmission($submissionId, array('transactions', 'order_items'));
        $submission->parsedData = $submissionModel->getParsedSubmission($submission);
        $html = $this->beforePaymentReceipt($submission);
        $html .= $this->paymentReceptHeader($submission);
        $html .= $this->paymentInfo($submission);
        $html .= $this->itemDetails($submission);
        $html .= $this->customerDetails($submission);
        $html .= $this->afterPaymentReceipt($submission);
        return $html;
    }

    private function beforePaymentReceipt($submission)
    {
        ob_start();
        do_action('wppayform/payment_receipt/before_content', $submission);
        return ob_get_clean();
    }

    private function afterPaymentReceipt($submission)
    {
        ob_start();
        do_action('wppayform/payment_receipt/after_content', $submission);
        return ob_get_clean();
    }

    private function paymentReceptHeader($submission)
    {
        $preRender = apply_filters('wppayform/payment_receipt/pre_render_header', '', $submission);
        if ($preRender) {
            // We are returning the header if someone want to render the recept. peace!!!
            return $preRender;
        }
        return $this->loadView('receipt/header', array('submission' => $submission));
    }

    private function paymentInfo($submission)
    {
        $preRender = apply_filters('wppayform/payment_receipt/pre_render_payment_info', '', $submission);
        if ($preRender) {
            return $preRender;
        }
        return $this->loadView('receipt/payment_info', array('submission' => $submission));
    }

    private function itemDetails($submission)
    {
        $preRender = apply_filters('wppayform/payment_receipt/pre_render_item_details', '', $submission);
        if ($preRender) {
            return $preRender;
        }
        return $this->loadView('receipt/item_table', array('submission' => $submission));
    }

    private function customerDetails($submission)
    {
        $preRender = apply_filters('wppayform/payment_receipt/pre_render_customer_details', '', $submission);
        if ($preRender) {
            return $preRender;
        }
        return $this->loadView('receipt/customer_details', array('submission' => $submission));
    }

    private function loadView($fileName, $data)
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