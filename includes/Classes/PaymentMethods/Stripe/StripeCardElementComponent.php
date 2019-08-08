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
                    'require_shipping_info' => 'no'
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
        wp_enqueue_script('stripe_elements', 'https://js.stripe.com/v3/', array('jquery'), '3.0', true);
        if ($stripe->getMode() == 'test') {
            echo '<p class="wpf_test_mode_message" style="margin: 10px 0px;padding: 0;font-style: italic;">Stripe test mode activated</p>';
        }
    }

    public function renderForMultiple($paymentSettings, $form, $elements)
    {
        $component = $this->component();
        $component['id'] = 'stripe_card_element';
        $component['field_options'] = $paymentSettings;
        $this->render($component, $form, $elements);
    }
}