<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

class DateComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('date', 19);
    }

    public function component()
    {
        $dateFormats = apply_filters('wpf_available_date_formats', array(
            'M/D/YYYY'    => 'M/D/YYYY - (Ex: 4/28/2019)',
            'M/D/YY'      => 'M/D/YY - (Ex: 4/28/18)',
            'MM/DD/YY'    => 'MM/DD/YY - (Ex: 04/28/18)',
            'MM/DD/YYYY'  => 'MM/DD/YYYY - (Ex: 04/28/2018)',
            'MMM/DD/YYYY' => 'MMM/DD/YYYY - (Ex: Apr/28/2018)',
            'YY/MM/DD'    => 'YY/MM/DD - (Ex: 18/04/28)',
            'YYYY-MM-DD'  => 'YYYY-MM-DD - (Ex: 2018-04-28)',
            'DD-MMM-YY'   => 'DD-MMM-YY - (Ex: 28-Apr-18)'
        ));
        return array(
            'type'            => 'date',
            'editor_title'    => 'Date Field',
            'group'           => 'input',
            'editor_elements' => array(
                'label'         => array(
                    'label' => 'Field Label',
                    'type'  => 'text'
                ),
                'placeholder'         => array(
                    'label' => 'Placeholder',
                    'type'  => 'text'
                ),
                'required'      => array(
                    'label' => 'Required',
                    'type'  => 'switch'
                ),
                'date_format'   => array(
                    'label'   => 'Date Format',
                    'type'    => 'select_option',
                    'options' => $dateFormats
                ),
                'default_value' => array(
                    'label' => 'Default Value',
                    'type'  => 'text'
                )
            ),
            'field_options'   => array(
                'label'       => '',
                'placeholder' => '',
                'required'    => 'no'
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
            'type' => 'text',
            'class' => $inputClass.' wpf_date_field',
            'data-date_format' =>  ArrayHelper::get($fieldOptions, 'date_format'),
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