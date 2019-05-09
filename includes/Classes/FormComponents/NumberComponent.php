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
                'min_value' => array(
                    'label' => 'Minimum Value',
                    'type'  => 'number',
                    'group' => 'general'
                ),
            ),
            'field_options'   => array(
                'label' => 'Numeric Value',
                'placeholder' => '',
                'required' => 'no'
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $element['type'] = 'number';
        $this->renderNormalInput($element, $form);
    }
}