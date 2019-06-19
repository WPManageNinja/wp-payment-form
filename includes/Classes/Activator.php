<?php

namespace WPPayForm\Classes;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax Handler Class
 * @since 1.0.0
 */
class Activator
{
    public $wpfDbVersion = WPPAYFORM_DB_VERSION;

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

    public function maybeUpgradeDB()
    {
        if(get_option('WPF_DB_VERSION') < $this->wpfDbVersion) {
            // We need to upgrade the database
            $this->forceUpgradeDB();
        }
    }

    public function forceUpgradeDB()
    {
        // We are upgrading the DB forcely
        $this->createTransactionsTable(true);
        $this->createMetaTable(true);
        $this->createSubscriptionTable(true);
        update_option('WPF_DB_VERSION', $this->wpfDbVersion, false);
    }

    public function migrate()
    {
        $this->createSubmissionsTable();
        $this->createOrderItemsTable();
        $isTransactionsTable = $this->createTransactionsTable(); // Altered in version 1.2.0
        $this->createSubmissionActivitiesTable();
        $isMetaTable = $this->createMetaTable(); // added in version 1.2.0
        $isSubscriptionsTable = $this->createSubscriptionTable(); // added in version 1.2.0
        $this->createPages();

        if(!$isTransactionsTable || !$isMetaTable || !$isSubscriptionsTable) {
            $this->maybeUpgradeDB();
        } else {
            // we are good. It's a new installation
            update_option('WPF_DB_VERSION', $this->wpfDbVersion, false);
        }
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
				ip_address varchar (45),
				browser varchar(45),
				device varchar(45),
				city varchar(45),
				country varchar(45),
				created_at timestamp NULL,
				updated_at timestamp NULL
			) $charset_collate;";

        return $this->runSQL($sql, $table_name);
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
				parent_holder varchar(255),
				billing_interval varchar(255),
				item_name varchar(255),
				quantity int(11) DEFAULT 1,
				item_price int(11),
				line_total int(11),
				created_at timestamp NULL,
				updated_at timestamp NULL
			) $charset_collate;";
        return $this->runSQL($sql, $table_name);
    }

    public function createTransactionsTable($forced = false)
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'wpf_order_transactions';

        $sql = "CREATE TABLE $table_name (
				id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				form_id int(11) NOT NULL,
				user_id int(11) DEFAULT NULL,
				submission_id int(11) NULL,
				subscription_id int(11) NULL,
				transaction_type varchar(255) DEFAULT 'one_time',
				payment_method varchar(255),
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
        if($forced) {
            return $this->runForceSQL($sql, $table_name);
        }
        return $this->runSQL($sql, $table_name);
    }

    public function createSubmissionActivitiesTable($forced = false)
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'wpf_submission_activities';

        $sql = "CREATE TABLE $table_name (
				id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				form_id int(11) NOT NULL,
				submission_id int(11) NOT NULL,
				type varchar(255),
				created_by varchar(255),
				created_by_user_id int(11),
				title varchar(255),
				content text,
				created_at timestamp NULL,
				updated_at timestamp NULL
			) $charset_collate;";

        if($forced) {
            return $this->runForceSQL($sql, $table_name);
        }
        return $this->runSQL($sql, $table_name);
    }

    public function createMetaTable($forced = false)
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'wpf_meta';

        $sql = "CREATE TABLE $table_name (
				id int(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				meta_group varchar(255),
				option_id int(11) NOT NULL,
				meta_key varchar(255),
				meta_value text,
				created_at timestamp NULL,
				updated_at timestamp NULL
			) $charset_collate;";
        if($forced) {
            return $this->runForceSQL($sql, $table_name);
        }
        return $this->runSQL($sql, $table_name);
    }

    public function createSubscriptionTable($forced = false)
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'wpf_subscriptions';

        $sql = "CREATE TABLE $table_name (
				id int(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				submission_id int(11),
				form_id int(11),
				payment_total int(11) DEFAULT 0,
				item_name varchar(255),
				plan_name varchar(255),
				parent_transaction_id int(11),
				billing_interval varchar (50),
				trial_days int(11),
				initial_amount int(11),
				quantity int(11) DEFAULT 1,
				recurring_amount int(11),
				bill_times int(11),
				bill_count int(11) DEFAULT 0,
				vendor_customer_id varchar(255),
				vendor_subscriptipn_id varchar(255),
				vendor_plan_id varchar(255),
				status varchar(255) DEFAULT 'pending',
				inital_tax_label varchar(255),
				inital_tax int(11),
				recurring_tax_label varchar(255),
				recurring_tax int(11),
				element_id varchar(255),
				note text,
				original_plan text,
				vendor_response longtext,
				expiration_at timestamp NULL,
				created_at timestamp NULL,
				updated_at timestamp NULL
			) $charset_collate;";
        if($forced) {
            return $this->runForceSQL($sql, $table_name);
        }
        return $this->runSQL($sql, $table_name);
    }

    private function runSQL($sql, $tableName)
    {
        global $wpdb;
        if ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") != $tableName) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            return true;
        }
        return false;
    }

    private function runForceSQL($sql, $tableName)
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        return true;
    }

    /**
     * Create the pages for success and failure redirects
     */
    public function createPages() {
        $options = get_option( 'wppayform_confirmation_pages' );
        if ( false === $options || ! array_key_exists( 'confirmation', $options ) ) {
            $charge_confirmation = wp_insert_post(array(
                'post_title'     => __('Payment Confirmation', 'wppayform'),
                'post_content'   => '[wppayform_reciept]',
                'post_status'    => 'publish',
                'post_author'    => 1,
                'post_type'      => 'page',
                'comment_status' => 'closed',
            ));
            $options['confirmation'] = $charge_confirmation;
        }
        if ( false === $options || ! array_key_exists( 'failed', $options ) ) {
            $charge_failed = wp_insert_post(array(
                'post_title'     => __('Payment Failed', 'wppayform'),
                /* translators: %s: The [simpay_errors] shortcode */
                'post_content'   => __("We're sorry, but your transaction failed to process. Please try again or contact site support.", 'wppayform'),
                'post_status'    => 'publish',
                'post_author'    => 1,
                'post_type'      => 'page',
                'comment_status' => 'closed',
            ));
            $options['failed'] = $charge_failed;
        }
        update_option( 'wppayform_confirmation_pages', $options );
    }
}