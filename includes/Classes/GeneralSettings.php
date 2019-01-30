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
    public static function getCurrencies() {

        return array(
            'AED' => __( 'United Arab Emirates Dirham', 'wppayform' ),
            'AFN' => __( 'Afghan Afghani', 'wppayform' ),
            'ALL' => __( 'Albanian Lek', 'wppayform' ),
            'AMD' => __( 'Armenian Dram', 'wppayform' ),
            'ANG' => __( 'Netherlands Antillean Gulden', 'wppayform' ),
            'AOA' => __( 'Angolan Kwanza', 'wppayform' ),
            'ARS' => __( 'Argentine Peso', 'wppayform' ), // non amex
            'AUD' => __( 'Australian Dollar', 'wppayform' ),
            'AWG' => __( 'Aruban Florin', 'wppayform' ),
            'AZN' => __( 'Azerbaijani Manat', 'wppayform' ),
            'BAM' => __( 'Bosnia & Herzegovina Convertible Mark', 'wppayform' ),
            'BBD' => __( 'Barbadian Dollar', 'wppayform' ),
            'BDT' => __( 'Bangladeshi Taka', 'wppayform' ),
            'BIF' => __( 'Burundian Franc', 'wppayform' ),
            'BGN' => __( 'Bulgarian Lev', 'wppayform' ),
            'BMD' => __( 'Bermudian Dollar', 'wppayform' ),
            'BND' => __( 'Brunei Dollar', 'wppayform' ),
            'BOB' => __( 'Bolivian Boliviano', 'wppayform' ),
            'BRL' => __( 'Brazilian Real', 'wppayform' ),
            'BSD' => __( 'Bahamian Dollar', 'wppayform' ),
            'BWP' => __( 'Botswana Pula', 'wppayform' ),
            'BZD' => __( 'Belize Dollar', 'wppayform' ),
            'CAD' => __( 'Canadian Dollar', 'wppayform' ),
            'CDF' => __( 'Congolese Franc', 'wppayform' ),
            'CHF' => __( 'Swiss Franc', 'wppayform' ),
            'CLP' => __( 'Chilean Peso', 'wppayform' ),
            'CNY' => __( 'Chinese Renminbi Yuan', 'wppayform' ),
            'COP' => __( 'Colombian Peso', 'wppayform' ),
            'CRC' => __( 'Costa Rican Colón', 'wppayform' ),
            'CVE' => __( 'Cape Verdean Escudo', 'wppayform' ),
            'CZK' => __( 'Czech Koruna', 'wppayform' ),
            'DJF' => __( 'Djiboutian Franc', 'wppayform' ),
            'DKK' => __( 'Danish Krone', 'wppayform' ),
            'DOP' => __( 'Dominican Peso', 'wppayform' ),
            'DZD' => __( 'Algerian Dinar', 'wppayform' ),
            'EGP' => __( 'Egyptian Pound', 'wppayform' ),
            'ETB' => __( 'Ethiopian Birr', 'wppayform' ),
            'EUR' => __( 'Euro', 'wppayform' ),
            'FJD' => __( 'Fijian Dollar', 'wppayform' ),
            'FKP' => __( 'Falkland Islands Pound', 'wppayform' ),
            'GBP' => __( 'British Pound', 'wppayform' ),
            'GEL' => __( 'Georgian Lari', 'wppayform' ),
            'GIP' => __( 'Gibraltar Pound', 'wppayform' ),
            'GMD' => __( 'Gambian Dalasi', 'wppayform' ),
            'GNF' => __( 'Guinean Franc', 'wppayform' ),
            'GTQ' => __( 'Guatemalan Quetzal', 'wppayform' ),
            'GYD' => __( 'Guyanese Dollar', 'wppayform' ),
            'HKD' => __( 'Hong Kong Dollar', 'wppayform' ),
            'HNL' => __( 'Honduran Lempira', 'wppayform' ),
            'HRK' => __( 'Croatian Kuna', 'wppayform' ),
            'HTG' => __( 'Haitian Gourde', 'wppayform' ),
            'HUF' => __( 'Hungarian Forint', 'wppayform' ),
            'IDR' => __( 'Indonesian Rupiah', 'wppayform' ),
            'ILS' => __( 'Israeli New Sheqel', 'wppayform' ),
            'INR' => __( 'Indian Rupee', 'wppayform' ),
            'ISK' => __( 'Icelandic Króna', 'wppayform' ),
            'JMD' => __( 'Jamaican Dollar', 'wppayform' ),
            'JPY' => __( 'Japanese Yen', 'wppayform' ),
            'KES' => __( 'Kenyan Shilling', 'wppayform' ),
            'KGS' => __( 'Kyrgyzstani Som', 'wppayform' ),
            'KHR' => __( 'Cambodian Riel', 'wppayform' ),
            'KMF' => __( 'Comorian Franc', 'wppayform' ),
            'KRW' => __( 'South Korean Won', 'wppayform' ),
            'KYD' => __( 'Cayman Islands Dollar', 'wppayform' ),
            'KZT' => __( 'Kazakhstani Tenge', 'wppayform' ),
            'LAK' => __( 'Lao Kip', 'wppayform' ),
            'LBP' => __( 'Lebanese Pound', 'wppayform' ),
            'LKR' => __( 'Sri Lankan Rupee', 'wppayform' ),
            'LRD' => __( 'Liberian Dollar', 'wppayform' ),
            'LSL' => __( 'Lesotho Loti', 'wppayform' ),
            'MAD' => __( 'Moroccan Dirham', 'wppayform' ),
            'MDL' => __( 'Moldovan Leu', 'wppayform' ),
            'MGA' => __( 'Malagasy Ariary', 'wppayform' ),
            'MKD' => __( 'Macedonian Denar', 'wppayform' ),
            'MNT' => __( 'Mongolian Tögrög', 'wppayform' ),
            'MOP' => __( 'Macanese Pataca', 'wppayform' ),
            'MRO' => __( 'Mauritanian Ouguiya', 'wppayform' ),
            'MUR' => __( 'Mauritian Rupee', 'wppayform' ),
            'MVR' => __( 'Maldivian Rufiyaa', 'wppayform' ),
            'MWK' => __( 'Malawian Kwacha', 'wppayform' ),
            'MXN' => __( 'Mexican Peso', 'wppayform' ),
            'MYR' => __( 'Malaysian Ringgit', 'wppayform' ),
            'MZN' => __( 'Mozambican Metical', 'wppayform' ),
            'NAD' => __( 'Namibian Dollar', 'wppayform' ),
            'NGN' => __( 'Nigerian Naira', 'wppayform' ),
            'NIO' => __( 'Nicaraguan Córdoba', 'wppayform' ),
            'NOK' => __( 'Norwegian Krone', 'wppayform' ),
            'NPR' => __( 'Nepalese Rupee', 'wppayform' ),
            'NZD' => __( 'New Zealand Dollar', 'wppayform' ),
            'PAB' => __( 'Panamanian Balboa', 'wppayform' ),
            'PEN' => __( 'Peruvian Nuevo Sol', 'wppayform' ),
            'PGK' => __( 'Papua New Guinean Kina', 'wppayform' ),
            'PHP' => __( 'Philippine Peso', 'wppayform' ),
            'PKR' => __( 'Pakistani Rupee', 'wppayform' ),
            'PLN' => __( 'Polish Złoty', 'wppayform' ),
            'PYG' => __( 'Paraguayan Guaraní', 'wppayform' ),
            'QAR' => __( 'Qatari Riyal', 'wppayform' ),
            'RON' => __( 'Romanian Leu', 'wppayform' ),
            'RSD' => __( 'Serbian Dinar', 'wppayform' ),
            'RUB' => __( 'Russian Ruble', 'wppayform' ),
            'RWF' => __( 'Rwandan Franc', 'wppayform' ),
            'SAR' => __( 'Saudi Riyal', 'wppayform' ),
            'SBD' => __( 'Solomon Islands Dollar', 'wppayform' ),
            'SCR' => __( 'Seychellois Rupee', 'wppayform' ),
            'SEK' => __( 'Swedish Krona', 'wppayform' ),
            'SGD' => __( 'Singapore Dollar', 'wppayform' ),
            'SHP' => __( 'Saint Helenian Pound', 'wppayform' ),
            'SLL' => __( 'Sierra Leonean Leone', 'wppayform' ),
            'SOS' => __( 'Somali Shilling', 'wppayform' ),
            'SRD' => __( 'Surinamese Dollar', 'wppayform' ),
            'STD' => __( 'São Tomé and Príncipe Dobra', 'wppayform' ),
            'SVC' => __( 'Salvadoran Colón', 'wppayform' ),
            'SZL' => __( 'Swazi Lilangeni', 'wppayform' ),
            'THB' => __( 'Thai Baht', 'wppayform' ),
            'TJS' => __( 'Tajikistani Somoni', 'wppayform' ),
            'TOP' => __( 'Tongan Paʻanga', 'wppayform' ),
            'TRY' => __( 'Turkish Lira', 'wppayform' ),
            'TTD' => __( 'Trinidad and Tobago Dollar', 'wppayform' ),
            'TWD' => __( 'New Taiwan Dollar', 'wppayform' ),
            'TZS' => __( 'Tanzanian Shilling', 'wppayform' ),
            'UAH' => __( 'Ukrainian Hryvnia', 'wppayform' ),
            'UGX' => __( 'Ugandan Shilling', 'wppayform' ),
            'USD' => __( 'United States Dollar', 'wppayform' ),
            'UYU' => __( 'Uruguayan Peso', 'wppayform' ),
            'UZS' => __( 'Uzbekistani Som', 'wppayform' ),
            'VND' => __( 'Vietnamese Đồng', 'wppayform' ),
            'VUV' => __( 'Vanuatu Vatu', 'wppayform' ),
            'WST' => __( 'Samoan Tala', 'wppayform' ),
            'XAF' => __( 'Central African Cfa Franc', 'wppayform' ),
            'XCD' => __( 'East Caribbean Dollar', 'wppayform' ),
            'XOF' => __( 'West African Cfa Franc', 'wppayform' ),
            'XPF' => __( 'Cfp Franc', 'wppayform' ),
            'YER' => __( 'Yemeni Rial', 'wppayform' ),
            'ZAR' => __( 'South African Rand', 'wppayform' ),
            'ZMW' => __( 'Zambian Kwacha', 'wppayform' ),
        );
    }

    /**
     * Get the available locales that Stripe can use
     *
     * @return array
     */
    public static function getLocales() {

        return array(
            ''     => __( 'English (en) (default)', 'wppayform' ),
            'auto' => __( 'Auto-detect locale', 'wppayform' ),
            'zh'   => __( 'Simplified Chinese (zh)', 'wppayform' ),
            'da'   => __( 'Danish (da)', 'wppayform' ),
            'nl'   => __( 'Dutch (nl)', 'wppayform' ),
            'fi'   => __( 'Finnish (fi)', 'wppayform' ),
            'fr'   => __( 'French (fr)', 'wppayform' ),
            'de'   => __( 'German (de)', 'wppayform' ),
            'it'   => __( 'Italian (it)', 'wppayform' ),
            'ja'   => __( 'Japanese (ja)', 'wppayform' ),
            'no'   => __( 'Norwegian (no)', 'wppayform' ),
            'es'   => __( 'Spanish (es)', 'wppayform' ),
            'sv'   => __( 'Swedish (sv)', 'wppayform' ),
        );
    }

    public static function getComponents()
    {
        $components = array();
        return apply_filters('wp_payment_form_components', $components);;
    }
}