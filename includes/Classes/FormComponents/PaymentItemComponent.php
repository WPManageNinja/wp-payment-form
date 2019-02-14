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
                    'type'  => 'text'
                ),
                'required'        => array(
                    'label' => 'Required',
                    'type'  => 'switch'
                ),
                'payment_options' => array(
                    'type'                   => 'payment_options',
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
                )
            ),
            'is_system_field'  => true,
            'is_payment_field' => true,
            'field_options'    => array(
                'required'        => 'yes',
                'pricing_details' => array(
                    'one_time_type'       => 'single',
                    'payment_amount'      => '10.00',
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

    public function render($element, $form, $elements)
    {
        $pricingDetails = ArrayHelper::get($element, 'field_options.pricing_details', array());
        if (!$pricingDetails) {
            return;
        }
        $paymentType = ArrayHelper::get($pricingDetails, 'one_time_type');
        if ($paymentType == 'single') {
            $this->renderSingleAmount($element, ArrayHelper::get($pricingDetails, 'payment_amount'));
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

    public function renderSingleAmount($element, $amount = false)
    {
        if ($amount) {
            echo '<input name=' . $element['id'] . ' type="hidden" class="wpf_payment_item" data-price="' . $amount * 100 . '" value="' . $amount . '" />';
        }
    }

    public function renderSingleChoice($type, $prices = array(), $element, $form)
    {
        if (!$type || !$prices) {
            return;
        }

        $currenySettings = Forms::getCurrencyAndLocale($form->ID);
        $elementId = 'wpf_' . $element['id'];
        $controlAttributes = array(
            'id'                => $elementId,
            'data-element_type' => $this->elementName,
            'class'             => $this->elementControlClass($element)
        );
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
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
                        <?php foreach ($prices as $price): ?>
                            <?php
                            $optionAttributes = array(
                                'value'      => $price['label'],
                                'data-price' => $price['value'] * 100,
                            );
                            if ($defaultValue == $price['value']) {
                                $optionAttributes['selected'] = 'true';
                            }
                            ?>
                            <option <?php echo $this->builtAttributes($optionAttributes); ?>><?php echo esc_attr($price['label']); ?>
                                (<?php echo esc_html(wpPayFormFormattedMoney(floatval($price['value']) * 100, $currenySettings)); ?>
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
                            'data-price' => $price['value'] * 100,
                            'name'       => $element['id'],
                            'id'         => $optionId,
                            'value'      => $index
                        );

                        if ($price['value'] == $defaultValue) {
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
                                    class="wpf_price_option_price"><?php echo wpPayFormFormattedMoney(floatval($price['value']) * 100, $currenySettings); ?></span>
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
            'id'                => 'wpf_' . $this->elementName,
            'data-element_type' => $this->elementName,
            'class'             => $controlClass
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
                        'data-price'    => $option['value'] * 100,
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
                                class="wpf_price_option_price"><?php echo wpPayFormFormattedMoney(floatval($option['value'] * 100), $currenySettings); ?></span>
                            <meta itemprop="price" content="<?php echo $option['value']; ?>"/>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}