<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class DemoRecurringPaymentComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('recurring_payment_item', 1);
    }

    public function component()
    {
        return array(
            'type'             => 'recurring_payment_item',
            'editor_title'     => __('Recurring Payment Item', 'wppayform'),
            'group'            => 'payment',
            'postion_group'    => 'payment',
            'editor_elements'  => array(
                'info'                     => array(
                    'type' => 'info_html',
                    'info' => '<h3 style="color: firebrick; text-align: center;">Recurring Payments require WPPayForm Pro. Please Upgrade To Pro</h3><br />'
                ),
                'label'                     => array(
                    'label' => 'Recurring Payment Item Name',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'required'                  => array(
                    'label' => 'Required',
                    'type'  => 'switch',
                    'group' => 'general'
                ),
                'show_main_label'           => array(
                    'label' => 'Show Pricing Label',
                    'type'  => 'switch',
                    'group' => 'general'
                ),
                'show_payment_summary'      => array(
                    'label' => 'Show Payment Summary',
                    'type'  => 'switch',
                    'group' => 'general'
                ),
                'recurring_payment_options' => array(
                    'type'         => 'recurring_payment_options',
                    'group'        => 'general',
                    'label'        => 'Configure Recurring Subscription Payment Plans',
                    'choice_label' => __('Choose your pricing plan'),
                    'choice_types' => array(
                        'simple'          => __('Simple Recurring Plan (Single)', 'wppayform'),
                        'choose_single'   => __('Chose One from Multiple Pricing Plans', 'wppayform'),
                        //'choose_multiple' => __('Choose Multiple Plan from Pricing Plans', 'wppayform')
                    ),
                    'selection_types' => array(
                        'radio' => __('Radio input field', 'wppayform'),
                        'select' => __('Select input field', 'wppayform')
                    )
                ),
                'admin_label' => array(
                    'label' => 'Admin Label',
                    'type'  => 'text',
                    'group' => 'advanced'
                ),
                'wrapper_class' => array(
                    'label' => 'Field Wrapper CSS Class',
                    'type'  => 'text',
                    'group' => 'advanced'
                ),
                'element_class' => array(
                    'label' => 'Input element CSS Class',
                    'type'  => 'text',
                    'group' => 'advanced'
                ),
            ),
            'is_system_field'  => true,
            'is_payment_field' => true,
            'field_options'    => array(
                'label'                     => __('Subscription Item', 'wppayform'),
                'required'                  => 'yes',
                'show_main_label'           => 'yes',
                'show_payment_summary'      => 'yes',
                'recurring_payment_options' => array(
                    'choice_type'     => 'simple',
                    'selection_type'  => 'radio',
                    'pricing_options' => [
                        [
                            'name'                => __('$9.99 / Month', 'wppayform'),
                            'trial_days'          => 0,
                            'has_trial_days'      => 'no',
                            'trial_days'          => 0,
                            'billing_interval'    => 'month',
                            'bill_times'          => 0,
                            'has_signup_fee'      => 'no',
                            'signup_fee'          => 0,
                            'subscription_amount' => '9.99',
                            'is_default'          => 'yes',
                            'plan_features' => []
                        ]
                    ]
                )
            )
        );
    }

    public function validateOnSave($error, $element, $formId)
    {
        $pricingDetails = ArrayHelper::get($element, 'field_options.pricing_details', array());
        $paymentType = ArrayHelper::get($pricingDetails, 'one_time_type');
        if ($paymentType == 'single') {
            if (!ArrayHelper::get($pricingDetails, 'payment_amount')) {
                $error = __('Payment amount is required for item:', 'wppayform') . ' ' . ArrayHelper::get($element, 'field_options.label');
            }
        } else if ($paymentType == 'choose_multiple' || $paymentType == 'choose_single') {
            if (!count(ArrayHelper::get($pricingDetails, 'multiple_pricing', array()))) {
                $error = __('Pricing Details is required for item:', 'wppayform') . ' ' . ArrayHelper::get($element, 'field_options.label');
            }
        }
        return $error;
    }

    public function render($element, $form, $elements)
    {
        return;
    }
}