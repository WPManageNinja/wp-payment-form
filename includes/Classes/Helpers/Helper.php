<?php

namespace WPPayForm\Classes\Helpers;

use WPPayForm\Framework\Helpers\ArrayHelper;

class Helper
{
    static $tabIndex = 0;

    static $formInstance = 0;

    static $loadedForms = [];

    static $tabIndexStatus = 'na';

    /**
     * Sanitize form inputs recursively.
     *
     * @param $input
     *
     * @return string $input
     */
    public static function sanitizer($input, $attribute = null, $fields = [])
    {
        if (is_string($input)) {
            if (ArrayHelper::get($fields, $attribute . '.element') === 'textarea') {
                $input = sanitize_textarea_field($input);
            } else {
                $input = sanitize_text_field($input);
            }
        } elseif (is_array($input)) {
            foreach ($input as $key => &$value) {
                $attribute = $attribute ? $attribute . '[' . $key . ']' : $key;

                $value = Helper::sanitizer($value, $attribute, $fields);

                $attribute = null;
            }
        }
        return $input;
    }

    public static function makeMenuUrl($page = 'fluent_forms_settings', $component = null)
    {
        $baseUrl = admin_url('admin.php?page=' . $page);

        $hash = ArrayHelper::get($component, 'hash', '');
        if ($hash) {
            $baseUrl = $baseUrl . '#' . $hash;
        }

        $query = ArrayHelper::get($component, 'query');

        if ($query) {
            $paramString = http_build_query($query);
            if ($hash) {
                $baseUrl .= '?' . $paramString;
            } else {
                $baseUrl .= '&' . $paramString;
            }
        }

        return $baseUrl;

    }

    public static function getHtmlElementClass($value1, $value2, $class = 'active', $default = '')
    {
        return $value1 === $value2 ? $class : $default;
    }

    /**
     * Determines if the given string is a valid json.
     *
     * @param $string
     *
     * @return bool
     */
    public static function isJson($string)
    {
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }


    public static function isSlackEnabled()
    {
        $globalModules = get_option('wppayform_global_modules_status');
        return $globalModules && isset($globalModules['slack']) && $globalModules['slack'] == 'yes';
    }

    public static function getEntryStutuses($form_id = false)
    {
        $statuses = apply_filters('fluentform_entry_statuses', [
            'unread' => 'Unread',
            'read'   => 'Read'
        ], $form_id);
        $statuses['trashed'] = 'Trashed';
        return $statuses;
    }

    public static function getReportableInputs()
    {
        return apply_filters('fluentform_reportable_inputs', [
            'select',
            'input_radio',
            'input_checkbox',
            'ratings',
            'net_promoter',
            'select_country',
            'net_promoter_score'
        ]);
    }

    public static function getSubFieldReportableInputs()
    {
        return apply_filters('fluentform_subfield_reportable_inputs', [
            'tabular_grid'
        ]);
    }

    public static function getFormMeta($formId, $metaKey, $default = '')
    {
        $meta = wpFluent()->table('fluentform_form_meta')
            ->where('meta_key', $metaKey)
            ->where('form_id', $formId)
            ->first();

        if (!$meta || !$meta->value) {
            return $default;
        }

        $metaValue = $meta->value;
        // decode the JSON data
        $result = json_decode($metaValue, true);


        if (json_last_error() == JSON_ERROR_NONE) {
            return $result;
        }
        return $metaValue;
    }

    public static function setFormMeta($formId, $metaKey, $value)
    {
        $meta = wpFluent()->table('fluentform_form_meta')
            ->where('meta_key', $metaKey)
            ->where('form_id', $formId)
            ->first();

        if (!$meta) {
            $insetid = wpFluent()->table('fluentform_form_meta')
                ->insert([
                    'meta_key' => $metaKey,
                    'form_id'  => $formId,
                    'value'    => json_encode($value)
                ]);

            return $insetid;

        } else {

            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }

            wpFluent()->table('fluentform_form_meta')
                ->where('id', $meta->id)
                ->update([
                    'value' => $value
                ]);
        }

        return $meta->id;
    }

    public static function isEntryAutoDeleteEnabled($formId)
    {
        $settings = wpFluent()->table('fluentform_form_meta')
            ->where('form_id', $formId)
            ->where('meta_key', 'formSettings')
            ->first();

        if (!$settings) {
            return false;
        }

        $formSettings = json_decode($settings->value, true);
        
        if ($formSettings && ArrayHelper::get($formSettings, 'delete_entry_on_submission') == 'yes') {
            return true;
        }

        return false;
    }

    public static function formExtraCssClass($form)
    {
        if(!$form->settings) {
            $settings = wpFluent()->table('fluentform_form_meta')
                ->where('form_id', $form->id)
                ->where('meta_key', 'formSettings')
                ->first();
                
            $formSettings = json_decode($settings->value, true);
        } else {
            $formSettings = $form->settings;
        }

        if (!$formSettings) {
            return '';
        }


        if ($formSettings && $extraClass = ArrayHelper::get($formSettings, 'form_extra_css_class')) {
            return esc_attr($extraClass);
        }

        return '';
    }

    public static function getNextTabIndex($increment = 1)
    {
        if(self::isTabIndexEnabled()) {
            static::$tabIndex += $increment;
            return static::$tabIndex;
        }
        return '';
    }

    public static function getFormInstaceClass($formId)
    {
        static::$formInstance += 1;
        return  'ff_form_instance_'.$formId.'_'.static::$formInstance;
    }

    public static function resetTabIndex()
    {
        static::$tabIndex = 0;
    }


    public static function isFluentAdminPage()
    {
        $fluentPages = [
            'fluent_forms',
            'fluent_forms_transfer',
            'fluent_forms_settings',
            'fluent_form_add_ons',
            'fluent_forms_docs'
        ];

        $status = true;
        
        if (!isset($_GET['page']) || !in_array($_GET['page'], $fluentPages)) {
            $status = false;
        }

        return apply_filters('fluentform_is_admin_page', $status);
    }


    public static function getShortCodeIds($content, $tag = 'fluentform', $selector = 'id') {

        if (false === strpos($content, '[')) {
            return [];
        }

        preg_match_all('/' . get_shortcode_regex() . '/', $content, $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            return [];
        }

        $ids = [];

        foreach ($matches as $shortcode) {
            if (count($shortcode) >= 2 && $tag === $shortcode[2]) {
                // Replace braces with empty string.
                $parsedCode = str_replace(['[', ']', '&#91;', '&#93;'], '', $shortcode[0]);

                $result = shortcode_parse_atts($parsedCode);

                if (!empty($result[$selector])) {
                    $ids[$result[$selector]] = $result[$selector];
                }
            }
        }

        return $ids;
    }

    public static function isTabIndexEnabled()
    {
        if(static::$tabIndexStatus == 'na') {
					$globalSettings = get_option('_fluentform_global_form_settings');
					static::$tabIndexStatus = ArrayHelper::get($globalSettings, 'misc.tabIndex') == 'yes';
        }
        return static::$tabIndexStatus;
    }

    public static function isMultiStepForm($formId)
    {
        $form = wpFluent()->table('fluentform_forms')->find($formId);
        $fields = json_decode($form->form_fields, true);

        if(ArrayHelper::get($fields, 'stepsWrapper')) {
            return true;
        }
        return false;
    }

    public static function hasFormElement($formId, $elementName)
    {
        $form = wpFluent()->table('fluentform_forms')->find($formId);
        $fieldsJson = $form->form_fields;
        return strpos($fieldsJson, '"element":"'.$elementName.'"') != false;

    }

}