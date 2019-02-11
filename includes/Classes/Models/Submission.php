<?php

namespace WPPayForm\Classes\Models;

use WPPayForm\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manage Submission
 * @since 1.0.0
 */
class Submission
{
    public function create($submission)
    {
        return wpPayformDB()->table('wpf_submissions')
            ->insert($submission);
    }

    public function getSubmissions($formId = false, $wheres = array(), $perPage, $skip)
    {
        $resultQuery = wpPayformDB()->table('wpf_submissions')
            ->select(array('wpf_submissions.*', 'posts.post_title'))
            ->join('posts', 'posts.ID', '=', 'wpf_submissions.form_id')
            ->orderBy('wpf_submissions.id', 'DESC')
            ->limit($perPage)
            ->offset($skip);

        if ($formId) {
            $resultQuery->where('wpf_submissions.form_id', $formId);
        }

        foreach ($wheres as $whereKey => $where) {
            $resultQuery->where('wpf_submissions.' . $whereKey, $where);
        }

        $totalItems = $resultQuery->count();

        $results = $resultQuery->get();

        $formattedResults = array();
        foreach ($results as $result) {
            $result->form_data_raw = maybe_unserialize($result->form_data_raw);
            $result->form_data_formatted = maybe_unserialize($result->form_data_formatted);
            $formattedResults[] = $result;
        }
        return (object)array(
            'items' => $results,
            'total' => $totalItems
        );
    }

    public function getSubmission($submissionId, $with = array())
    {

        $result = wpPayformDB()->table('wpf_submissions')
            ->select(array('wpf_submissions.*', 'posts.post_title'))
            ->join('posts', 'posts.ID', '=', 'wpf_submissions.form_id')
            ->where('wpf_submissions.id', $submissionId)
            ->first();

        $result->form_data_raw = maybe_unserialize($result->form_data_raw);
        $result->form_data_formatted = maybe_unserialize($result->form_data_formatted);
        if ($result->user_id) {
            $result->user_profile_url = get_edit_user_link($result->user_id);
        }

        if (in_array('transactions', $with)) {
            $result->transactions = (new Transaction())->getTransactions($submissionId);
        }

        if (in_array('order_items', $with)) {
            $result->order_items = (new OrderItem())->getOrderItems($submissionId);
        }

        if (in_array('activities', $with)) {
            $result->activities = SubmissionActivity::getSubmissionActivity($submissionId);
        }
        return $result;
    }

    public function getTotalCount($formId = false)
    {
        $query = wpPayformDB()->table('wpf_submissions');
        if ($formId) {
            $query = $query->where('form_id', $formId);
        }
        return $query->count();
    }

    public function update($submissionId, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return wpPayformDB()->table('wpf_submissions')->where('id', $submissionId)->update($data);
    }

    public function getParsedSubmission($submission)
    {
        $elements = get_post_meta($submission->form_id, '_wp_paymentform_builder_settings', true);
        if (!$elements) {
            return array();
        }
        $parsedSubmission = array();

        $inputValues = $submission->form_data_formatted;

        foreach ($elements as $element) {
            if ($element['group'] == 'input') {
                $elementId = ArrayHelper::get($element, 'id');
                $elementValue = apply_filters('wppayform/rendering_entry_value_' . $element['type'], ArrayHelper::get($inputValues, $elementId));
                if (is_array($elementValue)) {
                    $elementValue = implode(', ', $elementValue);
                }
                $parsedSubmission[$elementId] = array(
                    'label' => $this->getLabel($element),
                    'value' => $elementValue,
                    'type'  => $element['type']
                );
            }
        }

        return apply_filters('wppayform/parsed_entry', $parsedSubmission, $submission);
    }

    private function getLabel($element)
    {
        $elementId = ArrayHelper::get($element, 'id');
        if (!$label = ArrayHelper::get($element, 'field_options.admin_label')) {
            $label = ArrayHelper::get($element, 'field_options.label');
        }
        if (!$label) {
            $label = $elementId;
        }
        return $label;
    }

    public function deleteSubmission($sumissionId)
    {
        wpPayformDB()->table('wpf_submissions')
            ->where('id', $sumissionId)
            ->delete();

        wpPayformDB()->table('wpf_order_items')
            ->where('submission_id', $sumissionId)
            ->delete();

        wpPayformDB()->table('wpf_order_transactions')
            ->where('submission_id', $sumissionId)
            ->delete();

        wpPayformDB()->table('wpf_submission_activities')
            ->where('submission_id', $sumissionId)
            ->delete();
    }
}