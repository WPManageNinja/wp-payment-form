<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;

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
                    'type'  => 'textarea',
                    'group' => 'general'
                ),
                'min_height' => array(
                    'label' => 'Minimum Height',
                    'type'  => 'number',
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
                'label' => 'Textarea Field',
                'placeholder' => '',
                'min_height' => '',
                'required' => 'no'
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }
        $controlClass = $this->elementControlClass($element);
        $inputClass = $this->elementInputClass($element);
        $inputId = 'wpf_input_' . $form->ID . '_' . $this->elementName;

        $attributes = array(
            'data-required' => ArrayHelper::get($fieldOptions, 'required'),
            'data-type' => 'textarea',
            'name' => $element['id'],
            'placeholder' => ArrayHelper::get($fieldOptions, 'placeholder'),
            'class' => $inputClass,
            'id' => $inputId
        );

        if($minHeight = ArrayHelper::get($fieldOptions, 'min_height')) {
            $attributes['style'] = 'min-height: '.$minHeight.'px;';
        }

        if (ArrayHelper::get($fieldOptions, 'required') == 'yes') {
            $attributes['required'] = true;
        }

        $defaultValue = apply_filters('wppayform/input_default_value', ArrayHelper::get($fieldOptions, 'default_value'), $element, $form);

        ?>
        <div data-element_type="<?php echo $this->elementName; ?>"
             class="<?php echo $controlClass; ?>">
            <?php $this->buildLabel($fieldOptions, $form, array('for' => $inputId)); ?>
            <div class="wpf_input_content">
                <textarea <?php echo $this->builtAttributes($attributes); ?>><?php echo $defaultValue; ?></textarea>
            </div>
        </div>
        <?php
    }

}