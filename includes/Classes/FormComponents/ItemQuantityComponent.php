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
                )
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

    public function render($element, $form, $elements)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }
        $controlClass = $this->elementControlClass($element);
        $inputClass = $this->elementInputClass($element);
        $inputId = 'wpf_input_' . $form->ID . '_' . $element['id'];

        $defaultValue = apply_filters('wppayform/input_default_value', ArrayHelper::get($fieldOptions, 'default_value'), $element, $form);

        $attributes = array(
            'data-required'       => ArrayHelper::get($fieldOptions, 'required'),
            'name'                => $element['id'],
            'placeholder'         => ArrayHelper::get($fieldOptions, 'placeholder'),
            'value'               => $defaultValue,
            'type'                => 'number',
            'min'                 => ArrayHelper::get($fieldOptions, 'min_value'),
            'max'                 => ArrayHelper::get($fieldOptions, 'max_value'),
            'class'               => $inputClass . ' wpf_item_qty',
            'data-target_product' => ArrayHelper::get($fieldOptions, 'target_product'),
            'id'                  => $inputId
        );
        ?>
        <div id="wpf_<?php echo $this->elementName; ?>" data-element_type="<?php echo $this->elementName; ?>"
             class="<?php echo $controlClass; ?>">
            <?php $this->buildLabel($fieldOptions, $form, array('for' => $inputId)); ?>
            <div class="wpf_input_content">
                <input <?php echo $this->builtAttributes($attributes); ?> />
            </div>
        </div>
        <?php
    }
}