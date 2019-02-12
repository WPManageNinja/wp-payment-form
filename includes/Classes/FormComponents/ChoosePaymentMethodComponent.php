<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

class ChoosePaymentMethodComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('choose_payment_method', 4);
    }

    public function component()
    {
        $available_methods = array(
            'stripe' => array(
                'label'           => 'Stripe Payment Method',
                'isActive'        => true,
                'editor_elements' => array(
                    'label'                  => array(
                        'label' => 'Payment Option Label',
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
                )
            ),
            'paypal' => array(
                'label'           => 'Paypal Payment Method',
                'isActive'        => true,
                'editor_elements' => array(
                    'label'                    => array(
                        'label' => 'Field Label',
                        'type'  => 'text'
                    ),
                    'require_shipping_address' => array(
                        'label' => 'Require Shipping Address',
                        'type'  => 'switch'
                    )
                )
            )
        );

        return array(
            'type'            => 'choose_payment_method',
            'editor_title'    => 'Choose Payment Method Field',
            'group'           => 'payment_method_element',
            'postion_group'   => 'payment_method',
            'single_only'     => true,
            'editor_elements' => array(
                'label'           => array(
                    'label' => 'Field Label',
                    'type'  => 'text'
                ),
                'method_settings' => array(
                    'label'             => 'Payment Methods',
                    'type'              => 'choose_payment_method',
                    'available_methods' => $available_methods
                )
            ),
            'field_options'   => array(
                'label'           => __('Select Payment Method', 'wppayform'),
                'method_settings' => array(
                    'choosed_methods' => array('stripe'),
                    'prefered_method' => ''
                )
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $fieldOption = ArrayHelper::get($element, 'field_options');

        $controlAttributes = array(
            'id'                => 'wpf_' . $this->elementName,
            'data-element_type' => $this->elementName,
            'class'             => $this->elementControlClass($element)
        );
        $methods = ArrayHelper::get($fieldOption, 'method_settings.payment_settings', array());
        $validMethods = array();
        $defaultValue = 'stripex';
        foreach ($methods as $methodName => $method) {
            if (isset($method['enabled']) && $method['enabled'] == 'yes') {
                $validMethods[$methodName] = $method;
            }
        }
        ?>
        <div <?php echo $this->builtAttributes($controlAttributes); ?>>
            <?php $this->buildLabel($fieldOption, $form); ?>
            <div class="wpf_multi_form_controls wpf_input_content">
                <?php foreach ($validMethods as $methodName => $method): ?>
                    <?php
                    $optionId = $element['id'] . '_' . $methodName . '_' . $form->ID;
                    $attributes = array(
                        'class' => 'form-check-input',
                        'type'  => 'radio',
                        'name'  => '__wpf_selected_payment_method',
                        'id'    => $optionId,
                        'value' => $methodName,
                        'required' => true
                    );
                    if ($methodName == $defaultValue) {
                        $attributes['checked'] = 'true';
                    }
                    ?>
                    <div class="form-check">
                        <input <?php echo $this->builtAttributes($attributes); ?>>
                        <label class="form-check-label" for="<?php echo $optionId; ?>">
                            <?php echo $method['label']; ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}