<?php

namespace WPPayForm\Classes;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * General Settings Definations here
 * @since 1.0.0
 */
class GeneralSettings
{
    /**
     * https://support.stripe.com/questions/which-currencies-does-stripe-support
     */
    public static function getCurrencies()
    {

        return array(
            'AED' => __('United Arab Emirates Dirham', 'wppayform'),
            'AFN' => __('Afghan Afghani', 'wppayform'),
            'ALL' => __('Albanian Lek', 'wppayform'),
            'AMD' => __('Armenian Dram', 'wppayform'),
            'ANG' => __('Netherlands Antillean Gulden', 'wppayform'),
            'AOA' => __('Angolan Kwanza', 'wppayform'),
            'ARS' => __('Argentine Peso', 'wppayform'), // non amex
            'AUD' => __('Australian Dollar', 'wppayform'),
            'AWG' => __('Aruban Florin', 'wppayform'),
            'AZN' => __('Azerbaijani Manat', 'wppayform'),
            'BAM' => __('Bosnia & Herzegovina Convertible Mark', 'wppayform'),
            'BBD' => __('Barbadian Dollar', 'wppayform'),
            'BDT' => __('Bangladeshi Taka', 'wppayform'),
            'BIF' => __('Burundian Franc', 'wppayform'),
            'BGN' => __('Bulgarian Lev', 'wppayform'),
            'BMD' => __('Bermudian Dollar', 'wppayform'),
            'BND' => __('Brunei Dollar', 'wppayform'),
            'BOB' => __('Bolivian Boliviano', 'wppayform'),
            'BRL' => __('Brazilian Real', 'wppayform'),
            'BSD' => __('Bahamian Dollar', 'wppayform'),
            'BWP' => __('Botswana Pula', 'wppayform'),
            'BZD' => __('Belize Dollar', 'wppayform'),
            'CAD' => __('Canadian Dollar', 'wppayform'),
            'CDF' => __('Congolese Franc', 'wppayform'),
            'CHF' => __('Swiss Franc', 'wppayform'),
            'CLP' => __('Chilean Peso', 'wppayform'),
            'CNY' => __('Chinese Renminbi Yuan', 'wppayform'),
            'COP' => __('Colombian Peso', 'wppayform'),
            'CRC' => __('Costa Rican Colón', 'wppayform'),
            'CVE' => __('Cape Verdean Escudo', 'wppayform'),
            'CZK' => __('Czech Koruna', 'wppayform'),
            'DJF' => __('Djiboutian Franc', 'wppayform'),
            'DKK' => __('Danish Krone', 'wppayform'),
            'DOP' => __('Dominican Peso', 'wppayform'),
            'DZD' => __('Algerian Dinar', 'wppayform'),
            'EGP' => __('Egyptian Pound', 'wppayform'),
            'ETB' => __('Ethiopian Birr', 'wppayform'),
            'EUR' => __('Euro', 'wppayform'),
            'FJD' => __('Fijian Dollar', 'wppayform'),
            'FKP' => __('Falkland Islands Pound', 'wppayform'),
            'GBP' => __('British Pound', 'wppayform'),
            'GEL' => __('Georgian Lari', 'wppayform'),
            'GIP' => __('Gibraltar Pound', 'wppayform'),
            'GMD' => __('Gambian Dalasi', 'wppayform'),
            'GNF' => __('Guinean Franc', 'wppayform'),
            'GTQ' => __('Guatemalan Quetzal', 'wppayform'),
            'GYD' => __('Guyanese Dollar', 'wppayform'),
            'HKD' => __('Hong Kong Dollar', 'wppayform'),
            'HNL' => __('Honduran Lempira', 'wppayform'),
            'HRK' => __('Croatian Kuna', 'wppayform'),
            'HTG' => __('Haitian Gourde', 'wppayform'),
            'HUF' => __('Hungarian Forint', 'wppayform'),
            'IDR' => __('Indonesian Rupiah', 'wppayform'),
            'ILS' => __('Israeli New Sheqel', 'wppayform'),
            'INR' => __('Indian Rupee', 'wppayform'),
            'ISK' => __('Icelandic Króna', 'wppayform'),
            'JMD' => __('Jamaican Dollar', 'wppayform'),
            'JPY' => __('Japanese Yen', 'wppayform'),
            'KES' => __('Kenyan Shilling', 'wppayform'),
            'KGS' => __('Kyrgyzstani Som', 'wppayform'),
            'KHR' => __('Cambodian Riel', 'wppayform'),
            'KMF' => __('Comorian Franc', 'wppayform'),
            'KRW' => __('South Korean Won', 'wppayform'),
            'KYD' => __('Cayman Islands Dollar', 'wppayform'),
            'KZT' => __('Kazakhstani Tenge', 'wppayform'),
            'LAK' => __('Lao Kip', 'wppayform'),
            'LBP' => __('Lebanese Pound', 'wppayform'),
            'LKR' => __('Sri Lankan Rupee', 'wppayform'),
            'LRD' => __('Liberian Dollar', 'wppayform'),
            'LSL' => __('Lesotho Loti', 'wppayform'),
            'MAD' => __('Moroccan Dirham', 'wppayform'),
            'MDL' => __('Moldovan Leu', 'wppayform'),
            'MGA' => __('Malagasy Ariary', 'wppayform'),
            'MKD' => __('Macedonian Denar', 'wppayform'),
            'MNT' => __('Mongolian Tögrög', 'wppayform'),
            'MOP' => __('Macanese Pataca', 'wppayform'),
            'MRO' => __('Mauritanian Ouguiya', 'wppayform'),
            'MUR' => __('Mauritian Rupee', 'wppayform'),
            'MVR' => __('Maldivian Rufiyaa', 'wppayform'),
            'MWK' => __('Malawian Kwacha', 'wppayform'),
            'MXN' => __('Mexican Peso', 'wppayform'),
            'MYR' => __('Malaysian Ringgit', 'wppayform'),
            'MZN' => __('Mozambican Metical', 'wppayform'),
            'NAD' => __('Namibian Dollar', 'wppayform'),
            'NGN' => __('Nigerian Naira', 'wppayform'),
            'NIO' => __('Nicaraguan Córdoba', 'wppayform'),
            'NOK' => __('Norwegian Krone', 'wppayform'),
            'NPR' => __('Nepalese Rupee', 'wppayform'),
            'NZD' => __('New Zealand Dollar', 'wppayform'),
            'PAB' => __('Panamanian Balboa', 'wppayform'),
            'PEN' => __('Peruvian Nuevo Sol', 'wppayform'),
            'PGK' => __('Papua New Guinean Kina', 'wppayform'),
            'PHP' => __('Philippine Peso', 'wppayform'),
            'PKR' => __('Pakistani Rupee', 'wppayform'),
            'PLN' => __('Polish Złoty', 'wppayform'),
            'PYG' => __('Paraguayan Guaraní', 'wppayform'),
            'QAR' => __('Qatari Riyal', 'wppayform'),
            'RON' => __('Romanian Leu', 'wppayform'),
            'RSD' => __('Serbian Dinar', 'wppayform'),
            'RUB' => __('Russian Ruble', 'wppayform'),
            'RWF' => __('Rwandan Franc', 'wppayform'),
            'SAR' => __('Saudi Riyal', 'wppayform'),
            'SBD' => __('Solomon Islands Dollar', 'wppayform'),
            'SCR' => __('Seychellois Rupee', 'wppayform'),
            'SEK' => __('Swedish Krona', 'wppayform'),
            'SGD' => __('Singapore Dollar', 'wppayform'),
            'SHP' => __('Saint Helenian Pound', 'wppayform'),
            'SLL' => __('Sierra Leonean Leone', 'wppayform'),
            'SOS' => __('Somali Shilling', 'wppayform'),
            'SRD' => __('Surinamese Dollar', 'wppayform'),
            'STD' => __('São Tomé and Príncipe Dobra', 'wppayform'),
            'SVC' => __('Salvadoran Colón', 'wppayform'),
            'SZL' => __('Swazi Lilangeni', 'wppayform'),
            'THB' => __('Thai Baht', 'wppayform'),
            'TJS' => __('Tajikistani Somoni', 'wppayform'),
            'TOP' => __('Tongan Paʻanga', 'wppayform'),
            'TRY' => __('Turkish Lira', 'wppayform'),
            'TTD' => __('Trinidad and Tobago Dollar', 'wppayform'),
            'TWD' => __('New Taiwan Dollar', 'wppayform'),
            'TZS' => __('Tanzanian Shilling', 'wppayform'),
            'UAH' => __('Ukrainian Hryvnia', 'wppayform'),
            'UGX' => __('Ugandan Shilling', 'wppayform'),
            'USD' => __('United States Dollar', 'wppayform'),
            'UYU' => __('Uruguayan Peso', 'wppayform'),
            'UZS' => __('Uzbekistani Som', 'wppayform'),
            'VND' => __('Vietnamese Đồng', 'wppayform'),
            'VUV' => __('Vanuatu Vatu', 'wppayform'),
            'WST' => __('Samoan Tala', 'wppayform'),
            'XAF' => __('Central African Cfa Franc', 'wppayform'),
            'XCD' => __('East Caribbean Dollar', 'wppayform'),
            'XOF' => __('West African Cfa Franc', 'wppayform'),
            'XPF' => __('Cfp Franc', 'wppayform'),
            'YER' => __('Yemeni Rial', 'wppayform'),
            'ZAR' => __('South African Rand', 'wppayform'),
            'ZMW' => __('Zambian Kwacha', 'wppayform'),
        );
    }

    /**
     * Get the available locales that Stripe can use
     *
     * @return array
     */
    public static function getLocales()
    {

        return array(
            ''     => __('English (en) (default)', 'wppayform'),
            'auto' => __('Auto-detect locale', 'wppayform'),
            'zh'   => __('Simplified Chinese (zh)', 'wppayform'),
            'da'   => __('Danish (da)', 'wppayform'),
            'nl'   => __('Dutch (nl)', 'wppayform'),
            'fi'   => __('Finnish (fi)', 'wppayform'),
            'fr'   => __('French (fr)', 'wppayform'),
            'de'   => __('German (de)', 'wppayform'),
            'it'   => __('Italian (it)', 'wppayform'),
            'ja'   => __('Japanese (ja)', 'wppayform'),
            'no'   => __('Norwegian (no)', 'wppayform'),
            'es'   => __('Spanish (es)', 'wppayform'),
            'sv'   => __('Swedish (sv)', 'wppayform'),
        );
    }

    public static function getComponents()
    {
        $components = array();
        return apply_filters('wp_payment_form_components', $components);;
    }

    /**
     * Get a specific currency symbol
     *
     * https://support.stripe.com/questions/which-currencies-does-stripe-support
     */
    public static function getCurrencySymbol($currency = '')
    {

        if (!$currency) {
            // If no currency is passed then default it to USD
            $currency = 'USD';
        }

        $currency = strtoupper($currency);

        $symbols = apply_filters('wpf_currency_symbols', array(
            'AED' => '&#x62f;.&#x625;',
            'AFN' => '&#x60b;',
            'ALL' => 'L',
            'AMD' => 'AMD',
            'ANG' => '&fnof;',
            'AOA' => 'Kz',
            'ARS' => '&#36;',
            'AUD' => '&#36;',
            'AWG' => '&fnof;',
            'AZN' => 'AZN',
            'BAM' => 'KM',
            'BBD' => '&#36;',
            'BDT' => '&#2547;&nbsp;',
            'BGN' => '&#1083;&#1074;.',
            'BHD' => '.&#x62f;.&#x628;',
            'BIF' => 'Fr',
            'BMD' => '&#36;',
            'BND' => '&#36;',
            'BOB' => 'Bs.',
            'BRL' => '&#82;&#36;',
            'BSD' => '&#36;',
            'BTC' => '&#3647;',
            'BTN' => 'Nu.',
            'BWP' => 'P',
            'BYR' => 'Br',
            'BZD' => '&#36;',
            'CAD' => '&#36;',
            'CDF' => 'Fr',
            'CHF' => '&#67;&#72;&#70;',
            'CLP' => '&#36;',
            'CNY' => '&yen;',
            'COP' => '&#36;',
            'CRC' => '&#x20a1;',
            'CUC' => '&#36;',
            'CUP' => '&#36;',
            'CVE' => '&#36;',
            'CZK' => '&#75;&#269;',
            'DJF' => 'Fr',
            'DKK' => 'DKK',
            'DOP' => 'RD&#36;',
            'DZD' => '&#x62f;.&#x62c;',
            'EGP' => 'EGP',
            'ERN' => 'Nfk',
            'ETB' => 'Br',
            'EUR' => '&euro;',
            'FJD' => '&#36;',
            'FKP' => '&pound;',
            'GBP' => '&pound;',
            'GEL' => '&#x10da;',
            'GGP' => '&pound;',
            'GHS' => '&#x20b5;',
            'GIP' => '&pound;',
            'GMD' => 'D',
            'GNF' => 'Fr',
            'GTQ' => 'Q',
            'GYD' => '&#36;',
            'HKD' => '&#36;',
            'HNL' => 'L',
            'HRK' => 'Kn',
            'HTG' => 'G',
            'HUF' => '&#70;&#116;',
            'IDR' => 'Rp',
            'ILS' => '&#8362;',
            'IMP' => '&pound;',
            'INR' => '&#8377;',
            'IQD' => '&#x639;.&#x62f;',
            'IRR' => '&#xfdfc;',
            'ISK' => 'Kr.',
            'JEP' => '&pound;',
            'JMD' => '&#36;',
            'JOD' => '&#x62f;.&#x627;',
            'JPY' => '&yen;',
            'KES' => 'KSh',
            'KGS' => '&#x43b;&#x432;',
            'KHR' => '&#x17db;',
            'KMF' => 'Fr',
            'KPW' => '&#x20a9;',
            'KRW' => '&#8361;',
            'KWD' => '&#x62f;.&#x643;',
            'KYD' => '&#36;',
            'KZT' => 'KZT',
            'LAK' => '&#8365;',
            'LBP' => '&#x644;.&#x644;',
            'LKR' => '&#xdbb;&#xdd4;',
            'LRD' => '&#36;',
            'LSL' => 'L',
            'LYD' => '&#x644;.&#x62f;',
            'MAD' => '&#x62f;. &#x645;.',
            'MDL' => 'L',
            'MGA' => 'Ar',
            'MKD' => '&#x434;&#x435;&#x43d;',
            'MMK' => 'Ks',
            'MNT' => '&#x20ae;',
            'MOP' => 'P',
            'MRO' => 'UM',
            'MUR' => '&#x20a8;',
            'MVR' => '.&#x783;',
            'MWK' => 'MK',
            'MXN' => '&#36;',
            'MYR' => '&#82;&#77;',
            'MZN' => 'MT',
            'NAD' => '&#36;',
            'NGN' => '&#8358;',
            'NIO' => 'C&#36;',
            'NOK' => '&#107;&#114;',
            'NPR' => '&#8360;',
            'NZD' => '&#36;',
            'OMR' => '&#x631;.&#x639;.',
            'PAB' => 'B/.',
            'PEN' => 'S/.',
            'PGK' => 'K',
            'PHP' => '&#8369;',
            'PKR' => '&#8360;',
            'PLN' => '&#122;&#322;',
            'PRB' => '&#x440;.',
            'PYG' => '&#8370;',
            'QAR' => '&#x631;.&#x642;',
            'RMB' => '&yen;',
            'RON' => 'lei',
            'RSD' => '&#x434;&#x438;&#x43d;.',
            'RUB' => '&#8381;',
            'RWF' => 'Fr',
            'SAR' => '&#x631;.&#x633;',
            'SBD' => '&#36;',
            'SCR' => '&#x20a8;',
            'SDG' => '&#x62c;.&#x633;.',
            'SEK' => '&#107;&#114;',
            'SGD' => '&#36;',
            'SHP' => '&pound;',
            'SLL' => 'Le',
            'SOS' => 'Sh',
            'SRD' => '&#36;',
            'SSP' => '&pound;',
            'STD' => 'Db',
            'SYP' => '&#x644;.&#x633;',
            'SZL' => 'L',
            'THB' => '&#3647;',
            'TJS' => '&#x405;&#x41c;',
            'TMT' => 'm',
            'TND' => '&#x62f;.&#x62a;',
            'TOP' => 'T&#36;',
            'TRY' => '&#8378;',
            'TTD' => '&#36;',
            'TWD' => '&#78;&#84;&#36;',
            'TZS' => 'Sh',
            'UAH' => '&#8372;',
            'UGX' => 'UGX',
            'USD' => '&#36;',
            'UYU' => '&#36;',
            'UZS' => 'UZS',
            'VEF' => 'Bs F',
            'VND' => '&#8363;',
            'VUV' => 'Vt',
            'WST' => 'T',
            'XAF' => 'Fr',
            'XCD' => '&#36;',
            'XOF' => 'Fr',
            'XPF' => 'Fr',
            'YER' => '&#xfdfc;',
            'ZAR' => '&#82;',
            'ZMW' => 'ZK',
        ));
        $currency_symbol = isset($symbols[$currency]) ? $symbols[$currency] : '';
        return apply_filters('wpf_currency_symbol', $currency_symbol, $currency);
    }

    public static function getGlobalCurrencySettings($formId = false)
    {
        $settings = get_option('_wppayform_global_currency_settings', array());
        $defaults = array(
            'currency' => 'USD',
            'locale' => 'auto',
            'currency_sign_position' => 'left',
            'currency_separator' => 'dot_comma',
            'decimal_points' => 0
        );

        $settings = wp_parse_args($settings, $defaults);
        $settings = apply_filters('wpf_global_currency_setting', $settings, $formId);
        return $settings;
    }

    public static function ipLoggingStatus($bool = false)
    {
        $status = get_option('_wpf_ip_logging_status');
        if(!$status) {
            $status = 'yes';
        }
        if($bool) {
            return $status == 'yes';
        }
        return $status;
    }
}