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
                    'type' => 'key_pair'
                )
            ),
            'field_options'   => array(
                'label' => '',
                'placeholder' => '',
                'required' => 'no',
                'options' => array(
                    array(
                        'label' => '',
                        'value' => ''
                    )
                )
            )
        );
    }

    public function render($element, $formId, $elements)
    {
        $this->renderSelectInput($element, $formId);
    }
}