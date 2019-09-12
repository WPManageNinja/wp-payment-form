<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class PaymentItemComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('payment_item', 1);
        add_filter('wppayform/validate_component_on_save_payment_item', array($this, 'validateOnSave'), 1, 3);
    }

    public function component()
    {
        return array(
            'type'             => 'payment_item',
            'editor_title'     => 'Payment Item',
            'group'            => 'payment',
            'postion_group'    => 'payment',
            'editor_elements'  => array(
                'label'           => array(
                    'label' => 'Field Label',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'required'        => array(
                    'label' => 'Required',
                    'type'  => 'switch',
                    'group' => 'general'
                ),
                'payment_options' => array(
                    'type'                   => 'payment_options',
                    'group'                  => 'general',
                    'label'                  => 'Configure Payment Item',
                    'selection_type'         => 'Payment Type',
                    'selection_type_options' => array(
                        'one_time'        => 'One Time Payment',
                        'one_time_custom' => 'One Time Custom Amount'
                    ),
                    'one_time_field_options' => array(
                        'single'          => 'Single Item',
                        'choose_single'   => 'Chose One From Multiple Item',
                        'choose_multiple' => 'Choose Multiple Items'
                    )
                ),
                'admin_label'     => array(
                    'label' => 'Admin Label',
                    'type'  => 'text',
                    'group' => 'advanced'
                ),
                'wrapper_class'   => array(
                    'label' => 'Field Wrapper CSS Class',
                    'type'  => 'text',
                    'group' => 'advanced'
                )
            ),
            'is_system_field'  => true,
            'is_payment_field' => true,
            'field_options'    => array(
                'label'           => 'Payment Item',
                'required'        => 'yes',
                'pricing_details' => array(
                    'one_time_type'       => 'single',
                    'payment_amount'      => '10.00',
                    'show_onetime_labels' => 'yes',
                    'multiple_pricing'    => array(
                        array(
                            'label' => '',
                            'value' => ''
                        )
                    ),
                    'prices_display_type' => 'radio'
                )
            )
        );
    }

    public function validateOnSave($error, $element, $formId)
    {
        $pricingDetails = ArrayHelper::get($element, 'field_options.pricing_details', array());
        $paymentType = ArrayHelper::get($pricingDetails, 'one_time_type');
        if ($paymentType == 'single') {
            if (!ArrayHelper::get($pricingDetails, 'payment_amount')) {
                $error = __('Payment amount is required for item:', 'wppayform') . ' ' . ArrayHelper::get($element, 'field_options.label');
            }
        } else if ($paymentType == 'choose_multiple' || $paymentType == 'choose_single') {
            if (!count(ArrayHelper::get($pricingDetails, 'multiple_pricing', array()))) {
                $error = __('Pricing Details is required for item:', 'wppayform') . ' ' . ArrayHelper::get($element, 'field_options.label');
            }
        }
        return $error;
    }

    public function render($element, $form, $elements)
    {
        $pricingDetails = ArrayHelper::get($element, 'field_options.pricing_details', array());
        if (!$pricingDetails) {
            return;
        }

        $element['field_options']['default_value'] = apply_filters('wppayform/input_default_value', ArrayHelper::get($element['field_options'], 'default_value'), $element, $form);

        $paymentType = ArrayHelper::get($pricingDetails, 'one_time_type');
        if ($paymentType == 'single') {
            $this->renderSingleAmount($element, $form, ArrayHelper::get($pricingDetails, 'payment_amount'));
            return;
        } else if ($paymentType == 'choose_single') {
            $displayType = ArrayHelper::get($pricingDetails, 'prices_display_type', 'radio');
            $this->renderSingleChoice(
                $displayType,
                ArrayHelper::get($pricingDetails, 'multiple_pricing', array()),
                $element,
                $form
            );
        } else if ($paymentType == 'choose_multiple') {
            $this->chooseMultipleChoice(
                ArrayHelper::get($pricingDetails, 'multiple_pricing', array()),
                $element,
                $form
            );
        }
    }

    public function renderSingleAmount($element, $form, $amount = false)
    {
        $showTitle = ArrayHelper::get($element, 'field_options.pricing_details.show_onetime_labels') == 'yes';
        if ($showTitle) {
            $title = ArrayHelper::get($element, 'field_options.label');
            $currenySettings = Forms::getCurrencyAndLocale($form->ID);
            $controlAttributes = array(
                'data-element_type' => $this->elementName,
                'class'             => $this->elementControlClass($element)
            );
            ?>
            <div <?php echo $this->builtAttributes($controlAttributes); ?>>
                <div class="wpf_input_label wpf_single_amount_label">
                    <?php echo $title ?>: <span
                        class="wpf_single_amount"><?php echo wpPayFormFormattedMoney(wpPayFormConverToCents($amount), $currenySettings); ?></span>
                </div>
            </div>
            <?php
        }
        echo '<input name=' . $element['id'] . ' type="hidden" class="wpf_payment_item" data-price="' . wpPayFormConverToCents($amount) . '" value="' . $amount . '" />';
    }

    public function renderSingleChoice($type, $prices = array(), $element, $form)
    {
        if (!$type || !$prices) {
            return;
        }
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        $currenySettings = Forms::getCurrencyAndLocale($form->ID);
        $elementId = 'wpf_' . $element['id'];
        $controlAttributes = array(
            'data-element_type'     => $this->elementName,
            'data-required_element' => $type,
            'data-required'         => ArrayHelper::get($fieldOptions, 'required'),
            'data-target_element'   => $element['id'],
            'class'                 => $this->elementControlClass($element)
        );
        $defaultValue = ArrayHelper::get($fieldOptions, 'default_value');
        ?>
        <div <?php echo $this->builtAttributes($controlAttributes); ?>>
            <?php $this->buildLabel($fieldOptions, $form, array('for' => $elementId)); ?>
            <?php if ($type == 'select') : ?>
                <?php
                $placeholder = '--Select--';
                $inputId = 'wpf_input_' . $form->ID . '_' . $this->elementName;
                $inputAttributes = array(
                    'data-required' => ArrayHelper::get($fieldOptions, 'required'),
                    'data-type'     => 'select',
                    'name'          => $element['id'],
                    'class'         => $this->elementInputClass($element) . ' wpf_payment_item',
                    'id'            => $inputId
                );
                ?>
                <div class="wpf_multi_form_controls wpf_input_content wpf_multi_form_controls_select">
                    <select <?php echo $this->builtAttributes($inputAttributes); ?>>
                        <?php if ($placeholder): ?>
                            <option data-type="placeholder" value=""><?php echo $placeholder; ?></option>
                        <?php endif; ?>
                        <?php foreach ($prices as $index => $price): ?>
                            <?php
                            $optionAttributes = array(
                                'value'      => $index,
                                'data-price' => wpPayFormConverToCents($price['value']),
                            );
                            if ($defaultValue == $price['label']) {
                                $optionAttributes['selected'] = 'true';
                            }
                            ?>
                            <option <?php echo $this->builtAttributes($optionAttributes); ?>><?php echo esc_attr($price['label']); ?>
                                (<?php echo esc_html(wpPayFormFormattedMoney(wpPayFormConverToCents($price['value']), $currenySettings)); ?>
                                )
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <div class="wpf_multi_form_controls wpf_input_content wpf_multi_form_controls_radio">
                    <?php foreach ($prices as $index => $price): ?>
                        <?php
                        $optionId = $element['id'] . '_' . $index . '_' . $form->ID;
                        $attributes = array(
                            'class'      => 'form-check-input wpf_payment_item',
                            'type'       => 'radio',
                            'data-price' => wpPayFormConverToCents($price['value']),
                            'name'       => $element['id'],
                            'id'         => $optionId,
                            'value'      => $index
                        );

                        if ($price['label'] == $defaultValue) {
                            $attributes['checked'] = 'true';
                        }
                        ?>
                        <div class="form-check">
                            <input <?php echo $this->builtAttributes($attributes); ?>>
                            <label class="form-check-label" for="<?php echo $optionId; ?>">
                                <span class="wpf_price_option_name"
                                      itemprop="description"><?php echo $price['label']; ?></span>
                                <span class="wpf_price_option_sep">&nbsp;–&nbsp;</span>
                                <span
                                    class="wpf_price_option_price"><?php echo wpPayFormFormattedMoney(wpPayFormConverToCents($price['value']), $currenySettings); ?></span>
                                <meta itemprop="price" content="<?php echo $price['value']; ?>">
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function chooseMultipleChoice($prices = array(), $element, $form)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }
        $currenySettings = Forms::getCurrencyAndLocale($form->ID);
        $controlClass = $this->elementControlClass($element);
        $inputId = 'wpf_input_' . $form->ID . '_' . $this->elementName;
        $defaultValue = ArrayHelper::get($fieldOptions, 'default_value');
        $defaultValues = explode(',', $defaultValue);

        $controlAttributes = array(
            'data-element_type'      => $this->elementName,
            'class'                  => $controlClass,
            'data-checkbox_required' => ArrayHelper::get($fieldOptions, 'required'),
            'data-element_type'      => 'checkbox',
            'data-target_element'    => $element['id']
        );
        ?>
        <div <?php echo $this->builtAttributes($controlAttributes); ?>>
            <?php $this->buildLabel($fieldOptions, $form, array('for' => $inputId)); ?>

            <?php
            $itemParentAtrributes = array(
                'class'                   => 'wpf_multi_form_controls wpf_input_content',
                'data-item_required'      => ArrayHelper::get($fieldOptions, 'required'),
                'data-item_selector'      => 'checkbox',
                'data-has_multiple_input' => 'yes'
            );
            ?>

            <div <?php echo $this->builtAttributes($itemParentAtrributes); ?>>
                <?php foreach ($prices as $index => $option): ?>
                    <?php
                    $optionId = $element['id'] . '_' . $index . '_' . $form->ID;
                    $attributes = array(
                        'class'         => 'form-check-input wpf_payment_item',
                        'type'          => 'checkbox',
                        'data-price'    => wpPayFormConverToCents($option['value']),
                        'name'          => $element['id'] . '[' . $index . ']',
                        'id'            => $optionId,
                        'data-group_id' => $element['id'],
                        'value'         => $option['label']
                    );
                    if (in_array($option['value'], $defaultValues)) {
                        $attributes['checked'] = 'true';
                    }
                    ?>
                    <div class="form-check">
                        <input <?php echo $this->builtAttributes($attributes); ?>>
                        <label class="form-check-label" for="<?php echo $optionId; ?>">
                            <span class="wpf_price_option_name"
                                  itemprop="description"><?php echo $option['label']; ?></span>
                            <span class="wpf_price_option_sep">&nbsp;–&nbsp;</span>
                            <span
                                class="wpf_price_option_price"><?php echo wpPayFormFormattedMoney(wpPayFormConverToCents($option['value']), $currenySettings); ?></span>
                            <meta itemprop="price" content="<?php echo $option['value']; ?>"/>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}