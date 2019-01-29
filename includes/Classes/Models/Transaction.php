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
        $this->model = $wpdb->prefix.'wpf_order_transactions';
        $this->db = $wpdb;
    }

    public function create($item)
    {
        $result = $this->db->insert($this->model,$item);
        if($result) {
            return $this->db->insert_id;
        }
        return false;
    }
}