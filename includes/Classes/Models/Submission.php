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
        $this->model = $wpdb->prefix.'wpf_submissions';
        $this->db = $wpdb;
    }

    public function create($submission)
    {
        $result = $this->db->insert($this->model,$submission);
        if($result) {
            return $this->db->insert_id;
        }
        return false;
    }
}