<?php

namespace WPPayForm\Classes;

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
        return $string;
        // return str_replace(array_keys($submissionMaps), array_values($submissionMaps), $string);
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