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
        add_action('wp_ajax_wpf_get_next_prev_submission', array($this, 'getNextPrevSubmission'));
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

        $currencySettings = GeneralSettings::getGlobalCurrencySettings($formId);

        foreach ($submissions->items as $submission) {
            $currencySettings['currency_sign'] = GeneralSettings::getCurrencySymbol($submission->currency);
            $submission->currencySettings = $currencySettings;
        }

        wp_send_json_success(array(
            'submissions' => $submissions->items,
            'total'       => (int)$submissions->total
        ), 200);
    }

    public function getSubmission($submissionId = false)
    {
        $formId = absint($_REQUEST['form_id']);
        if(!$submissionId) {
            $submissionId = absint($_REQUEST['submission_id']);
        }
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId, array('transactions', 'order_items', 'activities'));

        $currencySetting = GeneralSettings::getGlobalCurrencySettings($formId);
        $currencySetting['currency_sign'] = GeneralSettings::getCurrencySymbol($submission->currency);
        $submission->currencySetting = $currencySetting;

        wp_send_json_success(array(
            'submission'    => $submission,
            'entry'         => (object) $submissionModel->getParsedSubmission($submission)
        ), 200);
    }

    public function getNextPrevSubmission()
    {
        $formId = false;
        if(isset($_REQUEST['form_id'])) {
            $formId = absint($_REQUEST['form_id']);
        }

        $currentSubmissionId = absint($_REQUEST['current_submission_id']);
        $queryType = sanitize_text_field($_REQUEST['type']);

        $whereOperator = '<';
        $orderBy = 'DESC';
        // find the next / previous form id
        if($queryType == 'prev') {
            $whereOperator = '>';
            $orderBy = 'ASC';
        }

        $submissionQuery = wpPayformDB()->table('wpf_submissions')
                        ->orderBy('id', $orderBy)
                        ->where('id', $whereOperator, $currentSubmissionId);

        if($formId) {
            $submissionQuery->where('form_id', $formId);
        }


        $submission = $submissionQuery->first();

        if(!$submission) {
            wp_send_json_error(array(
                'message' => __('Sorry, No Submission found', 'wppayform')
            ), 423);
        }

        $this->getSubmission($submission->id);
    }

    public function getAvailableForms()
    {
        wp_send_json_success(array(
            'available_forms' => Forms::getAllAvailableForms()
        ), 200);
    }

}
