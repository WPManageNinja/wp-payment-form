<?php
namespace WPPayForm\Classes\Builder;

class Helper
{
    static $formInstance = 0;

    public static function getFormInstaceClass($formId)
    {
        static::$formInstance += 1;
        return 'wpf_form_instance_' . $formId . '_' . static::$formInstance;
    }
}
