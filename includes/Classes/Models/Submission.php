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
class Submission extends Model
{

    public $metaGroup = 'wpf_submissions';

    public function create($submission)
    {
        return wpPayFormDB()->table('wpf_submissions')
            ->insert($submission);
    }

    public function getSubmissions($formId = false, $wheres = array(), $perPage = false, $skip = false, $orderBy = 'DESC', $searchString = false)
    {
        $resultQuery = wpPayFormDB()->table('wpf_submissions')
            ->select(array('wpf_submissions.*', 'posts.post_title'))
            ->join('posts', 'posts.ID', '=', 'wpf_submissions.form_id')
            ->orderBy('wpf_submissions.id', $orderBy);

        if ($perPage) {
            $resultQuery->limit($perPage);
        }
        if ($skip) {
            $resultQuery->offset($skip);
        }

        if ($formId) {
            $resultQuery->where('wpf_submissions.form_id', $formId);
        }

        foreach ($wheres as $whereKey => $where) {
            $resultQuery->where('wpf_submissions.' . $whereKey, $where);
        }

        if ($searchString) {
            $resultQuery->where(function ($q) use ($searchString) {
                $q->where('wpf_submissions.customer_name', 'LIKE', "%{$searchString}%")
                    ->orWhere('wpf_submissions.customer_email', 'LIKE', "%{$searchString}%")
                    ->orWhere('wpf_submissions.payment_method', 'LIKE', "%{$searchString}%")
                    ->orWhere('wpf_submissions.payment_total', 'LIKE', "%{$searchString}%")
                    ->orWhere('wpf_submissions.form_data_formatted', 'LIKE', "%{$searchString}%")
                    ->orWhere('wpf_submissions.created_at', 'LIKE', "%{$searchString}%");
            });
        }


        $totalItems = $resultQuery->count();

        $results = $resultQuery->get();

        $formattedResults = array();

        foreach ($results as $result) {
            $result->form_data_raw = maybe_unserialize($result->form_data_raw);
            $result->form_data_formatted = maybe_unserialize($result->form_data_formatted);
            $result->payment_total += $this->getSubscriptionPaymentTotal($result->form_id, $result->id);
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
            $result->order_items = (new OrderItem())->getSingleOrderItems($submissionId);
        }

        if (in_array('tax_items', $with)) {
            $result->tax_items = (new OrderItem())->getTaxOrderItems($submissionId);
        }

        if (in_array('activities', $with)) {
            $result->activities = SubmissionActivity::getSubmissionActivity($submissionId);
        }

        if (in_array('subscriptions', $with)) {
            $subscriptionModel = new Subscription();
            $result->subscriptions = $subscriptionModel->getSubscriptions($result->id);
        }
        if(in_array('refunds', $with)) {
            $refundModel = new Refund();
            $result->refunds = $refundModel->getRefunds($result->id);
            $refundTotal = 0;
            if($result->refunds) {
                foreach ($result->refunds as $refund) {
                    $refundTotal += $refund->payment_total;
                }
            }
            $result->refundTotal = $refundTotal;
        }

        return $result;
    }

    public function getSubmissionByHash($submissionHash, $with = array())
    {
        $submission = wpPayFormDB()->table('wpf_submissions')
                        ->where('submission_hash', $submissionHash)
                        ->orderBy('id', 'DESC')
                        ->first();

        if($submission) {
            return $this->getSubmission($submission->id, $with);
        }
        return false;
    }

    public function getTotalCount($formId = false, $paymentStatus = false)
    {
        $query = wpPayFormDB()->table('wpf_submissions');
        if ($formId) {
            $query = $query->where('form_id', $formId);
        }
        if ($paymentStatus) {
            $query = $query->where('payment_status', $paymentStatus);
        }
        return $query->count();
    }

    public function paymentTotal($formId, $paymentStatus = false)
    {
        $paymentTotal = 0;
        $query = wpPayFormDB()->table('wpf_submissions')
            ->select(wpPayFormDB()->raw('SUM(payment_total) as payment_total'));
        if ($formId) {
            $query = $query->where('form_id', $formId);
        }
        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }
        $result = $query->first();
        if ($result && $result->payment_total) {
            $paymentTotal = $result->payment_total;
        }

        if (!$paymentStatus || $paymentStatus == 'paid') {
            $paymentTotal += $this->getSubscriptionPaymentTotal($formId);
        }

        return $paymentTotal;
    }

    public function getSubscriptionPaymentTotal($formId, $submissionId = false)
    {
        $paymentTotal = 0;
        // Calculate from subscriptions
        $query = wpPayFormDB()->table('wpf_subscriptions')
            ->select(wpPayFormDB()->raw('SUM(payment_total) as payment_total'));
        if ($formId) {
            $query = $query->where('form_id', $formId);
        }

        if ($submissionId) {
            $query = $query->where('submission_id', $submissionId);
        }

        $result = $query->first();
        if ($result && $result->payment_total) {
            $paymentTotal = $result->payment_total;
        }
        return $paymentTotal;
    }

    public function update($submissionId, $data)
    {
        $data['updated_at'] = gmdate('Y-m-d H:i:s');
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
                $elementValue = apply_filters(
                    'wppayform/rendering_entry_value_' . $element['type'],
                    ArrayHelper::get($inputValues, $elementId),
                    $submission,
                    $element
                );

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

    public function getUnParsedSubmission($submission)
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
                $elementValue = ArrayHelper::get($inputValues, $elementId);

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

        return apply_filters('wppayform/unparsed_entry', $parsedSubmission, $submission);
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
            ->where('transaction_type', 'one_time')
            ->delete();

        wpPayFormDB()->table('wpf_submission_activities')
            ->where('submission_id', $sumissionId)
            ->delete();
    }

    public function getEntryCountByPaymentStatus($formId, $paymentStatuses = array(), $period = 'total')
    {
        $query = wpPayFormDB()->table('wpf_submissions')
            ->where('form_id', $formId);
        if ($paymentStatuses && count($paymentStatuses)) {
            $query->whereIn('payment_status', $paymentStatuses);
        }

        if ($period && $period != 'total') {
            $col = 'created_at';
            if ($period == 'day') {
                $year = "YEAR(`{$col}`) = YEAR(NOW())";
                $month = "MONTH(`{$col}`) = MONTH(NOW())";
                $day = "DAY(`{$col}`) = DAY(NOW())";
                $query->where(wpPayFormDB()->raw("{$year} AND {$month} AND {$day}"));
            } elseif ($period == 'week') {
                $query->where(
                    wpFluent()->raw("YEARWEEK(`{$col}`, 1) = YEARWEEK(CURDATE(), 1)")
                );
            } elseif ($period == 'month') {
                $year = "YEAR(`{$col}`) = YEAR(NOW())";
                $month = "MONTH(`{$col}`) = MONTH(NOW())";
                $query->where(wpPayFormDB()->raw("{$year} AND {$month}"));
            } elseif ($period == 'year') {
                $query->where(wpPayFormDB()->raw("YEAR(`{$col}`) = YEAR(NOW())"));
            }
        }

        return $query->count();
    }

}