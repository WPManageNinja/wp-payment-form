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
            'editor_elements' => array(
                'label'         => array(
                    'label' => 'Field Label',
                    'type'  => 'text'
                ),
                'required'      => array(
                    'label' => 'Required',
                    'type'  => 'switch'
                ),
                'default_value' => array(
                    'label' => 'Default Value',
                    'type'  => 'textarea'
                )
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
            'class' => $inputClass
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

            <textarea <?php echo $this->builtAttributes($attributes); ?>><?php echo ArrayHelper::get($fieldOptions, 'default_value') ?></textarea>
        </div>
        <?php
    }

}