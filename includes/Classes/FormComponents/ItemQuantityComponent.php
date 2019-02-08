<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\Models\Forms;

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
                    'type'  => 'text'
                ),
                'placeholder'    => array(
                    'label' => 'Placeholder',
                    'type'  => 'text'
                ),
                'required'       => array(
                    'label' => 'Required',
                    'type'  => 'switch'
                ),
                'default_value'  => array(
                    'label' => 'Default Quantity',
                    'type'  => 'number'
                ),
                'target_product' => array(
                    'label' => 'Target Payment Item',
                    'type'  => 'product_selector'
                ),
                'min_value'      => array(
                    'label' => 'Minimum Quantity',
                    'type'  => 'number'
                ),
                'max_value'      => array(
                    'label' => 'Maximum Quantity',
                    'type'  => 'number'
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
        $attributes = array(
            'data-required'       => ArrayHelper::get($fieldOptions, 'required'),
            'name'                => $element['id'],
            'placeholder'         => ArrayHelper::get($fieldOptions, 'placeholder'),
            'value'               => ArrayHelper::get($fieldOptions, 'default_value'),
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