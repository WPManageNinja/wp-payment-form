<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;

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

    public function render($element, $formId, $elements)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }
        $controlClass = $this->elementControlClass($element);
        $inputClass = $this->elementInputClass($element);
        $inputId = 'wpf_input_' . $formId . '_' . $this->elementName;
        $label = ArrayHelper::get($fieldOptions, 'label');

        $attributes = array(
            'data-required' => ArrayHelper::get($fieldOptions, 'required'),
            'name' => $element['id'],
            'placeholder' => ArrayHelper::get($fieldOptions, 'placeholder'),
            'value' => ArrayHelper::get($fieldOptions, 'default_value'),
            'type' => 'number',
            'min' => ArrayHelper::get($fieldOptions, 'min_value'),
            'data-price' => 0,
            'class' => $inputClass.' wpf_custom_amount wpf_payment_item'
        );
        if (ArrayHelper::get($fieldOptions, 'required') == 'yes') {
            $attributes['required'] = true;
        }
        ?>
        <div id="wpf_<?php echo $this->elementName; ?>" data-element_type="<?php echo $this->elementName; ?>"
             class="<?php echo $controlClass; ?>">
            <?php if ($label): ?>
                <label for="<?php echo $inputId; ?>"><?php echo $label; ?></label>
            <?php endif; ?>
            <input <?php echo $this->builtAttributes($attributes); ?> />
        </div>
        <?php
    }
}