<?php

namespace WPPayForm\Classes\Models;

use WPPayForm\Classes\Entry\Entry;
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
    public static function getForms($args = array(), $with = array())
    {
        $whereArgs = array(
            'post_type'   => 'wp_payform',
            'post_status' => 'publish'
        );

        $whereArgs = apply_filters('wppayform/all_forms_where_args', $whereArgs);

        $formsQuery = wpPayFormDB()->table('posts')
            ->orderBy('ID', 'DESC')
            ->offset($args['offset'])
            ->limit($args['posts_per_page']);

        foreach ($whereArgs as $key => $where) {
            $formsQuery->where($key, $where);
        }

        if (!empty($args['s'])) {
            $formsQuery->where(function ($q) use ($args) {
                $q->where('post_title', 'LIKE', "%{$args['s']}%");
                $q->orWhere('ID', 'LIKE', "%{$args['s']}%");
            });
        }

        $total = $formsQuery->count();

        $forms = $formsQuery->get();

        $submissionModel = new Submission();

        foreach ($forms as $form) {
            $form->preview_url = site_url('?wp_paymentform_preview=' . $form->ID);
            if (in_array('entries_count', $with)) {
                $form->entries_count = $submissionModel->getEntryCountByPaymentStatus($form->ID);
            }
        }

        $forms = apply_filters('wppayform/get_all_forms', $forms);

        $lastPage = ceil($total / $args['posts_per_page']);

        return array(
            'forms'     => $forms,
            'total'     => $total,
            'last_page' => $lastPage
        );
    }

    public static function getTotalCount()
    {
        return wpPayFormDB()->table('posts')
            ->where('post_type', 'wp_payform')
            ->count();
    }

    public static function getAllAvailableForms()
    {
        return wpPayFormDB()->table('posts')
            ->select(array('ID', 'post_title'))
            ->where('post_type', 'wp_payform')
            ->orderBy('ID', 'DESC')
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
        $settings = get_post_meta($formId, 'wppayform_submit_button_settings', true);
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
        if (!$form || $form->post_type != 'wp_payform') {
            return false;
        }
        $form->show_title_description = get_post_meta($formId, 'wppayform_show_title_description', true);
        $form->preview_url = site_url('?wp_paymentform_preview=' . $form->ID);
        return $form;
    }

    public static function getFormattedElements($formId)
    {
        $elements = Forms::getBuilderSettings($formId);
        $formattedElements = array(
            'input'                  => array(),
            'payment'                => array(),
            'payment_method_element' => array(),
            'item_quantity'          => array()
        );
        foreach ($elements as $element) {
            $formattedElements[$element['group']][$element['id']] = array(
                'options' => $element['field_options'],
                'type'    => $element['type'],
                'id'      => $element['id'],
                'label'   => ArrayHelper::get($element['field_options'], 'label')
            );
        }

        return $formattedElements;
    }

    public static function hasPaymentFields($formId)
    {
        $elements = Forms::getBuilderSettings($formId);
        foreach ($elements as $element) {
            if (in_array($element['group'], ['payment', 'payment_method_element'])) {
                return true;
            }
        }
        return false;
    }

    public static function getPaymentMethodElements($formId)
    {
        $elements = self::getFormattedElements($formId);
        return $elements['payment_method_element'];
    }

    public static function getFormInputLabels($formId)
    {
        $elements = get_post_meta($formId, 'wppayform_paymentform_builder_settings', true);
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
        $confirmationSettings = get_post_meta($formId, 'wppapyform_paymentform_confirmation_settings', true);
        if (!$confirmationSettings) {
            $confirmationSettings = array();
        }
        $defaultSettings = array(
            'confirmation_type'    => 'custom',
            'redirectTo'           => 'samePage',
            'customUrl'            => '',
            'messageToShow'        => __('Form has been successfully submitted', 'wppayform'),
            'samePageFormBehavior' => 'hide_form',
        );
        return wp_parse_args($confirmationSettings, $defaultSettings);
    }

    public static function getReceiptSettings($formId)
    {
        $receptSettings = get_post_meta($formId, 'wppapyform_receipt_settings', true);
        if (!$receptSettings) {
            $receptSettings = array();
        } else {
            if(isset($receptSettings['receipt_header'])) {
                if(strpos($receptSettings['receipt_header'], '[wppayform_reciept]') !== false || strpos($receptSettings['receipt_header'], '{submission.payment_receipt}') !== false) {
                    $receptSettings['receipt_header'] = str_replace(['[wppayform_reciept]', '{submission.payment_receipt}'], '', $receptSettings['receipt_header']);
                }
            }

            if(isset($receptSettings['receipt_footer'])) {
                if(strpos($receptSettings['receipt_footer'], '[wppayform_reciept]') !== false || strpos($receptSettings['receipt_footer'], '{submission.payment_receipt}') !== false) {
                    $receptSettings['receipt_footer'] = str_replace(['[wppayform_reciept]', '{submission.payment_receipt}'], '', $receptSettings['receipt_footer']);
                }
            }
        }

        $defaultSettings = array(
            'receipt_header'  => __('Thanks for your submission. Here are the details of your submission:', 'wppayform'),
            'receipt_footer'  => '',
            'info_modules' => [
                'input_details' => 'yes',
                'payment_info' => 'yes'
            ],
        );

        return wp_parse_args($receptSettings, $defaultSettings);
    }


    public static function getCurrencySettings($formId)
    {
        $currencySettings = get_post_meta($formId, 'wppayform_paymentform_currency_settings', true);
        $globalSettings = GeneralSettings::getGlobalCurrencySettings();
        if (!$currencySettings) {
            $currencySettings = array();
        } else if($currencySettings['settings_type'] == 'global') {
            return $globalSettings;
        }
        return wp_parse_args($currencySettings, $globalSettings);
    }

    public static function getCurrencyAndLocale($formId)
    {
        $settings = self::getCurrencySettings($formId);
        $globalSettings = GeneralSettings::getGlobalCurrencySettings($formId);
        if (isset($settings['settings_type']) && $settings['settings_type'] != 'global') {
            if (empty($settings['locale'])) {
                $settings['locale'] = 'auto';
            }
            if (empty($settings['currency'])) {
                $settings['currency'] = 'USD';
            }
            $settings['currency_sign_position'] = $globalSettings['currency_sign_position'];
            $settings['currency_separator'] = $globalSettings['currency_separator'];
            $settings['decimal_points'] = $globalSettings['decimal_points'];
        } else {
            $settings = $globalSettings;
        }
        $settings['currency_sign'] = GeneralSettings::getCurrencySymbol($settings['currency']);

        $settings['is_zero_decimal'] = GeneralSettings::isZeroDecimal($settings['currency']);
        return $settings;
    }

    public static function getEditorShortCodes($formId, $html = true)
    {
        $builderSettings = get_post_meta($formId, 'wppayform_paymentform_builder_settings', true);

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
                '{submission.payment_method}'  => __('Payment Method', 'wppayform')
            )
        );
        if ($hasPayment) {
            $submissionItem['shortcodes']['{submission.payment_total}'] = __('Payment Total', 'wppayform');
        }

        if ($html) {
            $submissionItem['shortcodes']['{submission.all_input_field_html}'] = __('All input field html', 'wppayform');
            if ($hasPayment) {
                $submissionItem['shortcodes']['{submission.product_items_table_html}'] = __('Order items table html', 'wppayform');
            }

            // check if subsction payment is available for this for
            $hasRecurringField = get_post_meta($formId, 'wpf_has_recurring_field', true) == 'yes';
            if ($hasRecurringField) {
                $submissionItem['shortcodes']['{submission.subscription_details_table_html}'] = __('Subscription details table html ', 'wppayform');
            }

            $submissionItem['shortcodes']['{submission.payment_receipt}'] = __('Payment Receipt', 'wppayform');

        }


        $items[] = $submissionItem;
        return $items;
    }

    public static function getBuilderSettings($formId)
    {
        $builderSettings = get_post_meta($formId, 'wppayform_paymentform_builder_settings', true);
        if (!$builderSettings) {
            $builderSettings = array();
        }
        $defaultSettings = array();
        $elements = wp_parse_args($builderSettings, $defaultSettings);
        $allElements = GeneralSettings::getComponents();
        $parsedElements = array();

        foreach ($elements as $elementIndex => $element) {
            if (!empty($allElements[$element['type']])) {
                $componentElement = $allElements[$element['type']];
                $fieldOption = ArrayHelper::get($element, 'field_options');
                if ($fieldOption) {
                    $componentElement['field_options'] = $fieldOption;
                }
                $componentElement['id'] = ArrayHelper::get($element, 'id');
                $element = $componentElement;
            }
            $parsedElements[$elementIndex] = $element;
        }
        return $parsedElements;
    }

    public static function deleteForm($formID)
    {
        wp_delete_post($formID, true);
        wpPayFormDB()->table('posts')
            ->where('ID', $formID)
            ->delete();

        wpPayFormDB()->table('wpf_submissions')
            ->where('form_id', $formID)
            ->delete();

        wpPayFormDB()->table('wpf_order_items')
            ->where('form_id', $formID)
            ->delete();

        wpPayFormDB()->table('wpf_order_transactions')
            ->where('form_id', $formID)
            ->delete();

        wpPayFormDB()->table('wpf_submission_activities')
            ->where('form_id', $formID)
            ->delete();

        wpPayFormDB()->table('wpf_subscriptions')
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
        $settings = get_post_meta($formId, 'wppayform_form_design_settings', true);
        if (!$settings) {
            $settings = array();
        }
        $defaults = array(
            'labelPlacement'         => 'top',
            'asteriskPlacement'      => 'none',
            'submit_button_position' => 'left',
            'extra_styles'           => array(
                'wpf_default_form_styles' => 'yes',
                'wpf_bold_labels'         => 'no'
            )
        );
        return wp_parse_args($settings, $defaults);
    }

    public static function getSchedulingSettings($formId)
    {
        $settings = get_post_meta($formId, 'wppayform_form_scheduling_settings', true);
        if (!$settings) {
            $settings = array();
        }
        $defaults = array(
            'limitNumberOfEntries'     => array(
                'status'                 => 'no',
                'limit_type'             => 'total',
                'number_of_entries'      => 100,
                'limit_payment_statuses' => array(),
                'limit_exceeds_message'  => __('Number of entry has been exceeds, Please check back later', 'wppayform')
            ),
            'scheduleForm'             => array(
                'status'               => 'no',
                'start_date'           => gmdate('Y-m-d H:i:s'),
                'end_date'             => '',
                'before_start_message' => __('Form submission time schedule is not started yet. Please check back later', 'wppayform'),
                'expire_message'       => __('Form submission time has been expired.')
            ),
            'requireLogin'             => array(
                'status'  => 'no',
                'message' => __('You need to login to submit this form', 'wppayform')
            ),
            'restriction_applied_type' => 'hide_form'
        );
        return wp_parse_args($settings, $defaults);
    }

    public static function hasRecurring($formId)
    {
        return get_post_meta($formId, 'wpf_has_recurring_field', true) == 'yes';
    }

    public static function recaptchaType($formId)
    {
        $globalSettings = GeneralSettings::getRecaptchaSettings();
        $type = ArrayHelper::get($globalSettings, 'recaptcha_version');
        if ($type == 'none') {
            return false;
        }

        $recaptchaStatus = get_post_meta($formId, '_recaptcha_status', true);

        if ($recaptchaStatus == 'yes') {
            return $type;
        }
        return false;

    }
}