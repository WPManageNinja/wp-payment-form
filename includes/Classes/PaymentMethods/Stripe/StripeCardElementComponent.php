<?php

namespace WPPayForm\Classes\PaymentMethods\Stripe;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\FormComponents\BaseComponent;

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
            'group'           => 'payment_method_element',
            'single_only'     => true,
            'editor_elements' => array(
                'label'      => array(
                    'label' => 'Field Label',
                    'type'  => 'text'
                ),
                'verify_zip' => array(
                    'label' => 'Verify Zip/Postal Code',
                    'type'  => 'switch'
                ),
                'checkout_display_style' => array(
                    'label' => 'Checkout display style',
                    'type' => 'checkout_display_options'
                )
            ),
            'field_options'   => array(
                'label'      => '',
                'verify_zip' => 'no',
                'checkout_display_style' => array(
                    'style' => 'stripe_checkout',
                    'require_billing_info' => 'no',
                    'require_shipping_info' => 'no'
                )
            )
        );
    }

    public function render($element, $formId, $elements)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }

        $checkOutStyle = ArrayHelper::get($fieldOptions, 'checkout_display_style.style', 'stripe_checkout');
        if($checkOutStyle == 'stripe_checkout') {
            wp_enqueue_script('stripe_checkout', 'https://checkout.stripe.com/checkout.js', array('jquery'), '3.0', true);
            $atrributes = array(
                'data-checkout_style' => $checkOutStyle,
                'class' => 'wpf_stripe_card_element',
                'data-verify_zip' => ArrayHelper::get($fieldOptions, 'verify_zip'),
                'data-require_billing_info' => ArrayHelper::get($fieldOptions,'checkout_display_style.require_billing_info'),
                'data-require_shipping_info' => ArrayHelper::get($fieldOptions,'checkout_display_style.require_shipping_info')
            );
            echo '<div style="display:none !important; visibility: hidden !important;" '.$this->builtAttributes($atrributes).' class="wpf_stripe_checkout"></div>';
            return;
        } else {
            wp_enqueue_script('stripe_elements', 'https://js.stripe.com/v3/', array('jquery'), '3.0', true);
        }

        $inputClass = $this->elementInputClass($element);
        $inputId = 'wpf_input_' . $formId . '_' . $this->elementName;
        $label = ArrayHelper::get($fieldOptions, 'label');
        $attributes = array(
            'data-checkout_style' => $checkOutStyle,
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
            <div class="wpf_card-errors" role="alert"></div>
        </div>
        <?php
    }
}