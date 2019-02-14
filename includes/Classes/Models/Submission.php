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
        return wpPayFormDB()->table('wpf_submissions')
            ->insert($submission);
    }

    public function getSubmissions($formId = false, $wheres = array(), $perPage, $skip)
    {
        $resultQuery = wpPayFormDB()->table('wpf_submissions')
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

        $result = wpPayFormDB()->table('wpf_submissions')
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
        $query = wpPayFormDB()->table('wpf_submissions');
        if ($formId) {
            $query = $query->where('form_id', $formId);
        }
        return $query->count();
    }

    public function update($submissionId, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return wpPayFormDB()->table('wpf_submissions')->where('id', $submissionId)->update($data);
    }

    public function getParsedSubmission($submission)
    {
        $elements = get_post_meta($submission->form_id, 'wppayform_paymentform_builder_settings', true);
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
        wpPayFormDB()->table('wpf_submissions')
            ->where('id', $sumissionId)
            ->delete();

        wpPayFormDB()->table('wpf_order_items')
            ->where('submission_id', $sumissionId)
            ->delete();

        wpPayFormDB()->table('wpf_order_transactions')
            ->where('submission_id', $sumissionId)
            ->delete();

        wpPayFormDB()->table('wpf_submission_activities')
            ->where('submission_id', $sumissionId)
            ->delete();
    }

    public function getEntryCountByPaymentStatus($formId, $paymentStatuses = array(), $period = 'total')
    {
        $query = wpPayFormDB()->table('wpf_submissions')
                    ->where('form_id', $formId);
        if($paymentStatuses && count($paymentStatuses)) {
            $query->whereIn('payment_status', $paymentStatuses);
        }

        if($period && $period != 'total') {
            $col               = 'created_at';
            if ( $period == 'day' ) {
                $year  = "YEAR(`{$col}`) = YEAR(NOW())";
                $month = "MONTH(`{$col}`) = MONTH(NOW())";
                $day   = "DAY(`{$col}`) = DAY(NOW())";
                $query->where( wpPayFormDB()->raw( "{$year} AND {$month} AND {$day}" ) );
            } elseif ( $period == 'week' ) {
                $query->where(
                    wpFluent()->raw( "YEARWEEK(`{$col}`, 1) = YEARWEEK(CURDATE(), 1)" )
                );
            } elseif ( $period == 'month' ) {
                $year  = "YEAR(`{$col}`) = YEAR(NOW())";
                $month = "MONTH(`{$col}`) = MONTH(NOW())";
                $query->where( wpPayFormDB()->raw( "{$year} AND {$month}" ) );
            } elseif ( $period == 'year' ) {
                $query->where( wpPayFormDB()->raw( "YEAR(`{$col}`) = YEAR(NOW())" ) );
            }
        }

        return $query->count();
    }
}