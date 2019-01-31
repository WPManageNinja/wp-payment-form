<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

abstract class BaseComponent
{
    public $elementName = '';

    public function __construct($elementName, $priority = 10)
    {
        $this->elementName = $elementName;
        $this->registerHooks($elementName, $priority);
    }

    public function registerHooks($elementName, $priority = 10)
    {
        add_filter('wp_payment_form_components', array($this, 'addComponent'), $priority);
        add_action('wppayform_render_' . $elementName, array($this, 'render'), 10, 3);
    }

    public function addComponent($components)
    {
        $component = $this->component();
        if ($component) {
            $components[$this->elementName] = $this->component();
        }
        return $components;
    }

    public function renderNormalInput($element, $formId)
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
            'type' => ArrayHelper::get($element, 'type', 'text'),
            'class' => $inputClass
        );

        if(isset($fieldOptions['min_value'])) {
            $attributes['min'] = $fieldOptions['min_value'];
        }

        if(isset($fieldOptions['min_value'])) {
            $attributes['max'] = $fieldOptions['max_value'];
        }

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

    public function renderSelectInput($element, $formId)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }
        $controlClass = $this->elementControlClass($element);
        $inputClass = $this->elementInputClass($element);
        $inputId = 'wpf_input_' . $formId . '_' . $this->elementName;
        $label = ArrayHelper::get($fieldOptions, 'label');
        $defaultValue = ArrayHelper::get($fieldOptions, 'default_value');

        $options = ArrayHelper::get($fieldOptions, 'options', array());
        $placeholder = ArrayHelper::get($fieldOptions, 'placeholder');
        $inputAttributes = array(
            'data-required' => ArrayHelper::get($fieldOptions, 'required'),
            'name'          => $element['id'],
            'class'         => $inputClass,
            'id'            => $inputId
        );
        if (ArrayHelper::get($fieldOptions, 'required') == 'yes') {
            $inputAttributes['required'] = 'true';
        }
        $controlAttributes = array(
            'id'                => 'wpf_' . $this->elementName,
            'data-element_type' => $this->elementName,
            'class'             => $controlClass
        );
        ?>
        <div <?php echo $this->builtAttributes($controlAttributes); ?>>
            <?php if ($label): ?>
                <label for="<?php echo $inputId; ?>"><?php echo $label; ?></label>
            <?php endif; ?>
            <select <?php echo $this->builtAttributes($inputAttributes); ?>>
                <?php if ($placeholder): ?>
                    <option data-type="placeholder" value=""><?php echo $placeholder; ?></option>
                <?php endif; ?>
                <?php foreach ($options as $option): ?>
                    <?php
                    $optionAttributes = array(
                        'value' => $option['value']
                    );
                    if ($defaultValue == $option['value']) {
                        $optionAttributes['selected'] = 'true';
                    }
                    ?>
                    <option <?php echo $this->builtAttributes($optionAttributes); ?>><?php echo $option['label']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    public function renderRadioInput($element, $formId)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }

        $controlClass = $this->elementControlClass($element);
        $inputClass = $this->elementInputClass($element);
        $inputId = 'wpf_input_' . $formId . '_' . $this->elementName;
        $label = ArrayHelper::get($fieldOptions, 'label');
        $defaultValue = ArrayHelper::get($fieldOptions, 'default_value');

        $options = ArrayHelper::get($fieldOptions, 'options', array());
        $inputAttributes = array(
            'data-required' => ArrayHelper::get($fieldOptions, 'required'),
            'name'          => $element['id'],
            'class'         => $inputClass,
            'id'            => $inputId
        );
        if (ArrayHelper::get($fieldOptions, 'required') == 'yes') {
            $inputAttributes['required'] = 'true';
        }
        $controlAttributes = array(
            'id'                => 'wpf_' . $this->elementName,
            'data-element_type' => $this->elementName,
            'class'             => $controlClass
        );
        ?>
        <div <?php echo $this->builtAttributes($controlAttributes); ?>>
            <?php if ($label): ?>
                <label for="<?php echo $inputId; ?>"><?php echo $label; ?></label>
            <?php endif; ?>
            <div class="wpf_multi_form_controls">
                <?php foreach ($options as $index => $option): ?>
                    <?php
                    $optionId = $element['id'] . '_' . $index . '_' . $formId;
                    $attributes = array(
                        'class' => 'form-check-input',
                        'type'  => 'radio',
                        'name'  => $element['id'],
                        'id'    => $optionId,
                        'value' => $option['value']
                    );

                    if ($option['value'] == $defaultValue) {
                        $attributes['checked'] = 'true';
                    }
                    ?>
                    <div class="form-check">
                        <input <?php echo $this->builtAttributes($attributes); ?>>
                        <label class="form-check-label" for="<?php echo $optionId; ?>">
                            <?php echo $option['label']; ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public function renderCheckBoxInput($element, $formId)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }
        $controlClass = $this->elementControlClass($element);
        $inputClass = $this->elementInputClass($element);
        $inputId = 'wpf_input_' . $formId . '_' . $this->elementName;
        $label = ArrayHelper::get($fieldOptions, 'label');
        $defaultValue = ArrayHelper::get($fieldOptions, 'default_value');
        $defaultValues = explode(',', $defaultValue);
        $options = ArrayHelper::get($fieldOptions, 'options', array());
        $inputAttributes = array(
            'data-required' => ArrayHelper::get($fieldOptions, 'required'),
            'name'          => $element['id'],
            'class'         => $inputClass,
            'id'            => $inputId
        );
        if (ArrayHelper::get($fieldOptions, 'required') == 'yes') {
            $inputAttributes['required'] = 'true';
        }
        $controlAttributes = array(
            'id'                => 'wpf_' . $this->elementName,
            'data-element_type' => $this->elementName,
            'class'             => $controlClass
        );
        ?>
        <div <?php echo $this->builtAttributes($controlAttributes); ?>>
            <?php if ($label): ?>
                <label for="<?php echo $inputId; ?>"><?php echo $label; ?></label>
            <?php endif; ?>
            <div class="wpf_multi_form_controls">
                <?php foreach ($options as $index => $option): ?>
                    <?php
                    $optionId = $element['id'] . '_' . $index . '_' . $formId;
                    $attributes = array(
                        'class' => 'form-check-input',
                        'type'  => 'checkbox',
                        'name'  => $element['id'] . '[]',
                        'id'    => $optionId,
                        'value' => ArrayHelper::get($option, 'value')
                    );
                    if (in_array(ArrayHelper::get($option, 'value'), $defaultValues)) {
                        $attributes['checked'] = 'true';
                    }
                    ?>
                    <div class="form-check">
                        <input <?php echo $this->builtAttributes($attributes); ?>>
                        <label class="form-check-label" for="<?php echo $optionId; ?>">
                            <?php echo $option['label']; ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public function renderHtmlContent($element, $formId)
    {
        ?>
            <div class="wpf_html_content_wrapper">
                <?php
                    $text = ArrayHelper::get($element, 'field_options.'.$element['id']);
                    echo $this->parseText($text, $formId);
                ?>
            </div>
        <?php
    }

    public function builtAttributes($attributes)
    {
        $atts = ' ';
        foreach ($attributes as $attributeKey => $attribute) {
            $atts .= $attributeKey . "='" . $attribute . "' ";
        }
        return $atts;
    }

    public function elementControlClass($element)
    {
        return apply_filters('wppayfrom_element_control_class', 'wpf_form_group wpf_item_' . $element['type'], $element);
    }

    public function elementInputClass($element)
    {
        $extraClasses = '';
        if(isset($element['extra_input_class'])) {
            $extraClasses = ' '.$element['extra_input_class'];
        }
        return apply_filters('wppayfrom_element_input_class', 'wpf_form_control'.$extraClasses, $element);
    }

    public function parseText($text, $formId)
    {
        return str_replace(
            array(
                '{sub_total}',
                '{tax_total}',
                '{payment_total}'
            ),
            array(
                '<span class="wpf_calc_sub_total"></span>',
                '<span class="wpf_calc_tax_total"></span>',
                '<span class="wpf_calc_payment_total"></span>',
            ),
            $text
        );
    }

    abstract function component();

    abstract function render($element, $formId, $elements);

}