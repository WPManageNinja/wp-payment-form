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
class SubmissionView
{
    public function __construct()
    {
        $this->registerAjaxEndpoints();
    }

    public function registerAjaxEndpoints()
    {
        add_action('wp_ajax_wpf_get_submissions', array($this, 'getSubmissions'));
        add_action('wp_ajax_wpf_get_submission', array($this, 'getSubmission'));
        add_action('wp_ajax_wpf_get_available_forms', array($this, 'getAvailableForms'));
    }

    public function getSubmissions()
    {
        $formId = false;
        if (isset($_REQUEST['form_id']) && $_REQUEST['form_id']) {
            $formId = absint($_REQUEST['form_id']);
        }

        $page = absint($_REQUEST['page_number']);
        $perPage = absint($_REQUEST['per_page']);
        $skip = ($page - 1) * $perPage;

        $wheres = array();

        if(isset($_REQUEST['payment_status']) && $_REQUEST['payment_status']) {
            $wheres['payment_status'] = sanitize_text_field($_REQUEST['payment_status']);
        }

        $submissionModel = new Submission();
        $submissions = $submissionModel->getSubmissions($formId, $wheres, $perPage, $skip);
        wp_send_json_success(array(
            'submissions' => $submissions->items,
            'total'       => (int)$submissions->total
        ), 200);
    }

    public function getSubmission()
    {
        $formId = absint($_REQUEST['form_id']);
        $submissionId = absint($_REQUEST['submission_id']);
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId, array('transactions', 'order_items'));
        wp_send_json_success(array(
            'submission'    => $submission,
            'entry'         => (object) $submissionModel->getParsedSubmission($submission)
        ), 200);
    }

    public function getAvailableForms()
    {
        wp_send_json_success(array(
            'available_forms' => Forms::getAllAvailableForms()
        ), 200);
    }

}
