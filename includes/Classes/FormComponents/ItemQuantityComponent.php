<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

class ItemQuantityComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('item_quantity', 5);
        add_filter('wppayform/validate_component_on_save_item_quantity', array($this, 'validateOnSave'), 1, 3);
    }

    public function component()
    {
        return array(
            'type'            => 'item_quantity',
            'editor_title'    => 'Item Quantity',
            'group'           => 'item_quantity',
            'postion_group'   => 'payment',
            'editor_elements' => array(
                'label'          => array(
                    'label' => 'Field Label',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'placeholder'    => array(
                    'label' => 'Placeholder',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'required'       => array(
                    'label' => 'Required',
                    'type'  => 'switch',
                    'group' => 'general'
                ),
                'default_value'  => array(
                    'label' => 'Default Quantity',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'target_product' => array(
                    'label' => 'Target Payment Item',
                    'type'  => 'product_selector',
                    'group' => 'general',
                    'info'  => 'Please select the product in where the quantity will be applied'
                ),
                'min_value'      => array(
                    'label' => 'Minimum Quantity',
                    'type'  => 'number',
                    'group' => 'general'
                ),
                'max_value'      => array(
                    'label' => 'Maximum Quantity',
                    'type'  => 'number',
                    'group' => 'general'
                ),
                'admin_label'    => array(
                    'label' => 'Admin Label',
                    'type'  => 'text',
                    'group' => 'advanced'
                ),
                'wrapper_class'  => array(
                    'label' => 'Field Wrapper CSS Class',
                    'type'  => 'text',
                    'group' => 'advanced'
                ),
                'element_class'  => array(
                    'label' => 'Input element CSS Class',
                    'type'  => 'text',
                    'group' => 'advanced'
                ),
            ),
            'field_options'   => array(
                'label'          => 'Quantity',
                'placeholder'    => 'Provide Quantity',
                'required'       => 'yes',
                'min_value'      => 1,
                'target_product' => ''
            )
        );
    }

    public function validateOnSave($error, $element, $formId)
    {
        if (!ArrayHelper::get($element, 'field_options.target_product')) {
            $error = __('Target Product is required for item:', 'wppayform') . ' ' . ArrayHelper::get($element, 'field_options.label');
        }
        return $error;
    }

    public function render($element, $form, $elements)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }
        $controlClass = $this->elementControlClass($element);
        $inputClass = $this->elementInputClass($element);
        $inputId = 'wpf_input_' . $form->ID . '_' . $element['id'];

        $defaultValue = '';
        if (isset($fieldOptions['default_value'])) {
            $defaultValue = $fieldOptions['default_value'];
        }

        $defaultValue = apply_filters('wppayform/input_default_value', $defaultValue, $element, $form);

        $attributes = array(
            'data-required'       => ArrayHelper::get($fieldOptions, 'required'),
            'data-type'           => 'input',
            'name'                => $element['id'],
            'placeholder'         => ArrayHelper::get($fieldOptions, 'placeholder'),
            'value'               => $defaultValue,
            'type'                => 'number',
            'min'                 => ArrayHelper::get($fieldOptions, 'min_value', '0'),
            'max'                 => ArrayHelper::get($fieldOptions, 'max_value'),
            'class'               => $inputClass . ' wpf_item_qty',
            'data-target_product' => ArrayHelper::get($fieldOptions, 'target_product'),
            'id'                  => $inputId
        );

        if (ArrayHelper::get($fieldOptions, 'required') == 'yes') {
            $attributes['required'] = true;
        }

        ?>
        <div data-element_type="<?php echo $this->elementName; ?>"
             class="<?php echo $controlClass; ?>">
            <?php $this->buildLabel($fieldOptions, $form, array('for' => $inputId)); ?>
            <div class="wpf_input_content">
                <input <?php echo $this->builtAttributes($attributes); ?> />
            </div>
        </div>
        <?php
    }
}