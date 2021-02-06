<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\Models\Forms;
use WPPayForm\Pro\Classes\Discounts\discounts;

if (!defined('ABSPATH')) {
    exit;
}

class DiscountComponent extends BaseComponent
{
    private $discounts;
    public function __construct()
    {
        parent::__construct('discount', 20);
        add_action('wp_ajax_wpf_discount_submit', array($this, 'handleDiscount'));
        add_action('wp_ajax_nopriv_wpf_discount_submit', array($this, 'handleDiscount'));
    }

    public function component()
    {
        return array(
            'type'            => 'discount',
            'editor_title'    => 'Discount',
            'group'           => 'input',
            'postion_group'   => 'payment',
            'is_system_field'  => false,
            'is_payment_field' => false,
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
        add_filter('wppayform/form_css_classes', function ($classes, $reneringForm) use ($form) {
            if ($reneringForm->ID == $form->ID) {
                $classes[] = 'wpf_has_discounts';
            }
            return $classes;
        }, 10, 2);

        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }

        $html =  ArrayHelper::get($element, 'field_options.custom_html', '');
        $controlClass = $this->elementControlClass($element);
        $inputClass = $this->elementInputClass($element);
        $inputId = 'wpf_input_' . $form->ID . '_' . $element['id'];
        $attributes = array(
            'data-type'        => 'input',
            'name'             => $element['id'],
            'placeholder'      => ArrayHelper::get($fieldOptions, 'placeholder'),
            'type'             => 'text',
            'id'               => $inputId,
            'class'            => $inputClass . ' wpf_discount_field',
        );

        // $this->insertScipt($fieldOptions, $attributes, $form->ID);

        if (ArrayHelper::get($fieldOptions, 'required') == 'yes') {
            $attributes['required'] = true;
        }
        ?>
        <style type="text/css">
            .wpf_discount_action {
                cursor: pointer;
                margin-right: -3px;
            }
        </style>

        <div data-element_type="<?php echo $this->elementName; ?>"
             class="<?php echo $controlClass; ?>">
            <?php $this->buildLabel($fieldOptions, $form, array('for' => $inputId)); ?>
            <div class="wpf_input_content">
                <div class="wpf_form_item_group">
                    <div class="wpf_input-group-prepend">
                        <div class="wpf_input-group-text wpf_discount_action" id="<?php echo $inputId . '_action'?>">Apply</div>
                    </div>
                    <input <?php echo $this->builtAttributes($attributes); ?> />
                </div>
            </div>
        </div>
        <?php
    }

    public function handleDiscount()
    {
        (new Discounts())->validate();
    }
}