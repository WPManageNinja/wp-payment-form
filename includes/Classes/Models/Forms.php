<?php

namespace WPPayForm\Classes\Models;

use WPPayForm\Classes\GeneralSettings;
use WPPayForm\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax Handler Class
 * @since 1.0.0
 */
class Forms
{
    public static function getForms($args = array())
    {
        $argsDefault = array(
            'posts_per_page' => 10,
            'offset'         => 0,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_type'      => 'wp_payform',
            'post_status'    => 'any',
        );
        $args = wp_parse_args($args, $argsDefault);

        $forms = get_posts($args);

        foreach ($forms as $form) {
            $form->preview_url = site_url('?wp_paymentform_preview=' . $form->ID);
        }

        $forms = apply_filters('wppayform/get_all_forms', $forms);

        $total = self::getTotalCount();
        $lastPage = ceil($total / $args['posts_per_page']);

        return array(
            'forms'     => $forms,
            'total'     => $total,
            'last_page' => $lastPage
        );
    }

    public static function getTotalCount()
    {
        return wpPayformDB()->table('posts')
            ->where('post_type', 'wp_payform')
            ->count();
    }

    public static function getAllAvailableForms()
    {
        return wpPayformDB()->table('posts')
            ->select(array('ID', 'post_title'))
            ->where('post_type', 'wp_payform')
            ->get();
    }

    public static function create($data)
    {
        $data['post_type'] = 'wp_payform';
        $data['post_status'] = 'publish';
        $id = wp_insert_post($data);
        return $id;
    }

    public static function update($formId, $data)
    {
        $data['ID'] = $formId;
        $data['post_type'] = 'wp_payform';
        $data['post_status'] = 'publish';
        return wp_update_post($data);
    }

    public static function getButtonSettings($formId)
    {
        $settings = get_post_meta($formId, '_wpf_submit_button_settings', true);
        if (!$settings) {
            $settings = array();
        }
        $buttonDefault = array(
            'button_text'     => __('Submit', 'wppayform'),
            'processing_text' => __('Please Wait…', 'wppayform'),
            'button_style'    => 'wpf_default_btn',
            'css_class'       => ''
        );

        return wp_parse_args($settings, $buttonDefault);
    }

    public static function getForm($formId)
    {
        $form = get_post($formId, 'OBJECT');
        if (!$form && $form->post_type != 'wp_payform') {
            return false;
        }
        $form->show_title_description = get_post_meta($formId, '_show_title_description', true);
        $form->preview_url = site_url('?wp_paymentform_preview=' . $form->ID);
        return $form;
    }

    public static function getFormInputLabels($formId)
    {
        $elements = get_post_meta($formId, '_wp_paymentform_builder_settings', true);
        if (!$elements) {
            return (object)array();
        }
        $formLabels = array();
        foreach ($elements as $element) {
            if ($element['group'] == 'input') {
                $elementId = ArrayHelper::get($element, 'id');
                if (!$label = ArrayHelper::get($element, 'field_options.admin_label')) {
                    $label = ArrayHelper::get($element, 'field_options.label');
                }
                if (!$label) {
                    $label = $elementId;
                }
                $formLabels[$elementId] = $label;
            }
        }
        return (object)$formLabels;
    }

    public static function getConfirmationSettings($formId)
    {
        $confirmationSettings = get_post_meta($formId, '_wp_paymentform_confirmation_settings', true);
        if (!$confirmationSettings) {
            $confirmationSettings = array();
        }
        $defaultSettings = array(
            'confirmation_type'    => 'custom',
            'redirectTo'           => 'samePage',
            'messageToShow'        => __('Form successfully submitted', 'wppayform'),
            'samePageFormBehavior' => 'hide_form',
        );
        return wp_parse_args($confirmationSettings, $defaultSettings);
    }

    public static function getCurrencySettings($formId)
    {
        $currencySettings = get_post_meta($formId, '_wp_paymentform_currency_settings', true);
        if (!$currencySettings) {
            $currencySettings = array();
        }
        $defaultSettings = GeneralSettings::getGlobalCurrencySettings();
        return wp_parse_args($currencySettings, $defaultSettings);
    }

    public static function getCurrencyAndLocale($formId)
    {
        $settings = self::getCurrencySettings($formId);
        $globalSettings = GeneralSettings::getGlobalCurrencySettings($formId);
        if ($settings['settings_type'] == 'global') {
            $settings = $globalSettings;
        } else {
            if (empty($settings['locale'])) {
                $settings['locale'] = 'auto';
            }
            if (empty($settings['currency'])) {
                $settings['currency'] = 'USD';
            }
            $settings['currency_sign_position'] = $globalSettings['currency_sign_position'];
            $settings['currency_separator'] = $globalSettings['currency_separator'];
            $settings['decimal_points'] = $globalSettings['decimal_points'];
        }


        $settings['currency_sign'] = GeneralSettings::getCurrencySymbol($settings['currency']);
        return $settings;
    }

    public static function getEditorShortCodes($formId)
    {
        $builderSettings = get_post_meta($formId, '_wp_paymentform_builder_settings', true);
        if (!$builderSettings) {
            return array();
        }
        $formattedShortcodes = array(
            'input'   => array(
                'title'      => 'Custom Input Items',
                'shortcodes' => array()
            ),
            'payment' => array(
                'title'      => 'Payment Items',
                'shortcodes' => array()
            )
        );

        $hasPayment = false;

        foreach ($builderSettings as $element) {
            $elementId = ArrayHelper::get($element, 'id');
            if ($element['group'] == 'input') {
                $formattedShortcodes['input']['shortcodes']['{input.' . $elementId . '}'] = self::getLabel($element);
            } elseif ($element['group'] == 'payment') {
                $formattedShortcodes['payment']['shortcodes']['{payment_item.' . $elementId . '}'] = self::getLabel($element);
                $hasPayment = true;
            } else if ($element['group'] == 'item_quantity') {
                $formattedShortcodes['input']['shortcodes']['{quantity.' . $elementId . '}'] = self::getLabel($element);
            }
        }

        $items = array($formattedShortcodes['input'], $formattedShortcodes['payment']);

        $submissionItem = array(
            'title'      => 'Submission Fields',
            'shortcodes' => array(
                '{submission.id}'              => __('Submission ID', 'wppayform'),
                '{submission.submission_hash}' => __('Submission Hash ID', 'wppayform'),
                '{submission.customer_name}'   => __('Customer Name', 'wppayform'),
                '{submission.customer_email}'  => __('Customer Email', 'wppayform'),
            )
        );
        if ($hasPayment) {
            $submissionItem['shortcodes']['{submission.payment_total}'] = __('Payment Total', 'wppayform');
        }
        $items[] = $submissionItem;
        return $items;
    }

    public static function getBuilderSettings($formId)
    {
        $builderSettings = get_post_meta($formId, '_wp_paymentform_builder_settings', true);
        if (!$builderSettings) {
            $builderSettings = array();
        }
        $defaultSettings = array();
        $elements = wp_parse_args($builderSettings, $defaultSettings);
        $allElements = GeneralSettings::getComponents();

        $parsedElements = array();

        foreach ($elements as $elementIndex => $element) {
            if (!empty($allElements[$element['type']]['editor_elements'])) {
                $element['editor_elements'] = $allElements[$element['type']]['editor_elements'];
            }
            $parsedElements[$elementIndex] = $element;
        }

        return $parsedElements;
    }

    public static function deleteForm($formID)
    {
        wp_delete_post($formID, true);
        wpPayformDB()->table('wpf_submissions')
            ->where('form_id', $formID)
            ->delete();

        wpPayformDB()->table('wpf_order_items')
            ->where('form_id', $formID)
            ->delete();

        wpPayformDB()->table('wpf_order_transactions')
            ->where('form_id', $formID)
            ->delete();

        wpPayformDB()->table('wpf_submission_activities')
            ->where('form_id', $formID)
            ->delete();

        return true;
    }

    private static function getLabel($element)
    {
        $elementId = ArrayHelper::get($element, 'id');
        if (!$label = ArrayHelper::get($element, 'field_options.admin_label')) {
            $label = ArrayHelper::get($element, 'field_options.label');
        }
        if (!$label) {
            $label = $elementId;
        }
        return $label;
    }

    public static function getDesignSettings($formId)
    {
        $settings = get_post_meta($formId, '_form_design_settings', true);
        if (!$settings) {
            $settings = array();
        }
        $defaults = array(
            'labelPlacement'         => 'top',
            'asteriskPlacement'      => 'none',
            'submit_button_position' => 'left'
        );
        return wp_parse_args($settings, $defaults);
    }

    public static function getSchedulingSettings($formId)
    {
        $settings = get_post_meta($formId, '_form_scheduling_settings', true);
        if (!$settings) {
            $settings = array();
        }
        $defaults = array(
            'limitNumberOfEntries' => array(
                'status'                => 'no',
                'limit_type'            => 'total',
                'number_of_entries'     => 100,
                'limit_payment_statuses'  => array(),
                'limit_exceeds_message' => __('Number of entry has been exceeds, Please check back later', 'wppayform')
            ),
            'scheduleForm'         => array(
                'status'               => 'no',
                'start_date'           => date('Y-m-d H:i:s'),
                'end_date'             => '',
                'before_start_message' => __('Form submission time schedule is not started yet. Please check back later', 'wppayform'),
                'expire_message'       => __('Form submission time has been expired.')
            ),
            'requireLogin'         => array(
                'status'  => 'no',
                'message' => __('You need to login to submit this form', 'wppayform')
            ),
            'restriction_applied_type' => 'hide_form'
        );
        return wp_parse_args($settings, $defaults);
    }
}