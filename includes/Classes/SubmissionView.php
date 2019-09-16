<?php

namespace WPPayForm\Classes;

use WPPayForm\Classes\Models\Forms;
use WPPayForm\Classes\Models\Submission;
use WPPayForm\Classes\Models\SubmissionActivity;
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
    public function registerEndpoints()
    {
        add_action('wp_ajax_wpf_submission_endpoints', array($this, 'routeAjaxMaps'));
    }

    public function routeAjaxMaps()
    {
        $routes = array(
            'get_submissions'          => 'getSubmissions',
            'get_submission'           => 'getSubmission',
            'get_available_forms'      => 'getAvailableForms',
            'get_next_prev_submission' => 'getNextPrevSubmission',
            'add_submission_note'      => 'addSubmissionNote',
            'change_payment_status'    => 'changePaymentStatus',
            'delete_submission'        => 'deleteSubmission',
            'get_form_report'          => 'getFormReport'
        );
        $route = sanitize_text_field($_REQUEST['route']);

        if (isset($routes[$route])) {
            AccessControl::checkAndPresponseError($route, 'submissions');
            do_action('wppayform/doing_ajax_submissions_' . $route);
            $this->{$routes[$route]}();
            return;
        }
    }

    public function getSubmissions()
    {
        $formId = false;
        if (isset($_REQUEST['form_id']) && $_REQUEST['form_id']) {
            $formId = absint($_REQUEST['form_id']);
        }
        $searchString = sanitize_text_field($_REQUEST['search_string']);

        $page = absint($_REQUEST['page_number']);
        $perPage = absint($_REQUEST['per_page']);
        $skip = ($page - 1) * $perPage;

        $wheres = array();

        if (isset($_REQUEST['payment_status']) && $_REQUEST['payment_status']) {
            $wheres['payment_status'] = sanitize_text_field($_REQUEST['payment_status']);
        }

        $submissionModel = new Submission();
        $submissions = $submissionModel->getSubmissions($formId, $wheres, $perPage, $skip, 'DESC', $searchString);

        $currencySettings = GeneralSettings::getGlobalCurrencySettings($formId);

        foreach ($submissions->items as $submission) {
            $currencySettings['currency_sign'] = GeneralSettings::getCurrencySymbol($submission->currency);
            $submission->currencySettings = $currencySettings;
        }

        $submissionItems = apply_filters('wppayform/form_entries', $submissions->items, $formId);

        $hasPaymentItem = true;

        if ($formId) {
            $hasPaymentItem = Forms::hasPaymentFields($formId);
        }

        wp_send_json_success(array(
            'submissions'    => $submissionItems,
            'total'          => (int)$submissions->total,
            'hasPaymentItem' => $hasPaymentItem
        ), 200);

    }

    public function getSubmission($submissionId = false)
    {
        $formId = absint($_REQUEST['form_id']);
        if (!$submissionId) {
            $submissionId = absint($_REQUEST['submission_id']);
        }

        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId, array('transactions', 'order_items', 'tax_items', 'activities', 'refunds'));

        $currencySetting = GeneralSettings::getGlobalCurrencySettings($formId);
        $currencySetting['currency_sign'] = GeneralSettings::getCurrencySymbol($submission->currency);
        $submission->currencySetting = $currencySetting;

        if ($submission->user_id) {
            $user = get_user_by('ID', $submission->user_id);
            if ($user) {
                $submission->user = [
                    'display_name' => $user->display_name,
                    'profile_url' => get_edit_user_link($user->ID)
                ];
            }
        }

        $submission = apply_filters('wppayform/form_entry', $submission);

        $parsedEntry = (object)$submissionModel->getParsedSubmission($submission);

        wp_send_json_success(array(
            'submission' => $submission,
            'entry'      => $parsedEntry
        ), 200);
    }

    public function getNextPrevSubmission()
    {
        $formId = false;
        if (isset($_REQUEST['form_id'])) {
            $formId = absint($_REQUEST['form_id']);
        }

        $currentSubmissionId = absint($_REQUEST['current_submission_id']);
        $queryType = sanitize_text_field($_REQUEST['type']);

        $whereOperator = '<';
        $orderBy = 'DESC';
        // find the next / previous form id
        if ($queryType == 'prev') {
            $whereOperator = '>';
            $orderBy = 'ASC';
        }

        $submissionQuery = wpPayFormDB()->table('wpf_submissions')
            ->orderBy('id', $orderBy)
            ->where('id', $whereOperator, $currentSubmissionId);

        if ($formId) {
            $submissionQuery->where('form_id', $formId);
        }


        $submission = $submissionQuery->first();

        if (!$submission) {
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

    public function addSubmissionNote()
    {
        $formId = intval($_REQUEST['form_id']);
        $submissionId = intval($_REQUEST['submission_id']);
        $content = esc_html($_REQUEST['note']);
        $userId = get_current_user_id();
        $user = get_user_by('ID', $userId);

        $note = array(
            'form_id'            => $formId,
            'submission_id'      => $submissionId,
            'type'               => 'custom_note',
            'content'            => $content,
            'created_by'         => $user->display_name,
            'created_by_user_id' => $userId
        );

        $note = apply_filters('wppayform/add_note_by_user', $note, $formId, $submissionId);
        do_action('wppayform/before_create_note_by_user', $note);
        SubmissionActivity::createActivity($note);
        do_action('wppayform/after_create_note_by_user', $note);

        wp_send_json_success(array(
            'message'    => __('Note successfully added', 'wppayform'),
            'activities' => SubmissionActivity::getSubmissionActivity($submissionId)
        ), 200);
    }

    public function changePaymentStatus()
    {
        $submissionId = intval($_REQUEST['submission_id']);
        $newStatus = sanitize_text_field($_REQUEST['new_payment_status']);
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId);
        if ($submission->payment_status == $newStatus) {
            wp_send_json_error(array(
                'message' => __('The submission have the same status', 'wppayform')
            ), 423);
        }

        do_action('wppayform/before_payment_status_change_manually', $submission, $newStatus, $submission->payment_status);
        $submissionModel->update($submissionId, array(
            'payment_status' => $newStatus
        ));
        wpPayFormDB()->table('wpf_order_transactions')->where('submission_id', $submissionId)->update(array(
            'status'     => $newStatus,
            'updated_at' => gmdate('Y-m-d H:i:s')
        ));
        do_action('wppayform/after_payment_status_change_manually', $submissionId, $newStatus, $submission->payment_status);

        $activityContent = 'Payment status changed from <b>' . $submission->payment_status . '</b> to <b>' . $newStatus . '</b>';

        if (isset($_REQUEST['status_change_note']) && $_REQUEST['status_change_note']) {
            $note = wp_kses_post($_REQUEST['status_change_note']);
            $activityContent .= '<br />Note: ' . $note;
        }

        $userId = get_current_user_id();
        $user = get_user_by('ID', $userId);
        SubmissionActivity::createActivity(array(
            'form_id'            => $submission->form_id,
            'submission_id'      => $submission->id,
            'type'               => 'info',
            'created_by'         => $user->display_name,
            'created_by_user_id' => $userId,
            'content'            => $activityContent
        ));

        wp_send_json_success(array(
            'message' => __('Payment status successfully changed', 'wppayform')
        ), 200);
    }

    public function deleteSubmission()
    {
        $submissionId = intval($_REQUEST['submission_id']);
        $formId = intval($_REQUEST['form_id']);
        do_action('wppayform/before_delete_submission', $submissionId, $formId);
        $submissionModel = new Submission();
        $submissionModel->deleteSubmission($submissionId);
        do_action('wppayform/after_delete_submission', $submissionId, $formId);

        wp_send_json_success(array(
            'message' => __('Selected submission successfully deleted', 'wppayform')
        ), 200);
    }

    public function getFormReport()
    {
        $formId = absint($_REQUEST['form_id']);
        $paymentStatuses = GeneralSettings::getPaymentStatuses();

        $submissionModel = new Submission();
        $reports = [];
        $reports[''] = [
            'label'            => 'All',
            'submission_count' => $submissionModel->getTotalCount($formId),
            'payment_total'    => $submissionModel->paymentTotal($formId)
        ];

        foreach ($paymentStatuses as $status => $statusName) {
            $reports[$status] = [
                'label'            => $statusName,
                'submission_count' => $submissionModel->getTotalCount($formId, $status),
                'payment_total'    => $submissionModel->paymentTotal($formId, $status)
            ];
        }

        wp_send_json_success([
            'reports'          => $reports,
            'currencySettings' => Forms::getCurrencyAndLocale($formId),
            'is_payment_form'  => Forms::hasPaymentFields($formId)
        ], 200);
    }
}
