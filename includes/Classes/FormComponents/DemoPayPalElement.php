<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\FormComponents\BaseComponent;

if (!defined('ABSPATH')) {
    exit;
}

class DemoPayPalElement extends BaseComponent
{
    public $gateWayName = 'paypal';

    public function __construct()
    {
        parent::__construct('paypal_gateway_element', 7);
    }


    public function component()
    {
        return array(
            'type'            => 'paypal_gateway_element',
            'editor_title'    => 'Paypal payment gateway (Pro)',
            'editor_icon'     => '',
            'disabled'        => true,
            'disabled_message' => 'PayPal Payment Method requires Pro version of WPPayForm. Please install Pro version to make it work.',
            'group'           => 'payment_method_element',
            'method_handler'  => $this->gateWayName,
            'postion_group'   => 'payment_method',
            'single_only'     => true,
            'editor_elements' => array(
                'info'                     => array(
                    'type' => 'info_html',
                    'info' => '<h3 style="color: firebrick; text-align: center;">PayPal Payment Method require Pro version of WPPayForm. Please install Pro version to make it work.</h3><br />'
                ),
                'label'                    => array(
                    'label' => 'Field Label',
                    'type'  => 'text'
                ),
                'require_shipping_address' => array(
                    'label' => 'Require Shipping Address',
                    'type'  => 'switch'
                ),
                'admin_label' => array(
                    'label' => 'Admin Label',
                    'type'  => 'text',
                    'group' => 'advanced'
                ),
                'wrapper_class' => array(
                    'label' => 'Field Wrapper CSS Class',
                    'type'  => 'text',
                    'group' => 'advanced'
                ),
                'element_class' => array(
                    'label' => 'Input element CSS Class',
                    'type'  => 'text',
                    'group' => 'advanced'
                ),
            ),
            'field_options'   => array(
                'label'                    => __('PayPal Payment Gateway', 'wppayform'),
                'require_shipping_address' => 'no'
            )
        );
    }

    public function render($element, $form, $elements)
    {
        return '';
    }
}