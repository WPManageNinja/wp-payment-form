<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class RecurringPaymentComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('recurring_payment_item', 1);
        //add_filter('wppayform/validate_component_on_save_payment_item', array($this, 'validateOnSave'), 1, 3);
    }

    public function component()
    {
        return array(
            'type'             => 'recurring_payment_item',
            'editor_title'     => __('Recurring Payment Item', 'wppayform'),
            'group'            => 'payment',
            'postion_group'    => 'payment',
            'editor_elements'  => array(
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
                'recurring_payment_options' => array(
                    'type'         => 'recurring_payment_options',
                    'group'        => 'general',
                    'label'        => 'Configure Recurring Subscription Payment Plans',
                    'choice_label' => __('Choose your pricing plan'),
                    'choice_types' => array(
                        'simple'          => __('Simple Recurring Plan (Single)', 'payform'),
                        'choose_single'   => __('Chose One from Multiple Pricing Plans', 'payform'),
                        'choose_multiple' => __('Choose Multiple Plan from Pricing Plans', 'payform')
                    )
                )
            ),
            'is_system_field'  => true,
            'is_payment_field' => true,
            'field_options'    => array(
                'label'                     => __('Subscription Item', 'payform'),
                'required'                  => 'yes',
                'recurring_payment_options' => array(
                    'choice_type'     => 'simple',
                    'pricing_options' => [
                        [
                            'name'                => __('$9.99 / Month', 'payform'),
                            'trial_days'          => 0,
                            'has_trial_days'      => 'no',
                            'trial_preriod_days'  => 0,
                            'billing_interval' => 'month',
                            'bill_times'          => 0,
                            'has_signup_fee'      => 'no',
                            'signup_fee'          => 0,
                            'subscription_amount' => '9.99'
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
        $pricingDetails = ArrayHelper::get($element, 'field_options.recurring_payment_options', array());
        if (!$pricingDetails) {
            return;
        }
        $element['field_options']['default_value'] = 0;
        ?>
        <input <?php echo $this->builtAttributes([
            'type'  => 'hidden',
            'name'  => $element['id'],
            'value' => 0
        ]); ?> />
        <?php
    }

}