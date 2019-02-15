<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

class CustomerNameComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('customer_name', 13);
    }

    public function component()
    {
        return array(
            'type'            => 'customer_name',
            'editor_title'    => 'Customer Name',
            'group'           => 'input',
            'postion_group'   => 'general',
            'editor_elements' => array(
                'label'         => array(
                    'label' => 'Field Label',
                    'type'  => 'text'
                ),
                'placeholder'   => array(
                    'label' => 'Placeholder',
                    'type'  => 'text'
                ),
                'required'      => array(
                    'label' => 'Required',
                    'type'  => 'switch'
                ),
                'default_value' => array(
                    'label' => 'Default Value',
                    'type'  => 'text'
                ),
            ),
            'field_options'   => array(
                'label' => 'Your Name',
                'placeholder' => 'Name',
                'required' => 'yes'
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $element['type'] = 'text';
        $defaultValue = ArrayHelper::get($element, 'field_options.default_value');
        if($defaultValue && strpos($defaultValue, '{current_user.display_name}') !== false) {
            $currentUserId = get_current_user_id();
            $replaceValue = '';
            if($currentUserId) {
                $currentUser = get_user_by('ID', $currentUserId);
                $replaceValue = $currentUser->display_name;
            }
            $element['field_options']['default_value'] = str_replace('{current_user.display_name}', $replaceValue, $defaultValue);
        }

        $this->renderNormalInput($element, $form);
    }
}