<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class TaxItemComponentDemo extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('tax_payment_input', 6);
    }

    public function component()
    {
        return array(
            'type'            => 'tax_payment_input',
            'editor_title'    => 'Tax Calculated Amount',
            'group'           => 'payment',
            'postion_group'   => 'payment',
            'editor_elements' => array(
                'info' => array(
                    'type' => 'info_html',
                    'info' => '<h3 style="color: firebrick; text-align: center;">Tax Module require Pro version of WPPayForm. Please install Pro version to make it work.</h3><br />'
                ),
                'label'           => array(
                    'label' => 'Field Label',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'tax_percent'     => array(
                    'label' => 'Tax Percentage)',
                    'type'  => 'number',
                    'group' => 'general'
                ),
                'target_product' => array(
                    'label' => 'Target Product Item',
                    'type'  => 'product_selector',
                    'group' => 'general',
                    'info'  => 'Please select the product in where this tax percentage will be applied'
                )
            ),
            'field_options'   => array(
                'label' => 'Tax Amount:',
                'tax_percent' => '10'
            )
        );
    }

    public function render($element, $form, $elements)
    {
        return;
    }
}