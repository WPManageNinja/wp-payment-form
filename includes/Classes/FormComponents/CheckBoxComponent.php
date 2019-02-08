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
                    'label' => 'Field Options',
                    'type'  => 'key_pair'
                )
            ),
            'field_options'   => array(
                'label' => ''
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $this->renderCheckBoxInput($element, $form);
    }

}