<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

class StripeCardElementComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('stripe_card_element', 6);
    }

    public function component()
    {
        return array(
            'type'            => 'stripe_card_element',
            'editor_title'    => 'Card Elements (Stripe)',
            'group'           => 'card_element',
            'editor_elements' => array(
                'label'      => array(
                    'label' => 'Field Label',
                    'type'  => 'text'
                ),
                'verify_zip' => array(
                    'label' => 'Verify Zip/Postal Code',
                    'type'  => 'switch'
                ),
            ),
            'field_options'   => array(
                'label'      => '',
                'verify_zip' => 'no'
            )
        );
    }

    public function render($element, $formId, $elements)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }

        wp_enqueue_script('stripe', 'https://js.stripe.com/v3/', array('jquery'), '3.0', true);
        $inputClass = $this->elementInputClass($element);
        $inputId = 'wpf_input_' . $formId . '_' . $this->elementName;
        $label = ArrayHelper::get($fieldOptions, 'label');
        $attributes = array(
            'name'       => $element['id'],
            'class'      => 'wpf_stripe_card_element ' . $inputClass,
            'data-verify_zip' => ArrayHelper::get($fieldOptions, 'verify_zip'),
            'id'         => $inputId
        );
        ?>
        <div class="wpf_form_group wpf_item_<?php echo $element['id']; ?>>">
            <?php if ($label): ?>
                <label for="<?php echo $inputId; ?>">
                    <?php echo $label; ?>
                </label>
            <?php endif; ?>
            <div <?php echo $this->builtAttributes($attributes); ?>></div>
            <div id="card-errors" class="wpf_card-errors" role="alert"></div>
        </div>
        <?php
    }
}