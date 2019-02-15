<?php

namespace WPPayForm\Classes\FormComponents;

if (!defined('ABSPATH')) {
    exit;
}

class RadioComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('radio', 17);
    }

    public function component()
    {
        return array(
            'type'            => 'radio',
            'editor_title'    => 'Radio Field',
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
                    'label' => 'Field Choices',
                    'type' => 'key_pair'
                )
            ),
            'field_options'   => array(
                'label' => '',
                'placeholder' => '',
                'required' => 'no',
                'options' => array(
                    array(
                        'label' => 'Radio Item 1',
                        'value' => 'Radio Item 1'
                    ),
                    array(
                        'label' => 'Radio Item 2',
                        'value' => 'Radio Item 2'
                    )
                )
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $this->renderRadioInput($element, $form);
    }
}