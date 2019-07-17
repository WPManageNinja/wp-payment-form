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
            'wpf_upload_image'                => 'handleFileUpload',
            'get_recaptcha_settings'          => 'getRecaprchaSettings',
            'save_recaptcha_settings'         => 'saveRecaptchaSettings'
        );
        $route = sanitize_text_field($_REQUEST['route']);
        if (isset($routes[$route])) {
            AccessControl::checkAndPresponseError($route, 'global');
            do_action('wppayform/doing_ajax_global_' . $route);
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
        update_option('wppayform_global_currency_settings', $data);
        update_option('wppayform_ip_logging_status', sanitize_text_field($_REQUEST['ip_logging_status']), false);

        // We will forecfully try to upgrade the DB and later we will remove this after 1-2 version
        $firstTransaction = wpFluent()->table('wpf_order_transactions')
            ->first();
        if (!$firstTransaction || !property_exists($firstTransaction, 'subscription_id')) {
            $activator = new Activator();
            $activator->forceUpgradeDB();
        }
        // end upgrade DB

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

    public function getRecaprchaSettings()
    {
        wp_send_json_success([
            'settings' => GeneralSettings::getRecaptchaSettings()
        ]);
    }

    public function saveRecaptchaSettings()
    {
        $settings = $_REQUEST['settings'];
        $sanitizedSettings = [];
        foreach ($settings as $settingKey => $setting) {
            $sanitizedSettings[$settingKey] = sanitize_text_field($setting);
        }

        if($sanitizedSettings['recaptcha_version'] != 'none') {
            if(empty($sanitizedSettings['site_key']) || empty($sanitizedSettings['secret_key'])) {
                wp_send_json_error([
                    'message' => 'Please provide site key and secret key for enable recaptcha'
                ], 423);
            }
        }

        update_option('wppayform_recaptcha_settings', $sanitizedSettings);

        wp_send_json_success([
            'message' => 'Settings successfully updated'
        ], 200);
    }
}