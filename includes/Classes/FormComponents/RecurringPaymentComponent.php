<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class RecurringPaymentComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('recurring_payment_item', 1);
    }

    public function component()
    {
        return array(
            'type'             => 'recurring_payment_item',
            'editor_title'     => __('Recurring Payment Item', 'wppayform'),
            'group'            => 'payment',
            'postion_group'    => 'payment',
            'disabled' => !defined('WPPAYFORM_PRO_INSTALLED'),
            'disabled_message' => 'Recurring Subscription Payment requires Pro Pro version of WPPayForm. Please install Pro version to make it work.',
            'editor_elements'  => array(
                'label'                     => array(
                    'label' => 'Recurring Payment Item Name',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'required'                  => array(
                    'label' => 'Required',
                    'type'  => 'switch',
                    'group' => 'general'
                ),
                'show_main_label'           => array(
                    'label' => 'Show Pricing Label',
                    'type'  => 'switch',
                    'group' => 'general'
                ),
                'show_payment_summary'      => array(
                    'label' => 'Show Payment Summary',
                    'type'  => 'switch',
                    'group' => 'general'
                ),
                'recurring_payment_options' => array(
                    'type'         => 'recurring_payment_options',
                    'group'        => 'general',
                    'label'        => 'Configure Recurring Subscription Payment Plans',
                    'choice_label' => __('Choose your pricing plan'),
                    'choice_types' => array(
                        'simple'          => __('Simple Recurring Plan (Single)', 'payform'),
                        'choose_single'   => __('Chose One from Multiple Pricing Plans', 'payform'),
                        //'choose_multiple' => __('Choose Multiple Plan from Pricing Plans', 'payform')
                    ),
                    'selection_types' => array(
                        'radio' => __('Radio input field', 'payform'),
                        'select' => __('Select input field', 'payform')
                    )
                )
            ),
            'is_system_field'  => true,
            'is_payment_field' => true,
            'field_options'    => array(
                'label'                     => __('Subscription Item', 'payform'),
                'required'                  => 'yes',
                'show_main_label'           => 'yes',
                'show_payment_summary'      => 'yes',
                'recurring_payment_options' => array(
                    'choice_type'     => 'simple',
                    'selection_type'  => 'radio',
                    'pricing_options' => [
                        [
                            'name'                => __('$9.99 / Month', 'payform'),
                            'trial_days'          => 0,
                            'has_trial_days'      => 'no',
                            'trial_days'          => 0,
                            'billing_interval'    => 'month',
                            'bill_times'          => 0,
                            'has_signup_fee'      => 'no',
                            'signup_fee'          => 0,
                            'subscription_amount' => '9.99',
                            'is_default'          => 'yes',
                            'plan_features' => []
                        ]
                    ]
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
        $fieldOptions = ArrayHelper::get($element, 'field_options', array());
        $paymentOptions = ArrayHelper::get($fieldOptions, 'recurring_payment_options', array());
        if (!$paymentOptions) {
            return;
        }
        $choiceType = ArrayHelper::get($paymentOptions, 'choice_type', 'simple');
        $pricingPlans = ArrayHelper::get($paymentOptions, 'pricing_options');
        if (count($pricingPlans) == 0) {
            return;
        }
        if ($choiceType == 'simple') {
            $this->renderSimplePlan($element, $fieldOptions, $pricingPlans, $form);
            return;
        } else if ($choiceType == 'choose_single') {
            $this->renderSingleChoice($element, $fieldOptions, $pricingPlans, $form);
            // return;
        }
//        echo '<pre>';
//        print_r($fieldOptions);
//        die();

    }

    private function renderSimplePlan($element, $fieldOptions, $pricingPlans, $form)
    {
        $plan = $pricingPlans[0];
        $currenySettings = Forms::getCurrencyAndLocale($form->ID);
        $title = ArrayHelper::get($element, 'field_options.label');
        $title .= ' - ' . $plan['name'];

        $fieldOptions['label'] = $title;

        $controlAttributes = array(
            'data-element_type' => $this->elementName,
            'class'             => $this->elementControlClass($element)
        );
        $paymentSummary = '';
        if (ArrayHelper::get($fieldOptions, 'show_payment_summary') == 'yes') {
            $paymentSummary = $this->getPaymentSummaryText($plan, $element, $form, $currenySettings);
        }
        $inputAttributes = [
            'type'  => 'hidden',
            'class' => 'wpf_payment_item',
            'value' => '0',
            'name'  => $element['id']
        ];

        $billingAttributes = $this->getPlanInputAttributes($plan);

        $itemInputAttributes = wp_parse_args($billingAttributes, $inputAttributes);

        ?>
        <div <?php echo $this->builtAttributes($controlAttributes); ?>>
            <?php if (ArrayHelper::get($fieldOptions, 'show_main_label') == 'yes'): ?>
                <?php $this->buildLabel($fieldOptions, $form); ?>
            <?php endif; ?>
            <?php echo $paymentSummary; ?>
            <input <?php echo $this->builtAttributes($itemInputAttributes); ?> />
        </div>
        <?php
    }

    private function renderSingleChoice($element, $fieldOptions, $pricingPlans, $form)
    {
        $currenySettings = Forms::getCurrencyAndLocale($form->ID);

        $elementId = 'wpf_' . $element['id'];
        $controlAttributes = array(
            'data-element_type' => $this->elementName,
            'class'             => $this->elementControlClass($element)
        );

        $type = ArrayHelper::get($fieldOptions, 'recurring_payment_options.selection_type', 'radio');
        ?>
        <div <?php echo $this->builtAttributes($controlAttributes); ?>>
            <?php if (ArrayHelper::get($fieldOptions, 'show_main_label') == 'yes'): ?>
                <?php $this->buildLabel($fieldOptions, $form); ?>
            <?php endif; ?>

            <?php if ($type == 'select') : ?>
                <?php
                $placeholder = __('--Select Plan--', 'wppayform');
                $placeholder = apply_filters('wppayform/subscription_selection_placeholder', $placeholder, $element, $form);
                $inputId = 'wpf_input_' . $form->ID . '_' . $this->elementName;
                $inputAttributes = array(
                    'data-required' => ArrayHelper::get($fieldOptions, 'required'),
                    'name'          => $element['id'],
                    'class'         => $this->elementInputClass($element) . ' wpf_payment_item',
                    'id'            => $inputId
                );
                ?>
                <div
                    class="wpf_multi_form_controls wpf_input_content wpf_subscrion_plans_select wpf_multi_form_controls_select">
                    <select <?php echo $this->builtAttributes($inputAttributes); ?>>
                        <?php if ($placeholder): ?>
                            <option data-type="placeholder" value=""><?php echo $placeholder; ?></option>
                        <?php endif; ?>
                        <?php foreach ($pricingPlans as $index => $plan): ?>
                            <?php
                            $optionAttributes = $this->getPlanInputAttributes($plan);
                            $optionAttributes['value'] = $index;

                            if ('yes' == $plan['is_default']) {
                                $optionAttributes['selected'] = 'true';
                            }
                            ?>
                            <option <?php echo $this->builtAttributes($optionAttributes); ?>><?php echo esc_attr($plan['name']); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <?php
                    if (ArrayHelper::get($fieldOptions, 'show_payment_summary') == 'yes') {
                        echo '<div class="wpf_subscription_plan_summary wpf_subscription_plan_summary_' . $inputId . '">';
                        foreach ($pricingPlans as $planIndex => $plan) {
                            $paymentSummary = $this->getPaymentSummaryText($plan, $element, $form, $currenySettings);
                            echo '<div style="display: none;" class="wpf_subscription_plan_summary_item wpf_subscription_plan_index_' . $planIndex . '">' . $paymentSummary . '</div>';
                        }
                        echo '</div>';
                    }
                    ?>
                </div>
            <?php else: ?>
                <div class="wpf_multi_form_controls wpf_input_content wpf_multi_form_controls_radio wpf_subscription_controls_radio">
                    <?php foreach ($pricingPlans as $index => $plan): ?>
                        <?php
                        $optionId = $element['id'] . '_' . $index . '_' . $form->ID;
                        $attributes = $this->getPlanInputAttributes($plan);
                        $attributes['class'] = 'form-check-input wpf_payment_item';
                        $attributes['type'] = 'radio';
                        $attributes['name'] = $element['id'];
                        $attributes['id'] = $optionId;
                        $attributes['value'] = $index;
                        if ('yes' == $plan['is_default']) {
                            $attributes['checked'] = 'true';
                        }
                        ?>
                        <div class="form-check">
                            <input <?php echo $this->builtAttributes($attributes); ?>>
                            <label class="form-check-label" for="<?php echo $optionId; ?>">
                                <span class="wpf_price_option_name"
                                      itemprop="description"><?php echo $plan['name']; ?></span>
                                <meta itemprop="price" content="<?php echo $plan['subscription_amount']; ?>">
                            </label>
                        </div>
                    <?php endforeach; ?>

                    <?php
                    if (ArrayHelper::get($fieldOptions, 'show_payment_summary') == 'yes') {
                        echo '<div class="wpf_subscription_plan_summary wpf_subscription_plan_summary_' . $element['id'] . '">';
                        foreach ($pricingPlans as $planIndex => $plan) {
                            $paymentSummary = $this->getPaymentSummaryText($plan, $element, $form, $currenySettings);
                            echo '<div style="display: none;" class="wpf_subscription_plan_summary_item wpf_subscription_plan_index_' . $planIndex . '">' . $paymentSummary . '</div>';
                        }
                        echo '</div>';
                    }
                    ?>

                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function getPaymentSummaryText($plan, $element, $form, $currenySettings)
    {
        $texts = apply_filters('wppayform/recurring_payment_summary_texts', [
            'signup_text' => __('Inital Payment: {signup_fee}', 'wppayform'),
            'trial_text'  => __('Recurring Total: {subscription_amount}/{billing_interval} after {trial_days} days', 'wppayform'),
            'normal'      => __('Recurring Total: {subscription_amount}/{billing_interval}', 'wppayform'),
            'bill_times'  => __('Total Billing times: {bill_times}', 'wppayform')
        ], $plan, $element, $form);

        $signupFee = wpPayFormFormattedMoney(wpPayFormConverToCents($plan['signup_fee']), $currenySettings);

        if ($plan['has_trial_days'] == 'yes' && $plan['trial_days']) {
            $signupFee = wpPayFormFormattedMoney(0, $currenySettings);
        }

        $replaces = array(
            '{signup_fee}'          => $signupFee,
            '{subscription_amount}' => wpPayFormFormattedMoney(wpPayFormConverToCents($plan['subscription_amount']), $currenySettings),
            '{billing_interval}'    => $plan['billing_interval'],
            '{trial_days}'          => $plan['trial_days'],
            '{bill_times}'          => $plan['bill_times']
        );

        foreach ($texts as $textKey => $text) {
            $texts[$textKey] = str_replace(array_keys($replaces), array_values($replaces), $text);
        }

        $finalText = '';
        $trialText = false;

        if ($this->hasSignupFee($plan) || $this->hasTrial($plan)) {
            $signupText = ArrayHelper::get($texts, 'signup_text');
            if ($signupText) {
                $finalText .= '<div class="wpf_summary_label wpf_initial_amount">' . $signupText . '</div>';
            }
        }
        if ($this->hasTrial($plan)) {
            $trialText = ArrayHelper::get($texts, 'trial_text');
            if ($trialText) {
                $finalText .= '<div class="wpf_summary_container"><div class="wpf_summary_label wpf_trial_amount">' . $trialText . '</div></div>';
            }
        }
        if (!$trialText) {
            $finalText .= '<div class="wpf_summary_label wpf_normal_amount">' . ArrayHelper::get($texts, 'normal') . '</div>';
        }

        if ($plan['bill_times']) {
            $finalText .= '<div class="wpf_summary_label wpf_normal_amount">' . ArrayHelper::get($texts, 'bill_times') . '</div>';
        }

        return '<div class="wpf_summary_container">' . $finalText . '</div>';
    }

    private function getPlanInputAttributes($plan)
    {
        $subscriptionAmount = wpPayFormConverToCents($plan['subscription_amount']);
        $currentBillableAmount = $subscriptionAmount;
        if ($this->hasSignupFee($plan)) {
            $currentBillableAmount = wpPayFormConverToCents($plan['signup_fee'] + $plan['subscription_amount']);
        }
        if ($this->hasTrial($plan)) {
            $currentBillableAmount = 0;
        }

        return [
            'data-subscription_amount' => $subscriptionAmount,
            'data-billing_interval'    => $plan['billing_interval'],
            'data-price'               => $currentBillableAmount
        ];
    }

    private function hasTrial($plan)
    {
        return $plan['has_trial_days'] == 'yes' && $plan['trial_days'];
    }

    private function hasSignupFee($plan)
    {
        return $plan['has_signup_fee'] == 'yes' && $plan['signup_fee'];
    }
}