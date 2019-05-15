<?php

namespace WPPayForm\Classes\Entry;


use WPPayForm\Classes\Models\Forms;
use WPPayForm\Classes\Models\OrderItem;
use WPPayForm\Classes\View;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Entry Methods
 * @since 1.0.0
 */
class Entry
{
    protected $formId;
    protected $submissionId;
    protected $submission;
    protected $formattedInput;
    protected $rawInput;
    public $default = false;

    public function __construct($submission)
    {
        $this->formId = $submission->form_id;
        $this->submissionId = $submission->id;
        $this->submission = $submission;
        $this->formattedInput = $submission->form_data_formatted;
        $this->rawInput = $submission->form_data_raw;
    }

    public function getRawInput($key, $default = false)
    {
        if (isset($this->rawInput[$key])) {
            return $this->rawInput[$key];
        }
        return $default;
    }

    public function getInput($key, $default = false )
    {
        if (isset($this->formattedInput[$key])) {
            return $this->formattedInput[$key];
        }
        return $default;
    }

    public function getItemQuantity($key)
    {
        return $this->getRawInput($key);
    }

    public function getPaymentItems($itemName)
    {
        $names = array();
        $itemNames = wpPayFormDB()->table('wpf_order_items')
            ->select(array('item_name'))
            ->where('submission_id', $this->submissionId)
            ->where('parent_holder', $itemName)
            ->get();
        foreach ($itemNames as $itemName) {
            $names[] = $itemName->item_name;
        }
        return $names;
    }

    public function getInputFieldsHtmlTable()
    {
        // We have to make the items as label and value pair first
        $inputItems = $this->formattedInput;
        $labels = (array)Forms::getFormInputLabels($this->formId);
        $items = array();
        foreach ($inputItems as $itemKey => $item) {
            $label = $itemKey;
            if (!empty($labels[$itemKey])) {
                $label = $labels[$itemKey];
            }
            $items[] = array(
                'label' => $label,
                'value' => $item
            );
        }

        return View::make('elements.input_fields_html', array(
            'items' => $items
        ));
    }

    public function getOrderItemsHtml()
    {
        // Just check if submission order items added or not
        $this->getOrderItems();

        return View::make('elements.order_items_table', array(
            'submission' => $this->submission
        ));
    }

    public function getOrderItems()
    {
        if(!property_exists($this->submission, 'order_items')) {
            $orderItem = new OrderItem();
            $this->submission->order_items = $orderItem->getOrderItems($this->submissionId);
        }
        return $this->submission->order_items;
    }

    public function getOrderItemsAsText($separator = "\n")
    {
        $orderItems = $this->getOrderItems();
        $text = '';
        foreach ($orderItems as $index => $orderItem) {

            $text .= $orderItem->item_name.' ('.$orderItem->quantity.') - '. number_format($orderItem->line_total / 100, 2);
            //if($index != count($orderItems) - 1) {
                $text .= $separator;
            //}
            $text .= $orderItem->item_name.' ('.$orderItem->quantity.') - '. number_format($orderItem->line_total / 100, 2);
            //if($index != count($orderItems) - 1) {
            $text .= $separator;
            //}
        }
        return $text;
    }

    public function __get($name)
    {
        if ($name == 'all_input_field_html') {
            return $this->getInputFieldsHtmlTable();
        }

        if($name == 'product_items_table_html') {
            return $this->getOrderItemsHtml();
        }

        if ($name == 'payment_total_in_cents') {
            return $this->submission->payment_total;
        } else if ($name == 'payment_total_in_decimal') {
            return number_format($this->submission->payment_total/ 100 , 2);
        }

        if (property_exists($this->submission, $name)) {
            if ($name == 'payment_total') {
                return $this->paymentTotal();
            }
            return $this->submission->{$name};
        }

        return $this->default;
    }

    public function paymentTotal()
    {
        return wpPayFormFormattedMoney($this->submission->payment_total, Forms::getCurrencyAndLocale($this->form_id));
    }
}