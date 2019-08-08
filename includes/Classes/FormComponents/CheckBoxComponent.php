<?php

namespace WPPayForm\Classes\FormComponents;

if (!defined('ABSPATH')) {
    exit;
}

class CheckBoxComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('checkbox', 18);
    }

    public function component()
    {
        return array(
            'type'            => 'checkbox',
            'editor_title'    => 'Checkbox Field',
            'group'           => 'input',
            'postion_group'   => 'general',
            'editor_elements' => array(
                'label'         => array(
                    'label' => 'Field Label',
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
                    'label' => 'Field Options',
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
                'label' => 'Checkboxes',
                'required' => 'no',
                'default_value' => '',
                'options'     => array(
                    array(
                        'label' => 'Checkbox Item 1',
                        'value' => 'Checkbox Item 1'
                    ),
                    array(
                        'label' => 'Checkbox Item 2',
                        'value' => 'Checkbox Item 2'
                    )
                )
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $this->renderCheckBoxInput($element, $form);
    }

}