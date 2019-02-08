<?php

namespace WPPayForm\Classes;

use WPPayForm\Classes\Models\Forms;
use WPPayForm\Classes\Models\OrderItem;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Placeholder Parser for a submission
 * @since 1.0.0
 */
class PlaceholderParser
{
    /**
     * @param $string
     * @param $submission
     *
     * Possible Submission Parameters: [can be found on submission.ATRRIBUTE_NAME]
     * {submission.id}
     * {submission.submission_hash}
     * {submission.customer_name}
     * {submission.customer_email}
     * {submission.payment_total}
     * {submission.payment_status}
     *
     * Possible Input Item Parameters [can be found on submission.form_data_formatted.INPUTNAME]
     * {input.INPUTNAME}
     *
     * Possible Product Items [can be found on submission -> wpf_order_items  where submission_id = submission.id and parent_holder = INPUTNAME]
     * {payment_item.INPUTNAME}
     *
     * Possible Quantity items: [can be found on submission.form_data_raw.INPUTNAME]
     * {quantity.INPUTNAME}
     */
    public static function parse($string, $submission)
    {
        $parsables = self::parseShortcode($string);
        if(!$parsables) {
            return $string;
        }

        $parsedData = array();

        $formattedParsables = array();
        foreach ($parsables as $parsableKey => $parsable) {
            // Get Parsed Group
            $group = strtok($parsable, '.');
            $itemExt = str_replace($group.'.', '', $parsable);
            $formattedParsables[$group][$itemExt] = $itemExt;
        }
        foreach ($formattedParsables as $group => $items) {
            if($group == 'input') {
                $parsedData[$group] = $submission->form_data_formatted;
            } else if($group == 'submission') {
                $parsedData[$group] = array(
                    'id' => $submission->id,
                    'customer_name' => $submission->customer_name,
                    'customer_email' => $submission->customer_email,
                    'currency' => $submission->currency,
                    'payment_status' => $submission->payment_status,
                    'payment_total' => $submission->payment_total,
                    'payment_mode' => $submission->payment_mode,
                    'payment_method' => $submission->payment_method,
                    'status' => $submission->status,
                    'ip_address' => $submission->ip_address,
                    'created_at' => $submission->created_at,
                );
            } else if($group == 'payment_item') {
                foreach ($items as $itemkey =>  $item) {
                   $itemNames = wpPayformDB()->table('wpf_order_items')
                       ->select(array('item_name'))
                       ->where('submission_id', $submission->id )
                       ->where('parent_holder', $item)
                       ->get();
                   $names = array();
                   foreach ($itemNames as $itemName) {
                       $names[] = $itemName->item_name;
                   }
                }
                $parsedData['payment_item'][$itemkey] = $names;
            }
        }

        $parseItems = array();
        foreach ($parsables as $parseKey => $parseValue) {
            $value = ArrayHelper::get($parsedData, $parseValue, '');
            if($value && $parseKey == '{submission.payment_total}') {
                $value = wpfFormattedMoney($value, Forms::getCurrencyAndLocale($submission->form_id));
            }
            if( is_array($value) ) {
                $value = implode(' ', $value);
            }
            $parseItems[$parseKey] = $value;
        }

        return str_replace(array_keys($parseItems), array_values($parseItems), $string);
    }

    public static function parseShortcode($string)
    {
        $parsables = [];
        preg_replace_callback('/{+(.*?)}/', function ($matches) use (&$parsables) {
            $parsables[$matches[0]] = $matches[1];
        }, $string);
        return $parsables;
    }
}