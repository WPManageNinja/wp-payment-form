<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class CustomAmountComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('custom_payment_input', 5);
    }

    public function component()
    {
        return array(
            'type'            => 'custom_payment_input',
            'editor_title'    => 'User Inputed Pay Amount',
            'group'           => 'payment',
            'postion_group'   => 'payment',
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
                    'type'  => 'number'
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

    public function render($element, $form, $elements)
    {
        $currencySettings = Forms::getCurrencyAndLocale($form->ID);
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }
        $controlClass = $this->elementControlClass($element);
        $inputClass = $this->elementInputClass($element);
        $inputId = 'wpf_input_' . $form->ID . '_' . $element['id'];
        $label = ArrayHelper::get($fieldOptions, 'label');

        $attributes = array(
            'data-required' => ArrayHelper::get($fieldOptions, 'required'),
            'name' => $element['id'],
            'placeholder' => ArrayHelper::get($fieldOptions, 'placeholder'),
            'value' => ArrayHelper::get($fieldOptions, 'default_value'),
            'type' => 'number',
            'min' => ArrayHelper::get($fieldOptions, 'min_value'),
            'data-price' => 0,
            'id' => $inputId,
            'class' => $inputClass.' wpf_custom_amount wpf_payment_item'
        );
        if (ArrayHelper::get($fieldOptions, 'required') == 'yes') {
            $attributes['required'] = true;
        }
        ?>
        <div id="wpf_<?php echo $this->elementName; ?>" data-element_type="<?php echo $this->elementName; ?>"
             class="<?php echo $controlClass; ?>">
            <?php $this->buildLabel($fieldOptions, $form, array('for' => $inputId)); ?>
            <div class="wpf_input_content">
                <div class="wpf_form_item_group">
                    <div class="wpf_input-group-prepend">
                        <div class="wpf_input-group-text"><?php echo $currencySettings['currency_sign']; ?></div>
                    </div>
                    <input <?php echo $this->builtAttributes($attributes); ?> />
                </div>
            </div>
        </div>
        <?php
    }
}