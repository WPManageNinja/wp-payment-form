<?php

namespace WPPayForm\Classes;

use WPPayForm\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Menu and Admin Pages
 * @since 1.0.0
 */
class Menu
{
    public function register()
    {
        add_action( 'admin_menu', array($this, 'addMenus') );
        add_action('admin_enqueue_scripts', array($this, 'enqueueAssets'));
    }


    public function addMenus()
    {
        $capability = 'manage_options';
        if (!$capability) {
            return;
        }
        global $submenu;
        add_menu_page(
            __( 'Payment Forms', 'wppayform' ),
            __( 'Payment Forms', 'wppayform' ),
            $capability,
            'wppayform.php',
            array($this, 'render'),
            '',
            6
        );
        $submenu['wppayform.php']['all_forms'] = array(
            __('All Forms', 'wppayform'),
            $capability,
            'admin.php?page=wppayform.php#/',
        );
        $submenu['wppayform.php']['entries'] = array(
            __('Entries', 'wppayform'),
            $capability,
            'admin.php?page=wppayform.php#/entries',
        );
        $submenu['wppayform.php']['settings'] = array(
            __('Settings', 'wppayform'),
            $capability,
            'admin.php?page=wppayform.php#/settings/stripe-settings',
        );
        $submenu['wppayform.php']['support'] = array(
            __('Support', 'wppayform'),
            $capability,
            'admin.php?page=wppayform.php#/support',
        );
    }

    public function render() {
        do_action('wppayform_render_admin_app');
    }

    public function enqueueAssets()
    {
        if(isset($_GET['page']) && $_GET['page'] == 'wppayform.php') {
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
}