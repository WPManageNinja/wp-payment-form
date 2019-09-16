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
        $menuPermission = AccessControl::hasTopLevelMenuPermission();
        if (!$menuPermission) {
            return;
        }

        $title = __('WPPayForms', 'wppayform');
        if(defined('WPPAYFORMHASPRO')) {
            $title .= ' Pro';
        }

        global $submenu;
        add_menu_page(
            $title,
            $title,
            $menuPermission,
            'wppayform.php',
            array($this, 'render'),
            $this->getIcon(),
            25
        );

        if(defined('WPPAYFORM_PRO_INSTALLED')) {
            $license = get_option('_wppayform_pro_license_status');
            if ($license != 'valid') {
                $submenu['wppayform.php']['activate_license'] = array(
                    '<span style="color:#f39c12;">Activate License</span>',
                    $menuPermission,
                    'admin.php?page=wppayform.php#/settings/licensing',
                    '',
                    'wppayform_license_menu'
                );
            }
        }

        $submenu['wppayform.php']['all_forms'] = array(
            __('All Forms', 'wppayform'),
            $menuPermission,
            'admin.php?page=wppayform.php#/',
        );
        $submenu['wppayform.php']['entries'] = array(
            __('Entries', 'wppayform'),
            $menuPermission,
            'admin.php?page=wppayform.php#/entries',
        );
        $submenu['wppayform.php']['settings'] = array(
            __('Settings', 'wppayform'),
            $menuPermission,
            'admin.php?page=wppayform.php#/settings/general-settings',
        );
        if(!defined('WPPAYFORM_PRO_INSTALLED')) {
            $submenu['wppayform.php']['upgrade_to_pro'] = array(
                '<span style="color: #f9e112;">Upgrade To Pro</span>',
                $menuPermission,
                'https://wpmanageninja.com/downloads/wppayform-pro-wordpress-payments-form-builder/?utm_source=plugin&utm_medium=menu&utm_campaign=upgrade',
            );
        }

        $submenu['wppayform.php']['support'] = array(
            __('Support', 'wppayform'),
            $menuPermission,
            'admin.php?page=wppayform.php#/support',
        );
    }

    public function render() {
        do_action('wppayform/render_admin_app');
    }

    public function enqueueAssets()
    {
        if(isset($_GET['page']) && $_GET['page'] == 'wppayform.php') {
            if (function_exists('wp_enqueue_editor')) {
                wp_enqueue_editor();
                wp_enqueue_script('thickbox');
            }
            if (function_exists('wp_enqueue_media')) {
                wp_enqueue_media();
            }

            wp_enqueue_script('wppayform_boot', WPPAYFORM_URL.'assets/js/payforms-boot.js', array('jquery'), WPPAYFORM_VERSION, true);
            // 3rd party developers can now add their scripts here
            do_action('wppayform/booting_admin_app');
            wp_enqueue_script('wppayform_admin_app', WPPAYFORM_URL.'assets/js/payforms-admin.js', array('wppayform_boot'), WPPAYFORM_VERSION, true);
            wp_enqueue_style('wppayform_admin_app', WPPAYFORM_URL.'assets/css/payforms-admin.css', array(), WPPAYFORM_VERSION);

            $payformAdminVars = apply_filters('wppayform/admin_app_vars',array(
                'i18n' => array(
                    'All Payment Forms' => __('All Payment Forms', 'wppayform')
                ),
                'paymentStatuses' => GeneralSettings::getPaymentStatuses(),
                'image_upload_url' => admin_url('admin-ajax.php?action=wpf_global_settings_handler&route=wpf_upload_image'),
                'forms_count' => Forms::getTotalCount(),
                'assets_url' => WPPAYFORM_URL.'assets/',
                'has_pro' => defined('WPPAYFORMHASPRO') && WPPAYFORMHASPRO,
                'hasValidLicense' => get_option('_wppayform_pro_license_status'),
                'ajaxurl' => admin_url('admin-ajax.php'),
                'ipn_url' => site_url().'?wpf_paypal_ipn=1',
                'printStyles' => apply_filters('wppayform/print_styles', []),
                'ace_path_url' => WPPAYFORM_URL.'assets/libs/ace'
            ));

            wp_localize_script('wppayform_boot', 'wpPayFormsAdmin', $payformAdminVars);
        }
    }

    public function getIcon()
    {
        $svg = '<?xml version="1.0" encoding="UTF-8"?><svg enable-background="new 0 0 512 512" version="1.1" viewBox="0 0 512 512" xml:space="preserve" xmlns="http://www.w3.org/2000/svg">
		<path d="m446 0h-380c-8.284 0-15 6.716-15 15v482c0 8.284 6.716 15 15 15h380c8.284 0 15-6.716 15-15v-482c0-8.284-6.716-15-15-15zm-15 482h-350v-452h350v452z" fill="#fff"/>
		<path d="m313 151h-2v-23c0-30.327-24.673-55-55-55s-55 24.673-55 55v23h-2c-8.284 0-15 6.716-15 15v78c0 8.284 6.716 15 15 15h114c8.284 0 15-6.716 15-15v-78c0-8.284-6.716-15-15-15zm-82-23c0-13.785 11.215-25 25-25s25 11.215 25 25v23h-50v-23zm67 101h-84v-48h84v48z" fill="#fff"/>
		<path d="m166.43 318h-22.857c-4.734 0-8.571 3.838-8.571 8.571v22.857c0 4.734 3.838 8.571 8.571 8.571h22.857c4.734 0 8.571-3.838 8.571-8.571v-22.857c0-4.733-3.838-8.571-8.571-8.571z" fill="#fff"/>
		<path d="m377 323h-142c-8.284 0-15 6.716-15 15s6.716 15 15 15h142c8.284 0 15-6.716 15-15s-6.716-15-15-15z" fill="#fff"/>
		<path d="m166.43 398h-22.857c-4.734 0-8.571 3.838-8.571 8.571v22.857c0 4.734 3.838 8.571 8.571 8.571h22.857c4.734 0 8.571-3.838 8.571-8.571v-22.857c0-4.733-3.838-8.571-8.571-8.571z" fill="#fff"/>
		<path d="m377 403h-142c-8.284 0-15 6.716-15 15s6.716 15 15 15h142c8.284 0 15-6.716 15-15s-6.716-15-15-15z" fill="#fff"/></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}