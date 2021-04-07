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

        $queryType = ArrayHelper::get($wheres, 'payment_status');
        if (isset($wheres) && $queryType === 'abandoned') {
            $wheres['payment_status'] = 'pending';
            $resultQuery = $this->makeQueryAbandoned($resultQuery, '<', true);
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

        if (in_array('discount', $with)) {
            $discounts = (new OrderItem())->getDiscountItems($submissionId);

            $totalDiscount = 0;
            if (isset($discounts)) {
                foreach ($discounts as $discount) {
                    $totalDiscount += intval($discount->line_total);
                }
            }
            $result->discounts = array(
                'applied' => $discounts,
                'total'   => $totalDiscount
            );
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
        if (in_array('refunds', $with)) {
            $refundModel = new Refund();
            $result->refunds = $refundModel->getRefunds($result->id);
            $refundTotal = 0;
            if ($result->refunds) {
                foreach ($result->refunds as $refund) {
                    $refundTotal += $refund->payment_total;
                }
            }
            $result->refundTotal = $refundTotal;
        }

        $route =  ArrayHelper::get($_REQUEST, 'route', '');

        if ($result->status == 'new' && $route === 'get_submission') {
            wpPayFormDB()->table('wpf_submissions')
            ->where('form_id', $result->form_id)
            ->where('id', $result->id)
            ->update(['status'=> 'read']);
            $result->status = 'read';
        }
        return $result;
    }

    public function getSubmissionByHash($submissionHash, $with = array())
    {
        $submission = wpPayFormDB()->table('wpf_submissions')
                        ->where('submission_hash', $submissionHash)
                        ->orderBy('id', 'DESC')
                        ->first();

        if ($submission) {
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
        if ($paymentStatus && $paymentStatus !== 'abandoned') {
            $query = $query->where('payment_status', $paymentStatus);
        } else {
            $query = $query->where('payment_status', 'pending');
            $query = $this->makeQueryAbandoned($query, '<', true);
        }
        return $query->count();
    }

    public function makeQueryAbandoned($query, $condition = '<', $payOnly = true)
    {
        $hour = get_option('wppayform_abandoned_time', 3);

        $beforeHour = intval($hour) * 3600;
        $now = current_time('mysql');
        $formatted_date = date('Y-m-d H:i:s', strtotime($now) - $beforeHour);

        $query->where('wpf_submissions.created_at', $condition, $formatted_date);
        if ($payOnly) {
            $query->where('wpf_submissions.payment_method', '!=', '');
        }
        return $query;
    }


    public function paymentTotal($formId, $paymentStatus = false)
    {
        $paymentTotal = 0;
        $query = wpPayFormDB()->table('wpf_submissions')
            ->select(wpPayFormDB()->raw('SUM(payment_total) as payment_total'));
        if ($formId) {
            $query = $query->where('form_id', $formId);
        }
        if ($paymentStatus !== 'abandoned') {
            $query->where('payment_status', $paymentStatus);
        } else {
            $query->where('payment_status', 'pending');
            $query = $this->makeQueryAbandoned($query, '<', true);
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
            ->select(wpPayFormDB()->raw('SUM(payment_total - initial_amount) as payment_total'));
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
        $data['updated_at'] = current_time('mysql');
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
        foreach ($sumissionId as $value) {
            wpPayFormDB()->table('wpf_submissions')
                ->where('id', intval($value))
                ->delete();

            wpPayFormDB()->table('wpf_order_items')
                ->where('submission_id', intval($value))
                ->delete();

            wpPayFormDB()->table('wpf_order_transactions')
                ->where('submission_id', intval($value))
                ->where('transaction_type', 'one_time')
                ->delete();

            wpPayFormDB()->table('wpf_submission_activities')
                ->where('submission_id', intval($value))
                ->delete();
        }
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

    public function changeEntryStatus()
    {
        $formId = intval(ArrayHelper::get($_REQUEST, 'form_id'));
        $entryId = intval(ArrayHelper::get($_REQUEST, 'id'));
        $newStatus = sanitize_text_field(ArrayHelper::get($_REQUEST, 'status'));

        wpPayFormDB()->table('wpf_submissions')
            ->where('form_id', $formId)
            ->where('id', $entryId)
            ->update(['status'=> $newStatus]);
        return $newStatus;
    }
}
