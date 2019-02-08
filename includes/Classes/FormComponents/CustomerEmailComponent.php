<?php

namespace WPPayForm\Classes\FormComponents;

if (!defined('ABSPATH')) {
    exit;
}

class CustomerEmailComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('customer_email', 11);
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
                'required' => 'yes'
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $element['type'] = 'email';
        $element['extra_input_class'] = 'wpf_customer_email';
        $this->renderNormalInput($element, $form);
    }
}