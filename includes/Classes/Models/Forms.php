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

        $forms = apply_filters('wppayment_forms_get_all_forms', $forms);

        $total = wp_count_posts('wp_payform');
        $total = intval($total->publish);
        $lastPage = ceil($total / $args['posts_per_page']);

        return array(
            'forms'     => $forms,
            'total'     => $total,
            'last_page' => $lastPage
        );
    }

    public static function create($data)
    {
        $data['post_type'] = 'wp_payform';
        $data['post_status'] = 'publish';
        $id = wp_insert_post($data);
        return $id;
    }

    public static function getForm($formId)
    {
        $form = get_post($formId, 'OBJECT');
        if(!$form && $form->post_type != 'wp_payform') {
            return false;
        }
        $form->preview_url = site_url('?wp_paymentform_preview=' . $form->ID);
        return $form;
    }

    public static function getPaymentSettings( $formId )
    {
        $paymentSettings = get_post_meta($formId, '_wp_paymentform_payment_settings', true);
        if(!$paymentSettings) {
            $paymentSettings = array();
        }
        $defaultSettings = array(
            'payment_type' => 'one_time',
            'min_amount' => '',
            'default_amount' => '',
            'currency_setting' => 'global',
            'custom_amount_label' => __('Choose Your Amount', 'wppayform'),
            'payment_amount' => '10.00',
            'currency' => 'USD',
            'locale' => 'en'
        );

        return wp_parse_args($paymentSettings, $defaultSettings);
    }

    public static function getBuilderSettings($formId) {
        $builderSettings = get_post_meta($formId, '_wp_paymentform_builder_settings', true);
        if(!$builderSettings) {
            $builderSettings = array();
        }
        $defaultSettings = array();
        $elements = wp_parse_args($builderSettings, $defaultSettings);
        $generalSettings = new GeneralSettings();
        $allElements = $generalSettings->getComponents();

        $parsedElements = array();

        foreach ($elements as $elementIndex => $element) {
            if( !empty($allElements[$element['type']]['editor_elements']) ) {
                $element['editor_elements'] = $allElements[$element['type']]['editor_elements'];
            }
            $parsedElements[$elementIndex] = $element;
        }

        return $parsedElements;
    }
}