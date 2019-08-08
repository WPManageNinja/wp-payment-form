<?php

namespace WPPayForm\Classes\FormComponents;

if (!defined('ABSPATH')) {
    exit;
}

class DemoTabularProductsComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('tabular_products', 2);
    }

    public function component()
    {
        return array(
            'type'             => 'tabular_products',
            'editor_title'     => 'Tabular Products (Pro)',
            'group'            => 'payment',
            'postion_group'    => 'payment',
            'disabled' => true,
            'disabled_message' => 'Tabular Products Module requires Pro version of WPPayForm. Please install Pro version to make it work.',
            'editor_elements'  => array(
                'info' => array(
                    'type' => 'info_html',
                    'info' => '<h3 style="color: firebrick; text-align: center;">Tabular Products Module require Pro version of WPPayForm. Please install Pro version to make it work.</h3><br />'
                ),
                'label'                => array(
                    'label' => 'Field Label',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'products'             => array(
                    'label' => 'Setup your Tabular products',
                    'group' => 'general',
                    'type'  => 'tabular_products',
                ),
                'show_sub_total'       => array(
                    'label' => 'Show Subtotal',
                    'type'  => 'switch',
                    'group' => 'general',
                    'info'  => 'If enabled then user can see subtotal after the table'
                ),
                'table_item_label'     => array(
                    'label' => 'Table Item Column Label',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'table_price_label'    => array(
                    'label' => 'Table Price Column Label',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'table_quantity_label' => array(
                    'label' => 'Table Quantity Column Label',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'table_subtotal_label' => array(
                    'label' => 'Table Sub Total Label Label',
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
            'is_system_field'  => true,
            'is_payment_field' => true,
            'field_options'    => array(
                'label'                => 'Add quantity of the products',
                'show_sub_total'       => 'yes',
                'table_item_label'     => 'Product',
                'table_price_label'    => 'Item Price',
                'table_quantity_label' => 'Quantity',
                'table_subtotal_label' => 'Sub Total',
                'products'             => array(
                    [
                        'product_name'     => 'Product 1',
                        'default_quantity' => 1,
                        'min_quantity'     => 0,
                        'product_price'    => '10'
                    ],
                    [
                        'product_name'     => 'Product 2',
                        'default_quantity' => 0,
                        'min_quantity'     => 0,
                        'product_price'    => '20'
                    ]
                )
            )
        );
    }


    public function render($element, $form, $elements)
    {
        return '';
    }

}