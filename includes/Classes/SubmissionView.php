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
    }

    public function getSubmission()
    {
        $formId = absint($_REQUEST['form_id']);
        $submissionId = absint($_REQUEST['submission_id']);
        $submissionModel = new Submission();
        wp_send_json_success( array(
            'submission' => $submissionModel->getSubmission($submissionId)
        ), 200 );
    }

    public function getSubmissions()
    {
        $formId = absint($_REQUEST['form_id']);
        $page = absint($_REQUEST['page_number']);
        $perPage = absint($_REQUEST['per_page']);
        $skip = ($page - 1) * $perPage;

        $submissionModel = new Submission();
        $submissions = $submissionModel->getSubmissions($formId, $perPage, $skip);
        $totalSubmissions = $submissionModel->getTotalCount($formId);
        wp_send_json_success(array(
            'submissions' => $submissions,
            'total' => (int) $totalSubmissions
        ), 200);
    }


}
