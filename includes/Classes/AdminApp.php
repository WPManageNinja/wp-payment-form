<?php

namespace WPPayForm\Classes;

use WPPayForm\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin App Renderer and Handler
 * @since 1.0.0
 */
class AdminApp
{
    public function bootView()
    {
        $this->enqueueAssets();
        echo "<div id='wppayformsapp'></div>";
    }

    public function enqueueAssets()
    {
        if (function_exists('wp_enqueue_editor')) {
            wp_enqueue_editor();
        }
        wp_enqueue_script('wppayform_boot', WPPAYFORM_URL.'assets/js/payforms-boot.js', array('jquery'), WPPAYFORM_VERSION, true);
        wp_enqueue_script('wppayform_admin_app', WPPAYFORM_URL.'assets/js/payforms-admin.js', array('wppayform_boot'), WPPAYFORM_VERSION, true);
        wp_enqueue_style('wppayform_admin_app', WPPAYFORM_URL.'assets/css/payforms-admin.css', array(), WPPAYFORM_VERSION);
        wp_localize_script('wppayform_boot', 'wpPayFormsAdmin', array(
            'i18n' => array(
                'All Payment Forms' => __('All Payment Forms', 'wppayform')
            ),
            'paymentStatuses' => apply_filters('wpf_available_payment_statuses', array(
                'pending' => 'Pending',
                'paid' => 'Paid',
                'failed' => 'Failed',
                'refunded' => 'Refunded'
            )),
            'image_upload_url' => admin_url('admin-ajax.php?action=wpf_upload_image'),
            'forms_count' => Forms::getTotalCount(),
            'assets_url' => WPPAYFORM_URL.'assets/'
        ));

    }
}