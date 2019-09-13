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
        add_action('wppayform/payment_method_choose_element_render_stripe', array($this, 'renderForMultiple'), 10, 3);
        add_filter('wppayform/available_payment_methods', array($this, 'pushPaymentMethod'), 1, 1);
    }

    public function pushPaymentMethod($methods)
    {
        $methods['stripe'] = array(
            'label'           => 'Credit/Debit Card (Stripe)',
            'isActive'        => true,
            'editor_elements' => array(
                'label'                  => array(
                    'label'   => 'Payment Option Label',
                    'type'    => 'text',
                    'default' => 'Pay with Card (Stripe)'
                ),
                'checkout_display_style' => array(
                    'label'   => 'Checkout display style',
                    'type'    => 'checkout_display_options',
                    'default' => array(
                        'style' => 'stripe_checkout'
                    )
                ),
                'verify_zip'             => array(
                    'label' => 'Verify Zip/Postal Code',
                    'type'  => 'switch'
                ),
            )
        );
        return $methods;
    }

    public function component()
    {
        return array(
            'type'            => 'stripe_card_element',
            'editor_title'    => 'Card Elements (Stripe)',
            'editor_icon'     => '',
            'group'           => 'payment_method_element',
            'method_handler'  => 'stripe',
            'postion_group'   => 'payment_method',
            'single_only'     => true,
            'editor_elements' => array(
                'label'                  => array(
                    'label' => 'Field Label',
                    'type'  => 'text'
                ),
                'checkout_display_style' => array(
                    'label' => 'Checkout display style',
                    'type'  => 'checkout_display_options'
                ),
                'verify_zip'             => array(
                    'label' => 'Verify Zip/Postal Code',
                    'type'  => 'switch'
                ),
            ),
            'field_options'   => array(
                'label'                  => __('Your Card info (Powered By Stripe)', 'wppayform'),
                'verify_zip'             => 'no',
                'checkout_display_style' => array(
                    'style'                 => 'stripe_checkout',
                    'require_billing_info'  => 'no',
                )
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $stripe = new Stripe();
        if (!$stripe->getPubKey()) { ?>
            <p style="color: red">You did not configure stripe payment gateway. Please configure stripe payment
                gateway from <b>WPPayFroms->Settings->Stripe Settings</b> to start accepting payments</p>
            <?php return;
        }
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }
        $checkOutStyle = ArrayHelper::get($fieldOptions, 'checkout_display_style.style', 'stripe_checkout');

        $inputId = 'wpf_input_' . $form->ID . '_' . $this->elementName;
        add_filter('wppayform/checkout_vars', function ($vars) use ($checkOutStyle, $form, $fieldOptions, $inputId) {
            if($vars['form_id'] == $form->ID) {
                $vars['stripe_checkout_style'] = $checkOutStyle;
                $vars['stripe_verify_zip'] = ArrayHelper::get($fieldOptions, 'verify_zip');
                $vars['stripe_billing_info']  = ArrayHelper::get($fieldOptions, 'checkout_display_style.require_billing_info');
                $vars['stripe_shipping_info'] = ArrayHelper::get($fieldOptions, 'checkout_display_style.require_shipping_info');
                $vars['stripe_element_id'] = $inputId;
            }
            return $vars;
        });

        wp_enqueue_script('stripe_elements', 'https://js.stripe.com/v3/', array('jquery'), '3.0', true);

        if ($checkOutStyle == 'stripe_checkout') {
            $atrributes = array(
                'data-checkout_style'        => $checkOutStyle,
                'data-wpf_payment_method'    => 'stripe',
                'class'                      => 'wpf_stripe_card_element',
                'data-verify_zip'            => ArrayHelper::get($fieldOptions, 'verify_zip'),
                'data-require_billing_info'  => ArrayHelper::get($fieldOptions, 'checkout_display_style.require_billing_info'),
                'data-require_shipping_info' => ArrayHelper::get($fieldOptions, 'checkout_display_style.require_shipping_info')
            );
            echo '<div style="display:none !important; visibility: hidden !important;" ' . $this->builtAttributes($atrributes) . ' class="wpf_stripe_checkout"></div>';
            return;
        }


        $inputClass = $this->elementInputClass($element);
        $label = ArrayHelper::get($fieldOptions, 'label');
        $attributes = array(
            'data-checkout_style'     => $checkOutStyle,
            'data-wpf_payment_method' => 'stripe',
            'name'                    => $element['id'],
            'class'                   => 'wpf_stripe_card_element ' . $inputClass,
            'data-verify_zip'         => ArrayHelper::get($fieldOptions, 'verify_zip'),
            'id'                      => $inputId
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
            <?php if ($stripe->getMode() == 'test') { ?>
                <p class="wpf_test_mode_message" style="margin: 0;padding: 0;font-style: italic;">Stripe test mode
                    activated</p>
            <?php } ?>
        </div>
        <?php
    }

    public function renderForMultiple($paymentSettings, $form, $elements)
    {
        $component = $this->component();
        $component['id'] = 'stripe_card_element';
        $component['field_options'] = $paymentSettings;
        $this->render($component, $form, $elements);
    }
}