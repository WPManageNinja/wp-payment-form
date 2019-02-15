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
            'editor_title'    => 'Select Field',
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
                'options'       => array(
                    'label' => 'Field Choices',
                    'type'  => 'key_pair'
                )
            ),
            'field_options'   => array(
                'label'       => '',
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