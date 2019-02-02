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
class AdminAjaxHandler
{
    public function __construct()
    {
        add_action('wp_ajax_wp_payment_forms_admin_ajax', array($this, 'handeEndPoint'));
    }

    public function handeEndPoint()
    {
        $route = sanitize_text_field($_REQUEST['route']);
        $validRoutes = array(
            'get_forms'                  => 'getForms',
            'create_form'                => 'createForm',
            'get_form'                   => 'getForm',
            'save_form_settings'         => 'saveFormSettings',
            'save_form_builder_settings' => 'saveFormBuilderSettings',
            'get_custom_form_settings'   => 'getFormBuilderSettings',
            'delete_form'                => 'deleteForm',
            'get_form_settings'         => 'getFormSettings'
        );

        if (isset($validRoutes[$route])) {
            return $this->{$validRoutes[$route]}();
        }
    }

    protected function getForms()
    {
        $perPage = absint($_REQUEST['per_page']);
        $pageNumber = absint($_REQUEST['page_number']);
        $args = array(
            'posts_per_page' => $perPage,
            'offset'         => $perPage * ($pageNumber - 1)
        );
        wp_send_json_success(Forms::getForms($args));
    }

    protected function createForm()
    {
        $postTitle = sanitize_text_field($_REQUEST['post_title']);
        if (!$postTitle) {
            wp_send_json_error(array(
                'message' => __('Please provide title of this form')
            ), 423);
            return;
        }

        $data = array(
            'post_title'  => $postTitle,
            'post_status' => 'publish'
        );

        $formId = Forms::create($data);
        if (is_wp_error($formId)) {
            wp_send_json_error(array(
                'message' => __('Something is wrong when createding the form. Please try again', 'wppayform')
            ), 423);
            return;
        }
        wp_send_json_success(array(
            'message' => __('Form successfully created', 'wppayform'),
            'form_id' => $formId
        ), 200);
    }

    protected function getForm()
    {
        $formId = absint($_REQUEST['form_id']);
        $form = Forms::getForm($formId);

        wp_send_json_success(array(
            'form' => $form
        ), 200);
    }

    protected function getFormSettings()
    {
        $formId = absint($_REQUEST['form_id']);
        wp_send_json_success(array(
            'confirmation_settings' => Forms::getConfirmationSettings($formId),
            'currency_settings' => Forms::getCurrencySettings($formId),
            'editor_shortcodes' => Forms::getEditorShortCodes($formId),
            'currencies'       => GeneralSettings::getCurrencies(),
            'locales'          => GeneralSettings::getLocales()
        ), 200);
    }

    protected function saveFormSettings()
    {
        $formId = absint($_REQUEST['form_id']);
        $confirmationSettings = wp_unslash($_REQUEST['confirmation_settings']);
        $currency_settings = wp_unslash($_REQUEST['currency_settings']);
        update_post_meta($formId, '_wp_paymentform_confirmation_settings', $confirmationSettings);
        update_post_meta($formId, '_wp_paymentform_currency_settings', $currency_settings);
        wp_send_json_success(array(
            'message' => __('Settings successfully updated', 'wppayform')
        ), 200);
    }

    protected function saveFormBuilderSettings()
    {
        $formId = absint($_REQUEST['form_id']);
        $builderSettings = wp_unslash($_REQUEST['builder_settings']);
        if (!$formId || !$builderSettings ) {
            wp_send_json_error(array(
                'message' => __('Validation Error, Please try again', 'wppayform')
            ), 423);
        }
        $submit_button_settings = wp_unslash($_REQUEST['submit_button_settings']);

        update_post_meta($formId, '_wp_paymentform_builder_settings', $builderSettings);
        update_post_meta($formId, '_wpf_submit_button_settings', $submit_button_settings);

        wp_send_json_success(array(
            'message' => __('Settings successfully updated', 'wppayform')
        ), 200);
    }

    protected function getFormBuilderSettings()
    {
        $formId = absint($_REQUEST['form_id']);
        $builderSettings = Forms::getBuilderSettings($formId);

        wp_send_json_success(array(
            'builder_settings'     => $builderSettings,
            'components'           => GeneralSettings::getComponents(),
            'form_button_settings' => Forms::getButtonSettings($formId)
        ), 200);
    }

    protected function deleteForm()
    {
        $formId = intval($_REQUEST['form_id']);
        do_action('wpf_before_form_delete', $formId);
        Forms::deleteForm($formId);

        wp_send_json_success(array(
            'message' => __('Selected form successfully deleted', 'wppayform')
        ), 200);
    }
}