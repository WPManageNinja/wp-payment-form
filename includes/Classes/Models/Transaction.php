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
class Transaction
{
    private $model;
    private $db;

    public function __construct()
    {
        global $wpdb;
        $this->model = $wpdb->prefix . 'wpf_order_transactions';
        $this->db = $wpdb;
    }

    public function create($item)
    {
        $result = $this->db->insert($this->model, $item);
        if ($result) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function getTransactions($submissionId)
    {
        $transactions = wpPayformDB()->table('wpf_order_transactions')
                            ->where('submission_id', $submissionId)
                            ->get();

        return apply_filters('wpf_form_transactions', $transactions, $submissionId);
    }

    public function getTransaction($transactionId)
    {
        return wpPayformDB()->table('wpf_order_transactions')->where('id', $transactionId)->first();
    }

    public function update($transactionId, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update($this->model, $data, array('id' => $transactionId));
    }
}