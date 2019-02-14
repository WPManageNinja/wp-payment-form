<?php

namespace WPPayForm\Classes;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Global Settings Handler
 * @since 1.0.0
 */
class GlobalSettingsHandler
{
    public function registerHooks()
    {
        add_action('wp_ajax_wpf_global_settings_handler', array($this, 'handleEndpoints'));
    }

    public function handleEndpoints()
    {
        $routes = array(
            'get_global_currency_settings'    => 'getGlobalCurrencySettings',
            'update_global_currency_settings' => 'updateGlobalCurrencySettings',
            'wpf_upload_image'                => 'handleFileUpload'
        );
        $route = sanitize_text_field($_REQUEST['route']);
        if (isset($routes[$route])) {
            AccessControl::checkAndPresponseError($route, 'global');
            do_action('wppayform/doing_ajax_global_'.$route);
            $this->{$routes[$route]}();
            return;
        }
    }


    protected function getGlobalCurrencySettings()
    {
        wp_send_json_success(array(
            'currency_settings' => GeneralSettings::getGlobalCurrencySettings(),
            'currencies'        => GeneralSettings::getCurrencies(),
            'locales'           => GeneralSettings::getLocales(),
            'ip_logging_status' => GeneralSettings::ipLoggingStatus()
        ), 200);
    }

    protected function updateGlobalCurrencySettings()
    {
        $settings = $_REQUEST['settings'];
        // Validate the data
        if (empty($settings['currency'])) {
            wp_send_json_error(array(
                'message' => __('Please select a currency', 'wppayform')
            ), 423);
        }

        $data = array(
            'currency'               => sanitize_text_field($settings['currency']),
            'locale'                 => sanitize_text_field($settings['locale']),
            'currency_sign_position' => sanitize_text_field($settings['currency_sign_position']),
            'currency_separator'     => sanitize_text_field($settings['currency_separator']),
            'decimal_points'         => intval($settings['decimal_points']),
        );
        update_option('_wppayform_global_currency_settings', $data);
        update_option('_wpf_ip_logging_status', sanitize_text_field($_REQUEST['ip_logging_status']), false);
        wp_send_json_success(array(
            'message' => __('Settings successfully updated', 'wppayform')
        ), 200);
    }

    protected function handleFileUpload()
    {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        $uploadedfile = $_FILES['file'];

        $acceptedFilles = array(
            'image/png',
            'image/jpeg'
        );

        if (!in_array($uploadedfile['type'], $acceptedFilles)) {
            wp_send_json(__('Please upload only jpg/png format files', 'wppayform'), 423);
        }

        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
        if ($movefile && !isset($movefile['error'])) {
            wp_send_json_success(array(
                'file' => $movefile
            ), 200);
        } else {
            wp_send_json(__('Something is wrong when uploading the file', 'wppayform'), 423);
        }
    }
}