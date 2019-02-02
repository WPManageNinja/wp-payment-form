<?php

namespace WPPayForm\Classes;

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
            )
        ));

    }
}