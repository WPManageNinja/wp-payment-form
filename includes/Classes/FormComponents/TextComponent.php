<?php
namespace WPPayForm\Classes\FormComponents;

if (!defined('ABSPATH')) {
    exit;
}

class TextComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('text', 13);
    }

    public function component()
    {
        return array(
            'type'            => 'text',
            'editor_title'    => 'Single Line Text',
            'group'           => 'input',
            'postion_group'   => 'general',
            'editor_elements' => array(
                'label'         => array(
                    'label' => 'Field Label',
                    'type'  => 'text'
                ),
                'placeholder'    => array(
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
                )
            ),
            'field_options'   => array(
                'label' => '',
                'placeholder' => '',
                'required' => 'no'
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $element['type'] = 'text';
        $this->renderNormalInput($element, $form);
    }
}