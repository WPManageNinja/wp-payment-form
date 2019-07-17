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

        return apply_filters('wppayform/accepted_currencies', array(
            'AED' => 'United Arab Emirates Dirham',
            'AFN' => 'Afghan Afghani',
            'ALL' => 'Albanian Lek',
            'AMD' => 'Armenian Dram',
            'ANG' => 'Netherlands Antillean Gulden',
            'AOA' => 'Angolan Kwanza',
            'ARS' => 'Argentine Peso', // non amex
            'AUD' => 'Australian Dollar',
            'AWG' => 'Aruban Florin',
            'AZN' => 'Azerbaijani Manat',
            'BAM' => 'Bosnia & Herzegovina Convertible Mark',
            'BBD' => 'Barbadian Dollar',
            'BDT' => 'Bangladeshi Taka',
            'BIF' => 'Burundian Franc',
            'BGN' => 'Bulgarian Lev',
            'BMD' => 'Bermudian Dollar',
            'BND' => 'Brunei Dollar',
            'BOB' => 'Bolivian Boliviano',
            'BRL' => 'Brazilian Real',
            'BSD' => 'Bahamian Dollar',
            'BWP' => 'Botswana Pula',
            'BZD' => 'Belize Dollar',
            'CAD' => 'Canadian Dollar',
            'CDF' => 'Congolese Franc',
            'CHF' => 'Swiss Franc',
            'CLP' => 'Chilean Peso',
            'CNY' => 'Chinese Renminbi Yuan',
            'COP' => 'Colombian Peso',
            'CRC' => 'Costa Rican Colón',
            'CVE' => 'Cape Verdean Escudo',
            'CZK' => 'Czech Koruna',
            'DJF' => 'Djiboutian Franc',
            'DKK' => 'Danish Krone',
            'DOP' => 'Dominican Peso',
            'DZD' => 'Algerian Dinar',
            'EGP' => 'Egyptian Pound',
            'ETB' => 'Ethiopian Birr',
            'EUR' => 'Euro',
            'FJD' => 'Fijian Dollar',
            'FKP' => 'Falkland Islands Pound',
            'GBP' => 'British Pound',
            'GEL' => 'Georgian Lari',
            'GIP' => 'Gibraltar Pound',
            'GMD' => 'Gambian Dalasi',
            'GNF' => 'Guinean Franc',
            'GTQ' => 'Guatemalan Quetzal',
            'GYD' => 'Guyanese Dollar',
            'HKD' => 'Hong Kong Dollar',
            'HNL' => 'Honduran Lempira',
            'HRK' => 'Croatian Kuna',
            'HTG' => 'Haitian Gourde',
            'HUF' => 'Hungarian Forint',
            'IDR' => 'Indonesian Rupiah',
            'ILS' => 'Israeli New Sheqel',
            'INR' => 'Indian Rupee',
            'ISK' => 'Icelandic Króna',
            'JMD' => 'Jamaican Dollar',
            'JPY' => 'Japanese Yen',
            'KES' => 'Kenyan Shilling',
            'KGS' => 'Kyrgyzstani Som',
            'KHR' => 'Cambodian Riel',
            'KMF' => 'Comorian Franc',
            'KRW' => 'South Korean Won',
            'KYD' => 'Cayman Islands Dollar',
            'KZT' => 'Kazakhstani Tenge',
            'LAK' => 'Lao Kip',
            'LBP' => 'Lebanese Pound',
            'LKR' => 'Sri Lankan Rupee',
            'LRD' => 'Liberian Dollar',
            'LSL' => 'Lesotho Loti',
            'MAD' => 'Moroccan Dirham',
            'MDL' => 'Moldovan Leu',
            'MGA' => 'Malagasy Ariary',
            'MKD' => 'Macedonian Denar',
            'MNT' => 'Mongolian Tögrög',
            'MOP' => 'Macanese Pataca',
            'MRO' => 'Mauritanian Ouguiya',
            'MUR' => 'Mauritian Rupee',
            'MVR' => 'Maldivian Rufiyaa',
            'MWK' => 'Malawian Kwacha',
            'MXN' => 'Mexican Peso',
            'MYR' => 'Malaysian Ringgit',
            'MZN' => 'Mozambican Metical',
            'NAD' => 'Namibian Dollar',
            'NGN' => 'Nigerian Naira',
            'NIO' => 'Nicaraguan Córdoba',
            'NOK' => 'Norwegian Krone',
            'NPR' => 'Nepalese Rupee',
            'NZD' => 'New Zealand Dollar',
            'PAB' => 'Panamanian Balboa',
            'PEN' => 'Peruvian Nuevo Sol',
            'PGK' => 'Papua New Guinean Kina',
            'PHP' => 'Philippine Peso',
            'PKR' => 'Pakistani Rupee',
            'PLN' => 'Polish Złoty',
            'PYG' => 'Paraguayan Guaraní',
            'QAR' => 'Qatari Riyal',
            'RON' => 'Romanian Leu',
            'RSD' => 'Serbian Dinar',
            'RUB' => 'Russian Ruble',
            'RWF' => 'Rwandan Franc',
            'SAR' => 'Saudi Riyal',
            'SBD' => 'Solomon Islands Dollar',
            'SCR' => 'Seychellois Rupee',
            'SEK' => 'Swedish Krona',
            'SGD' => 'Singapore Dollar',
            'SHP' => 'Saint Helenian Pound',
            'SLL' => 'Sierra Leonean Leone',
            'SOS' => 'Somali Shilling',
            'SRD' => 'Surinamese Dollar',
            'STD' => 'São Tomé and Príncipe Dobra',
            'SVC' => 'Salvadoran Colón',
            'SZL' => 'Swazi Lilangeni',
            'THB' => 'Thai Baht',
            'TJS' => 'Tajikistani Somoni',
            'TOP' => 'Tongan Paʻanga',
            'TRY' => 'Turkish Lira',
            'TTD' => 'Trinidad and Tobago Dollar',
            'TWD' => 'New Taiwan Dollar',
            'TZS' => 'Tanzanian Shilling',
            'UAH' => 'Ukrainian Hryvnia',
            'UGX' => 'Ugandan Shilling',
            'USD' => 'United States Dollar',
            'UYU' => 'Uruguayan Peso',
            'UZS' => 'Uzbekistani Som',
            'VND' => 'Vietnamese Đồng',
            'VUV' => 'Vanuatu Vatu',
            'WST' => 'Samoan Tala',
            'XAF' => 'Central African Cfa Franc',
            'XCD' => 'East Caribbean Dollar',
            'XOF' => 'West African Cfa Franc',
            'XPF' => 'Cfp Franc',
            'YER' => 'Yemeni Rial',
            'ZAR' => 'South African Rand',
            'ZMW' => 'Zambian Kwacha',
        ));
    }

    /**
     * Get the available locales that Stripe can use
     *
     * @return array
     */
    public static function getLocales()
    {

        return array(
            ''     => 'English (en) (default)',
            'auto' => 'Auto-detect locale',
            'zh'   => 'Simplified Chinese (zh)',
            'da'   => 'Danish (da)',
            'nl'   => 'Dutch (nl)',
            'fi'   => 'Finnish (fi)',
            'fr'   => 'French (fr)',
            'de'   => 'German (de)',
            'it'   => 'Italian (it)',
            'ja'   => 'Japanese (ja)',
            'no'   => 'Norwegian (no)',
            'es'   => 'Spanish (es)',
            'sv'   => 'Swedish (sv)',
        );
    }

    public static function getComponents()
    {
        $components = array();
        return apply_filters('wppayform/form_components', $components);;
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

        $symbols = apply_filters('wppayform/currency_symbols', array(
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
        return apply_filters('wppayform/currency_symbol', $currency_symbol, $currency);
    }

    public static function getGlobalCurrencySettings()
    {
        $settings = get_option('wppayform_global_currency_settings', array());
        $defaults = array(
            'currency'               => 'USD',
            'locale'                 => 'auto',
            'currency_sign_position' => 'left',
            'currency_separator'     => 'dot_comma',
            'decimal_points'         => 0,
            'settings_type'          => 'global'
        );

        $settings = wp_parse_args($settings, $defaults);
        $settings = apply_filters('wppayform/global_currency_setting', $settings);

        $settings['is_zero_decimal'] = self::isZeroDecimal($defaults['currency']);

        return $settings;
    }

    public static function ipLoggingStatus($bool = false)
    {
        $status = get_option('wppayform_ip_logging_status');
        if (!$status) {
            $status = 'yes';
        }
        if ($bool) {
            $status == 'yes';
        }
        return apply_filters('wppayform/ip_logging_status', $status);
    }

    public static function getConfirmationPageSettings()
    {
        $settings = get_option('wppayform_confirmation_pages');
        if (is_array($settings)) {
            return $settings;
        }
        return array();
    }

    public static function getPaymentStatuses()
    {
        return apply_filters('wppayform/available_payment_statuses', array(
            'paid'       => __('Paid', 'wppayform'),
            'processing' => __('Processing', 'wppayform'),
            'pending'    => __('Pending', 'wppayform'),
            'failed'     => __('Failed', 'wppayform'),
            'refunded'   => __('Refunded', 'wppayform')
        ));
    }

    public static function zeroDecimalCurrencies()
    {
        return apply_filters('swppayform/zero_decimal_currencies', array(
            'BIF' => esc_html__('Burundian Franc', 'wppayform'),
            'CLP' => esc_html__('Chilean Peso', 'wppayform'),
            'DJF' => esc_html__('Djiboutian Franc', 'wppayform'),
            'GNF' => esc_html__('Guinean Franc', 'wppayform'),
            'JPY' => esc_html__('Japanese Yen', 'wppayform'),
            'KMF' => esc_html__('Comorian Franc', 'wppayform'),
            'KRW' => esc_html__('South Korean Won', 'wppayform'),
            'MGA' => esc_html__('Malagasy Ariary', 'wppayform'),
            'PYG' => esc_html__('Paraguayan Guaraní', 'wppayform'),
            'RWF' => esc_html__('Rwandan Franc', 'wppayform'),
            'VND' => esc_html__('Vietnamese Dong', 'wppayform'),
            'VUV' => esc_html__('Vanuatu Vatu', 'wppayform'),
            'XAF' => esc_html__('Central African Cfa Franc', 'wppayform'),
            'XOF' => esc_html__('West African Cfa Franc', 'wppayform'),
            'XPF' => esc_html__('Cfp Franc', 'wppayform'),
        ));
    }

    public static function isZeroDecimal($currencyCode)
    {
        $currencyCode = strtoupper($currencyCode);
        $zeroDecimals = self::zeroDecimalCurrencies();
        return isset($zeroDecimals[$currencyCode]);
    }

    public static function getRecaptchaSettings()
    {
        $settings = get_option('wppayform_recaptcha_settings', array());
        $defaults = array(
            'recaptcha_version' => 'none',
            'site_key'          => '',
            'secret_key'        => '',
            'all_forms'        => 'no'
        );
        $settings = wp_parse_args($settings, $defaults);
        $settings = apply_filters('wppayform/recaptcha_setting', $settings);
        return $settings;
    }
}