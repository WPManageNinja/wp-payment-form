<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

class CustomerEmailComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('customer_email', 11);
        add_filter('wppayform/validate_data_on_submission_customer_email', array($this, 'validateEmailOnSubmission'), 10, 4);
    }

    public function component()
    {
        return array(
            'type'            => 'customer_email',
            'editor_title'    => 'Email',
            'group'           => 'input',
            'postion_group'   => 'general',
            'editor_elements' => array(
                'label'         => array(
                    'label' => 'Field Label',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'placeholder'   => array(
                    'label' => 'Placeholder',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'required'      => array(
                    'label' => 'Required',
                    'type'  => 'switch',
                    'group' => 'general'
                ),
                'confirm_email'      => array(
                    'label' => 'Enable Confirm Email Field',
                    'type'  => 'confirm_email_switch',
                    'group' => 'general'
                ),
                'default_value' => array(
                    'label' => 'Default Value',
                    'type'  => 'text',
                    'group' => 'general'
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
            'field_options'   => array(
                'label' => 'Email Address',
                'placeholder' => 'Email Address',
                'required' => 'yes',
                'confirm_email' => 'no',
                'confirm_email_label' => 'Confirm Email',
                'default_value' => ''
            )
        );
    }

    public function validateEmailOnSubmission($error, $elementId, $element, $data)
    {
        // Validation Already failed so We are just returning it
        if($error) {
            return $error;
        }
        $value = ArrayHelper::get($data, $elementId);
        if($value) {
            // We have to check if it's a valid email address or not
            if(!is_email($value)) {
                return __('Valid email address is required for field:', 'wppayform').' '.ArrayHelper::get($element, 'label');
            }
        }

        // check if confirm email exists and need to validate
        if(ArrayHelper::get($element, 'options.confirm_email') == 'yes') {
            $confirmEmailvalue = ArrayHelper::get($data, '__confirm_'.$elementId);
            if($confirmEmailvalue != $value) {
                return ArrayHelper::get($element, 'label') .' & '.ArrayHelper::get($element, 'options.confirm_email_label') .__(' does not match', 'wppayform');
            }
        }

        return $error;
    }

    public function render($element, $form, $elements)
    {
        $element['type'] = 'email';
        $element['extra_input_class'] = 'wpf_customer_email';
        $defaultValue = apply_filters('wppayform/input_default_value', ArrayHelper::get($element['field_options'], 'default_value'), $element, $form);
        $element['field_options']['default_value'] = $defaultValue;
        $this->renderNormalInput($element, $form);
        if(ArrayHelper::get($element, 'field_options.confirm_email') == 'yes') {
            $element['field_options']['extra_data_atts'] = array(
                'data-parent_confirm_name' =>  $element['id']
            );
            $element['extra_input_class'] = 'wpf_confirm_email';
            $element['id'] = '__confirm_'.$element['id'];
            $element['field_options']['placeholder'] = ArrayHelper::get($element, 'field_options.confirm_email_label', 'Confirm Email');
            $element['field_options']['label'] = ArrayHelper::get($element, 'field_options.confirm_email_label', 'Confirm Email');
            $this->renderNormalInput($element, $form);
        }
    }
}