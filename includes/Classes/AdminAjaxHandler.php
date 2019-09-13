<?php

namespace WPPayForm\Classes;

use WPPayForm\Classes\Models\Forms;
use WPPayForm\Classes\Tools\GlobalTools;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax Handler Class
 * @since 1.0.0
 */
class AdminAjaxHandler
{
    public function registerEndpoints()
    {
        add_action('wp_ajax_wppayform_forms_admin_ajax', array($this, 'handeEndPoint'));
    }

    public function handeEndPoint()
    {
        $route = sanitize_text_field($_REQUEST['route']);

        $validRoutes = array(
            'get_forms'                  => 'getForms',
            'create_form'                => 'createForm',
            'update_form'                => 'updateForm',
            'get_form'                   => 'getForm',
            'save_form_settings'         => 'saveFormSettings',
            'save_form_builder_settings' => 'saveFormBuilderSettings',
            'get_custom_form_settings'   => 'getFormBuilderSettings',
            'delete_form'                => 'deleteForm',
            'get_form_settings'          => 'getFormSettings',
            'get_design_settings'        => 'getDesignSettings',
            'update_design_settings'     => 'updateDesignSettings',
            'duplicate_form'             => 'duplicateForm'
        );

        if (WPPAYFORM_DB_VERSION > intval(get_option('WPF_DB_VERSION'))) {
            $activator = new Activator();
            $activator->maybeUpgradeDB();
        }

        if (isset($validRoutes[$route])) {
            AccessControl::checkAndPresponseError($route, 'forms');
            do_action('wppayform/doing_ajax_forms_' . $route);
            return $this->{$validRoutes[$route]}();
        }
        do_action('wppayform/admin_ajax_handler_catch', $route);
    }

    protected function getForms()
    {
        $perPage = absint($_REQUEST['per_page']);
        $pageNumber = absint($_REQUEST['page_number']);
        $searchString = sanitize_text_field($_REQUEST['search_string']);
        $args = array(
            'posts_per_page' => $perPage,
            'offset'         => $perPage * ($pageNumber - 1)
        );

        $args = apply_filters('wppayform/get_all_forms_args', $args);

        if ($searchString) {
            $args['s'] = $searchString;
        }
        $forms = Forms::getForms($args, $with = array('entries_count'));

        wp_send_json_success($forms);
    }

    protected function createForm()
    {
        $postTitle = sanitize_text_field($_REQUEST['post_title']);
        if (!$postTitle) {
            $postTitle = 'Blank Form';
            return;
        }
        $template = sanitize_text_field($_REQUEST['template']);

        $data = array(
            'post_title'  => $postTitle,
            'post_status' => 'publish'
        );

        do_action('wppayform/before_create_form', $data, $template);

        $formId = Forms::create($data);

        wp_update_post([
            'ID'         => $formId,
            'post_title' => $data['post_title'] . ' (#' . $formId . ')'
        ]);

        if (is_wp_error($formId)) {
            wp_send_json_error(array(
                'message' => __('Something is wrong when createding the form. Please try again', 'wppayform')
            ), 423);
            return;
        }

        do_action('wppayform/after_create_form', $formId, $data, $template);

        wp_send_json_success(array(
            'message' => __('Form successfully created.', 'wppayform'),
            'form_id' => $formId
        ), 200);
    }

    protected function updateForm()
    {
        // validate first
        $formId = intval($_REQUEST['form_id']);
        $title = sanitize_text_field($_REQUEST['post_title']);
        if (!$formId || !$title) {
            wp_send_json_error(array(
                'message' => __('Please provide form title', 'wppayform')
            ), 423);
        }

        $formData = array(
            'post_title'   => $title,
            'post_content' => wp_kses_post($_REQUEST['post_content'])
        );

        do_action('wppayform/before_update_form', $formId, $formData);
        Forms::update($formId, $formData);
        do_action('wppayform/after_update_form', $formId, $formData);

        update_post_meta($formId, 'wppayform_show_title_description', sanitize_text_field($_REQUEST['show_title_description']));
        wp_send_json_success(array(
            'message' => __('Form successfully updated', 'wppayform')
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
        $allPages = wpPayFormDB()->table('posts')
            ->select(array('ID', 'post_title'))
            ->where('post_type', 'page')
            ->where('post_status', 'publish')
            ->get();

        $formId = absint($_REQUEST['form_id']);

        wp_send_json_success(array(
            'confirmation_settings' => Forms::getConfirmationSettings($formId),
            'receipt_settings'      => Forms::getReceiptSettings($formId),
            'currency_settings'     => Forms::getCurrencySettings($formId),
            'editor_shortcodes'     => FormPlaceholders::getAllPlaceholders($formId),
            'currencies'            => GeneralSettings::getCurrencies(),
            'locales'               => GeneralSettings::getLocales(),
            'pages'                 => $allPages,
            'recaptcha_settings'    => GeneralSettings::getRecaptchaSettings(),
            'form_recaptcha_status' => get_post_meta($formId, '_recaptcha_status', true)
        ), 200);
    }

    protected function saveFormSettings()
    {
        $formId = absint($_REQUEST['form_id']);
        if (isset($_REQUEST['confirmation_settings'])) {
            $confirmationSettings = wp_unslash($_REQUEST['confirmation_settings']);
            update_post_meta($formId, 'wppapyform_paymentform_confirmation_settings', $confirmationSettings);
        }
        if (isset($_REQUEST['currency_settings'])) {
            $currency_settings = wp_unslash($_REQUEST['currency_settings']);
            update_post_meta($formId, 'wppayform_paymentform_currency_settings', $currency_settings);
        }

        if (isset($_REQUEST['form_recaptcha_status'])) {
            update_post_meta($formId, '_recaptcha_status', sanitize_text_field($_REQUEST['form_recaptcha_status']));
        }

        if (isset($_REQUEST['receipt_settings'])) {
            $confirmationSettings = wp_unslash($_REQUEST['receipt_settings']);
            update_post_meta($formId, 'wppapyform_receipt_settings', $confirmationSettings);
        }

        wp_send_json_success(array(
            'message' => __('Settings successfully updated', 'wppayform')
        ), 200);
    }

    protected function saveFormBuilderSettings()
    {
        $data = file_get_contents("php://input");
        if ($data) {
            parse_str($data, $form_data);
            $builderSettings = json_decode($form_data['builder_settings'], true);
        } else {
            // failsafe
            $builderSettings = wp_unslash($_REQUEST['builder_settings']);
            $builderSettings = json_decode($builderSettings, true);
        }

        $formId = absint($_REQUEST['form_id']);

        if (!$formId || !$builderSettings) {
            wp_send_json_error(array(
                'message' => __('Validation Error, Please try again', 'wppayform'),
                'errors'  => array(
                    'general' => __('Please add atleast one input element', 'wppayform')
                )
            ), 423);
        }
        $errors = array();

        $hasRecurringField = 'no';

        foreach ($builderSettings as $builderSetting) {
            $error = apply_filters('wppayform/validate_component_on_save_' . $builderSetting['type'], false, $builderSetting, $formId);
            if ($error) {
                $errors[$builderSetting['id']] = $error;
            }

            if ($builderSetting['type'] == 'recurring_payment_item') {
                $hasRecurringField = 'yes';
            }
        }

        if ($errors) {
            wp_send_json_error(array(
                'message' => __('Validation failed when saving the form', 'wppayform'),
                'errors'  => $errors
            ), 423);
        }

        $submit_button_settings = wp_unslash($_REQUEST['submit_button_settings']);
        update_post_meta($formId, 'wppayform_paymentform_builder_settings', $builderSettings);
        update_post_meta($formId, 'wppayform_submit_button_settings', $submit_button_settings);

        update_post_meta($formId, 'wpf_has_recurring_field', $hasRecurringField);

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
        do_action('wppayform/before_form_delete', $formId);
        Forms::deleteForm($formId);
        do_action('wppayform/after_form_delete', $formId);
        wp_send_json_success(array(
            'message' => __('Selected form successfully deleted', 'wppayform')
        ), 200);
    }

    protected function getDesignSettings()
    {
        $formId = intval($_REQUEST['form_id']);
        wp_send_json_success(array(
            'layout_settings' => Forms::getDesignSettings($formId)
        ), 200);
    }

    protected function updateDesignSettings()
    {
        $formId = intval($_REQUEST['form_id']);
        $layoutSettings = wp_unslash($_REQUEST['layout_settings']);
        update_post_meta($formId, 'wppayform_form_design_settings', $layoutSettings);
        wp_send_json_success(array(
            'message' => __('Settings successfully updated', 'wppayform')
        ), 200);
    }

    protected function duplicateForm()
    {
        $formId = absint($_POST['form_id']);
        $globalTools = new GlobalTools();
        $oldForm = $globalTools->getForm($formId);
        $oldForm['post_title'] = '(Duplicate) ' . $oldForm['post_title'];
        $oldForm = apply_filters('wppayform/form_duplicate', $oldForm);

        if (!$oldForm) {
            wp_send_json_error(array(
                'message' => __('No form found when duplicating the form', 'wppayform')
            ), 423);
        }
        $newForm = $globalTools->createFormFromData($oldForm);
        wp_send_json_success(array(
            'message' => __('Form successfully duplicated', 'wppayform'),
            'form'    => $newForm
        ), 200);
    }
}