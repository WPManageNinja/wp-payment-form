<?php

namespace WPPayForm\Classes\FormComponents;

if (!defined('ABSPATH')) {
    exit;
}

class NumberComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('number', 15);
    }

    public function component()
    {
        return array(
            'type'            => 'number',
            'editor_title'    => 'Number Field',
            'group'           => 'input',
            'is_markup'       => 'no',
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
                'min_value' => array(
                    'label' => 'Minimum Value',
                    'type'  => 'number'
                ),
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
        $element['type'] = 'number';
        $this->renderNormalInput($element, $formId);
    }
}