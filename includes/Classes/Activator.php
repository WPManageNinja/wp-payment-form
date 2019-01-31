<?php

namespace WPPayForm\Classes;

use WPPayForm\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax Handler Class
 * @since 1.0.0
 */
class Activator
{
    public function migrateDatabases($network_wide = false)
    {
        global $wpdb;
        if ($network_wide) {
            // Retrieve all site IDs from this network (WordPress >= 4.6 provides easy to use functions for that).
            if (function_exists('get_sites') && function_exists('get_current_network_id')) {
                $site_ids = get_sites(array('fields' => 'ids', 'network_id' => get_current_network_id()));
            } else {
                $site_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs WHERE site_id = $wpdb->siteid;");
            }
            // Install the plugin for all these sites.
            foreach ($site_ids as $site_id) {
                switch_to_blog($site_id);
                $this->migrate();
                restore_current_blog();
            }
        } else {
            $this->migrate();
        }

    }

    private function migrate()
    {
        $this->createSubmissionsTable();
        $this->createOrderItemsTable();
        $this->createTransactionsTable();
    }

    public function createSubmissionsTable()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'wpf_submissions';
        $sql = "CREATE TABLE $table_name (
				id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				form_id int(11) NOT NULL,
				user_id int(11) DEFAULT NULL,
				customer_id varchar(255),
				customer_name varchar(255),
				customer_email varchar(255),
				form_data_raw longtext,
				form_data_formatted longtext,
				currency varchar(255),
				payment_status varchar(255),
				submission_hash varchar (255),
				payment_total int(11),
				payment_mode varchar(255),
				payment_method varchar(255),
				status varchar(255),
				created_at timestamp NULL,
				updated_at timestamp NULL
			) $charset_collate;";

        $this->runSQL($sql, $table_name);
    }

    public function createOrderItemsTable()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'wpf_order_items';
        $sql = "CREATE TABLE $table_name (
				id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				form_id int(11) NOT NULL,
				submission_id int(11) NOT NULL,
				type varchar(255) DEFAULT 'single',
				billing_interval varchar(255),
				item_name varchar(255),
				quantity int(11) DEFAULT 1,
				item_price int(11),
				line_total int(11),
				created_at timestamp NULL,
				updated_at timestamp NULL
			) $charset_collate;";
        $this->runSQL($sql, $table_name);
    }

    public function createTransactionsTable()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'wpf_order_transactions';

        $sql = "CREATE TABLE $table_name (
				id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				form_id int(11) NOT NULL,
				user_id int(11) DEFAULT NULL,
				submission_id int(11) NOT NULL,
				transaction_type varchar(255) DEFAULT 'one_time',
				payment_method varchar(255) DEFAULT 'stripe',
				card_last_4 int(4),
				card_brand varchar(255),
				charge_id varchar(255),
				payment_total int(11) DEFAULT 1,
				status varchar(255),
				currency varchar(255),
				payment_mode varchar(255),
				payment_note longtext,
				created_at timestamp NULL,
				updated_at timestamp NULL
			) $charset_collate;";
        $this->runSQL($sql, $table_name);
    }

    private function runSQL($sql, $tableName)
    {
        global $wpdb;
        if ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") != $tableName) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
}