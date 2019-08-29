<?php

namespace WPPayForm\Classes\FormComponents;

if (!defined('ABSPATH')) {
    exit;
}

class SelectComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('select', 16);
    }

    public function component()
    {
        return array(
            'type'            => 'select',
            'editor_title'    => 'Dropdown Field',
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
                'default_value' => array(
                    'label' => 'Default Value',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'options'       => array(
                    'label' => 'Field Choices',
                    'type'  => 'key_pair',
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
                'label'       => 'Dropdown',
                'placeholder' => '',
                'required'    => 'no',
                'options'     => array(
                    array(
                        'label' => 'Select Item 1',
                        'value' => 'Select Item 1'
                    ),
                    array(
                        'label' => 'Select Item 2',
                        'value' => 'Select Item 2'
                    )
                )
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $this->renderSelectInput($element, $form);
    }
}