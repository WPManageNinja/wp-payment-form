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

    public function getSubmissions($formId, $perPage, $skip)
    {
        $results = $this->db->get_results( "SELECT * FROM {$this->model} WHERE form_id = {$formId} LIMIT {$perPage} OFFSET {$skip}", OBJECT );
        $formattedResults = array();
        foreach ($results as $result) {
            $result->form_data_raw = maybe_unserialize($result->form_data_raw);
            $result->form_data_formatted = maybe_unserialize($result->form_data_formatted);
            $formattedResults[] = $result;
        }
        return $formattedResults;
    }

    public function getSubmission($submissionId, $with = array())
    {
        $result = $this->db->get_row( "SELECT * FROM {$this->model} WHERE id = {$submissionId} LIMIT 1", OBJECT );
        $result->form_data_raw = maybe_unserialize($result->form_data_raw);
        $result->form_data_formatted = maybe_unserialize($result->form_data_formatted);

        if(in_array('transactions',$with)) {
            $result->transactions = (new Transaction())->getTransactions($submissionId);
        }

        if(in_array('order_items',$with)) {
            $result->order_items = (new OrderItem())->getOrderItems($submissionId);
        }

        return $result;
    }

    public function getTotalCount($formId)
    {
        return $this->db->get_var( "SELECT COUNT(*) FROM {$this->model} WHERE form_id = {$formId}" );
    }
}