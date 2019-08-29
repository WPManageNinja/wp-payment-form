<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

class HiddenInputComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('hidden_input', 19);
        add_filter('wppayform/validate_component_on_save_hidden_input', array($this, 'validateOnSave'), 1, 3);
    }

    public function component()
    {
        return array(
            'type'            => 'hidden_input',
            'editor_title'    => 'Hidden Input',
            'group'           => 'input',
            'postion_group'   => 'general',
            'editor_elements' => array(
                'label'         => array(
                    'label' => 'Admin Field Label',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'default_value' => array(
                    'label' => 'Input Value',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'admin_label' => array(
                    'label' => 'Admin Label',
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
                'label'         => 'Hidden Value',
                'required'      => 'no',
                'default_value' => ''
            )
        );
    }

    public function validateOnSave($error, $element, $formId)
    {
        if (!ArrayHelper::get($element, 'field_options.default_value')) {
            $error = __('Value is required for item:', 'wppayform') . ' ' . ArrayHelper::get($element, 'field_options.label');
        }
        return $error;
    }

    public function render($element, $form, $elements)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }
        $inputId = 'wpf_input_' . $form->ID . '_' . $element['id'];
        $defaultValue = apply_filters('wppayform/input_default_value', ArrayHelper::get($fieldOptions, 'default_value'), $element, $form);

        $attributes = array(
            'name'  => $element['id'],
            'value' => $defaultValue,
            'type'  => 'hidden',
            'id'    => $inputId
        );

        if($userClass = ArrayHelper::get($fieldOptions, 'element_class')) {
            $attributes['class'] = $userClass;
        }

        ?>
        <input <?php echo $this->builtAttributes($attributes); ?> />
        <?php
    }
}