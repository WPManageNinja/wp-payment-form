<?php

namespace WPPayForm\Classes\FormComponents;

if (!defined('ABSPATH')) {
    exit;
}

class TextAreaComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('textarea', 14);
    }

    public function component()
    {
        return array(
            'type'            => 'textarea',
            'editor_title'    => 'Textarea Field',
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
                    'type'  => 'textarea'
                )
            ),
            'field_options'   => array(
                'label' => '',
                'placeholder' => '',
                'required' => 'no'
            )
        );
    }

    public function render($element, $formId, $elements)
    {
        echo "<pre>Called";
        print_r($element);
    }

}