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
    private $model;
    private $db;

    public function __construct()
    {
        global $wpdb;
        $this->model = $wpdb->prefix . 'wpf_submissions';
        $this->db = $wpdb;
    }

    public function create($submission)
    {
        $result = $this->db->insert($this->model, $submission);
        if ($result) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function getSubmissions($formId = false, $wheres = array(), $perPage, $skip)
    {
        $resultQuery = wpPayformDB()->table('wpf_submissions')
                    ->select(array('wpf_submissions.*', 'posts.post_title'))
                    ->join('posts', 'posts.ID', '=', 'wpf_submissions.form_id')
                    ->orderBy('wpf_submissions.id', 'DESC')
                    ->limit($perPage)
                    ->offset($skip);

        if($formId) {
            $resultQuery->where('wpf_submissions.form_id', $formId);
        }

        foreach ($wheres as $whereKey => $where) {
            $resultQuery->where('wpf_submissions.'.$whereKey, $where);
        }

        $totalItems = $resultQuery->count();

        $results = $resultQuery->get();

        $formattedResults = array();
        foreach ($results as $result) {
            $result->form_data_raw = maybe_unserialize($result->form_data_raw);
            $result->form_data_formatted = maybe_unserialize($result->form_data_formatted);
            $formattedResults[] = $result;
        }
        return (object) array(
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
        if($result->user_id) {
            $result->user_profile_url = get_edit_user_link($result->user_id);
        }



        if(in_array('transactions',$with)) {
            $result->transactions = (new Transaction())->getTransactions($submissionId);
        }

        if(in_array('order_items',$with)) {
            $result->order_items = (new OrderItem())->getOrderItems($submissionId);
        }

        return $result;
    }

    public function getTotalCount($formId = false)
    {
        if($formId) {
            return $this->db->get_var( "SELECT COUNT(*) FROM {$this->model} WHERE form_id = {$formId}" );
        }

        return $this->db->get_var( "SELECT COUNT(*) FROM {$this->model}" );

    }

    public function update($submissionId, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update($this->model, $data, array('id' => $submissionId));
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
            if($element['group'] == 'input') {
                $elementId = ArrayHelper::get($element, 'id');
                $elementValue = apply_filters('wpf_rendering_value_'.$element['type'], ArrayHelper::get($inputValues, $elementId));
                if(is_array($elementValue)) {
                    $elementValue = implode(', ', $elementValue);
                }
                $parsedSubmission[$elementId] = array(
                    'label' => $this->getLabel($element),
                    'value' => $elementValue,
                    'type' => $element['type']
                );
            }
        }

        return apply_filters('wpf_parse_submission', $parsedSubmission, $submission);
    }

    private function getLabel($element)
    {
        $elementId = ArrayHelper::get($element, 'id');
        if(!$label = ArrayHelper::get($element, 'field_options.admin_label')) {
            $label = ArrayHelper::get($element, 'field_options.label');
        }
        if(!$label) {
            $label = $elementId;
        }
        return $label;
    }
}