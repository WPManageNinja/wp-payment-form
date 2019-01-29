<?php

namespace WPPayForm\Classes\FormComponents;

if (!defined('ABSPATH')) {
    exit;
}

class CustomerNameComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('customer_name', 13);
    }

    public function component()
    {
        return array(
            'type'            => 'customer_name',
            'editor_title'    => 'Customer Name',
            'group'           => 'input',
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
                'label' => 'Your Name',
                'placeholder' => 'Name',
                'required' => 'yes'
            )
        );
    }

    public function render($element, $formId, $elements)
    {
        $element['type'] = 'text';
        $this->renderNormalInput($element, $formId);
    }
}