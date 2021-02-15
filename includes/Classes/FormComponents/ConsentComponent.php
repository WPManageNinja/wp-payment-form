<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

class ConsentComponent extends BaseComponent
{
    protected $valueDefault = '';

    protected $componentName = 'terms_conditions';

    public function __construct()
    {
        $termsValue = __('Agreed', 'wppayform');
        $this->valueDefault = apply_filters('wppayform/terms_value', $termsValue);
        parent::__construct($this->componentName, 600);
    }

    public function component()
    {
        return array(
            'type'            => $this->componentName,
            'editor_title'    => 'Consent/T&C',
            'group'           => 'input',
            'postion_group'   => 'general',
            'editor_elements' => array(
                'label'          => array(
                    'label' => 'Terms Text',
                    'type'  => 'textarea',
                    'group' => 'general',
                    'info'  => 'Provide Terms & Confitions / Consent text (HTML Supported)'
                ),
                'tc_description' => array(
                    'label' => 'Terms Description (optional)',
                    'type'  => 'html',
                    'group' => 'general',
                    'info'  => 'The full description of your terms and condition. It will show as scrolable text'
                ),
                'required'       => array(
                    'label' => 'Required',
                    'type'  => 'switch',
                    'group' => 'general'
                ),
                'default_value'  => array(
                    'label' => 'Default Value',
                    'type'  => 'text',
                    'group' => 'general',
                    'info'  => 'Keep value 1 if you want to make it pre-checked by default'
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
                )
            ),
            'field_options'   => array(
                'label'          => __('I agree with the terms and condition'),
                'required'       => 'yes',
                'wrapper_class'  => '',
                'admin_label'    => 'Terms & Condition Agreement',
                'tc_description' => ''
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        $disable = ArrayHelper::get($fieldOptions, 'disable');

        if (!$fieldOptions || $disable) {
            return;
        }
        $controlClass = $this->elementControlClass($element);
        $inputClass = $this->elementInputClass($element);
        $inputId = 'wpf_input_' . $form->ID . '_' . $element['id'];
        $defaultValue = ArrayHelper::get($fieldOptions, 'default_value');
        $defaultValues = apply_filters('wppayform/input_default_value', $defaultValue, $element, $form);

        $controlAttributes = array(
            'data-element_type'   => $this->elementName,
            'class'               => $controlClass . ' wpf_consent_wrapper',
            'data-target_element' => $element['id']
        );
        if (ArrayHelper::get($fieldOptions, 'required') == 'yes') {
            $controlAttributes['data-checkbox_required'] = 'yes';
        }
        $termDescription = ArrayHelper::get($fieldOptions, 'tc_description');

        ?>
        <div <?php echo $this->builtAttributes($controlAttributes); ?>>
            <div class="wpf_multi_form_controls wpf_input_content">
                <?php
                $optionId = $element['id'] . '_' . $form->ID;
                $attributes = array(
                    'class' => 'form-check-input ' . $inputClass,
                    'type'  => 'checkbox',
                    'name'  => $element['id'] . '[]',
                    'id'    => $optionId,
                    'value' => $this->valueDefault
                );
                if ($defaultValues == '1' || $defaultValues == 1) {
                    $attributes['checked'] = 'true';
                }
                ?>
                <div class="form-check wpf_t_c_checks">
                    <input <?php echo $this->builtAttributes($attributes); ?>>
                    <label class="form-check-label" for="<?php echo $optionId; ?>">
                        <?php echo ArrayHelper::get($fieldOptions, 'label'); ?>
                    </label>
                </div>
                <?php if ($termDescription): ?>
                    <div class="wpf_tc_scroll">
                        <?php echo wp_kses_post($termDescription); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

}