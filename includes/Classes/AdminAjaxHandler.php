<?php

namespace WPPayForm\Classes;

use SimplePay\Core\Abstracts\Form;
use WPPayForm\Classes\Models\Forms;
use WPPayForm\Classes\Models\Submission;
use WPPayForm\Classes\Models\SubmissionActivity;

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
        add_action('wp_ajax_wpf_upload_image', array($this, 'handleFileUpload'));
    }

    public function handeEndPoint()
    {
        $route = sanitize_text_field($_REQUEST['route']);
        $validRoutes = array(
            'get_forms'                       => 'getForms',
            'create_form'                     => 'createForm',
            'update_form'                     => 'updateForm',
            'get_form'                        => 'getForm',
            'save_form_settings'              => 'saveFormSettings',
            'save_form_builder_settings'      => 'saveFormBuilderSettings',
            'get_custom_form_settings'        => 'getFormBuilderSettings',
            'delete_form'                     => 'deleteForm',
            'get_form_settings'               => 'getFormSettings',
            'add_submission_note'             => 'addSubmissionNote',
            'delete_submission'               => 'deleteSubmission',
            'change_payment_status'           => 'changePaymentStatus',
            'get_global_currency_settings'    => 'getGlobalCurrencySettings',
            'update_global_currency_settings' => 'updateGlobalCurrencySettings',
            'get_design_settings'             => 'getDesignSettings',
            'update_design_settings'          => 'updateDesignSettings'
        );

        if (isset($validRoutes[$route])) {
            return $this->{$validRoutes[$route]}();
        }
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
        if($searchString) {
            $args['s'] = $searchString;
        }
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
        Forms::update($formId, $formData);
        update_post_meta($formId, '_show_title_description', sanitize_text_field($_REQUEST['show_title_description']));
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
        $formId = absint($_REQUEST['form_id']);
        wp_send_json_success(array(
            'confirmation_settings' => Forms::getConfirmationSettings($formId),
            'currency_settings'     => Forms::getCurrencySettings($formId),
            'editor_shortcodes'     => Forms::getEditorShortCodes($formId),
            'currencies'            => GeneralSettings::getCurrencies(),
            'locales'               => GeneralSettings::getLocales()
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
        if (!$formId || !$builderSettings) {
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

    protected function addSubmissionNote()
    {
        $formId = intval($_REQUEST['form_id']);
        $submissionId = intval($_REQUEST['submission_id']);
        $content = esc_html($_REQUEST['note']);
        $userId = get_current_user_id();
        $user = get_user_by('ID', $userId);

        SubmissionActivity::createActivity(array(
            'form_id'            => $formId,
            'submission_id'      => $submissionId,
            'type'               => 'custom_note',
            'content'            => $content,
            'created_by'         => $user->display_name,
            'created_by_user_id' => $userId
        ));

        wp_send_json_success(array(
            'message'    => __('Note successfully added', 'wppayform'),
            'activities' => SubmissionActivity::getSubmissionActivity($submissionId)
        ), 200);
    }

    protected function changePaymentStatus()
    {
        $submissionId = intval($_REQUEST['submission_id']);
        $newStatus = sanitize_text_field($_REQUEST['new_payment_status']);
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId);
        if ($submission->payment_status == $newStatus) {
            wp_send_json_error(array(
                'message' => __('The submission have the same status', 'wppayform')
            ), 423);
        }

        do_action('wpf_before_payment_status_change', $submission, $newStatus);
        $submissionModel->update($submissionId, array(
            'payment_status' => $newStatus
        ));
        do_action('wpf_after_payment_status_change', $submissionId, $newStatus);

        $activityContent = 'Payment status changed from <b>' . $submission->payment_status . '</b> to <b>' . $newStatus . '</b>';

        if (isset($_REQUEST['status_change_note']) && $_REQUEST['status_change_note']) {
            $note = wp_kses_post($_REQUEST['status_change_note']);
            $activityContent .= '<br />Note: ' . $note;
        }
        $userId = get_current_user_id();
        $user = get_user_by('ID', $userId);
        SubmissionActivity::createActivity(array(
            'form_id'            => $submission->form_id,
            'submission_id'      => $submission->id,
            'type'               => 'info',
            'created_by'         => $user->display_name,
            'created_by_user_id' => $userId,
            'content'            => $activityContent
        ));

        wp_send_json_success(array(
            'message' => __('Payment status successfully changed', 'wppayform')
        ), 200);
    }

    protected function deleteSubmission()
    {
        $submissionId = intval($_REQUEST['submission_id']);
        $formId = intval($_REQUEST['form_id']);
        do_action('wpf_before_delete_submission', $submissionId, $formId);
        $submissionModel = new Submission();
        $submissionModel->deleteSubmission($submissionId);
        do_action('wpf_after_delete_submission', $submissionId, $formId);

        wp_send_json_success(array(
            'message' => __('Selected submission successfully deleted', 'wppayform')
        ), 200);
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

    public function handleFileUpload()
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

    public function getDesignSettings()
    {
        $formId = intval($_REQUEST['form_id']);
        wp_send_json_success(array(
            'layout_settings' => Forms::getDesignSettings($formId)
        ), 200);
    }

    public function updateDesignSettings()
    {
        $formId = intval($_REQUEST['form_id']);
        $layoutSettings = wp_unslash($_REQUEST['layout_settings']);
        update_post_meta($formId, '_form_design_settings', $layoutSettings);
        wp_send_json_success(array(
            'message' => __('Settings successfully updated', 'wppayform')
        ), 200);
    }
}