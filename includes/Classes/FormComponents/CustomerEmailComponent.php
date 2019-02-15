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
            'editor_title'    => 'Customer Email',
            'group'           => 'input',
            'postion_group'   => 'general',
            'editor_elements' => array(
                'label'         => array(
                    'label' => 'Field Label',
                    'type'  => 'text'
                ),
                'placeholder'   => array(
                    'label' => 'Placeholder',
                    'type'  => 'text'
                ),
                'required'      => array(
                    'label' => 'Required',
                    'type'  => 'switch'
                ),
                'default_value' => array(
                    'label' => 'Default Value',
                    'type'  => 'text'
                ),
            ),
            'field_options'   => array(
                'label' => 'Email Address',
                'placeholder' => 'Email Address',
                'required' => 'yes',
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
        return $error;
    }

    public function render($element, $form, $elements)
    {
        $element['type'] = 'email';
        $element['extra_input_class'] = 'wpf_customer_email';
        $defaultValue = $element['field_options']['default_value'];
        if($defaultValue && strpos($defaultValue, '{current_user.user_email}') !== false) {
            $currentUserId = get_current_user_id();
            $replaceValue = '';
            if($currentUserId) {
                $currentUser = get_user_by('ID', $currentUserId);
                $replaceValue = $currentUser->user_email;
            }
            $element['field_options']['default_value'] = str_replace('{current_user.user_email}', $replaceValue, $defaultValue);
        }

        $this->renderNormalInput($element, $form);
    }
}