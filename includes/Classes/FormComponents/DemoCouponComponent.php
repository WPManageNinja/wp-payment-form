<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class DemoCouponComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('coupon', 20);
    }

    public function component()
    {
        return array(
            'type'            => 'coupon',
            'editor_title'    => 'Coupon (Pro)',
            'group'           => 'payment',
            'postion_group'   => 'payment',
            'is_system_field' => false,
            'is_payment_field'=> false,
            'disabled'        => true,
            'disabled_message'=> 'Coupon Module requires Pro version of WPPayForm. Please install Pro version to make it work.',
            'editor_elements' => array(
                'label'         => array(
                    'label' => 'Field Label',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'placeholder'   => array(
                    'label' => 'Placeholder',
                    'type'  => 'text',
                    'group' => 'general'
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
                'label' => 'Coupon Code',
                'placeholder' => '',
                'required' => 'no'
            )
        );
    }

    public function render($element, $form, $elements)
    {
        return;
    }
}
