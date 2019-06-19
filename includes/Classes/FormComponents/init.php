<?php
// Load and Register Form Components
new \WPPayForm\Classes\FormComponents\CustomerNameComponent();
new \WPPayForm\Classes\FormComponents\CustomerEmailComponent();
new \WPPayForm\Classes\FormComponents\TextComponent();
new \WPPayForm\Classes\FormComponents\NumberComponent();
new \WPPayForm\Classes\FormComponents\SelectComponent();
new \WPPayForm\Classes\FormComponents\RadioComponent();
new \WPPayForm\Classes\FormComponents\CheckBoxComponent();
new \WPPayForm\Classes\FormComponents\TextAreaComponent();
new \WPPayForm\Classes\FormComponents\HtmlComponent();
new \WPPayForm\Classes\FormComponents\PaymentItemComponent();
new \WPPayForm\Classes\FormComponents\ItemQuantityComponent();
new \WPPayForm\Classes\FormComponents\DateComponent();
new \WPPayForm\Classes\FormComponents\CustomAmountComponent();
new \WPPayForm\Classes\FormComponents\ChoosePaymentMethodComponent();
new \WPPayForm\Classes\FormComponents\HiddenInputComponent();

if(!defined('WPPAYFORM_PRO_INSTALLED')) {
    new \WPPayForm\Classes\FormComponents\DemoFileUploadComponent();
    new \WPPayForm\Classes\FormComponents\DemoTaxItemComponent();
    new \WPPayForm\Classes\FormComponents\DemoPayPalElement();
    new \WPPayForm\Classes\FormComponents\DemoTabularProductsComponent();
    new \WPPayForm\Classes\FormComponents\DemoRecurringPaymentComponent();
}
