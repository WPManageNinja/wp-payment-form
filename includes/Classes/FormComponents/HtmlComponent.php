<?php

namespace WPPayForm\Classes\FormComponents;

if (!defined('ABSPATH')) {
    exit;
}

class HtmlComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('custom_html', 20);
    }

    public function component()
    {
        return array(
            'type'            => 'custom_html',
            'editor_title'    => 'HTML Markup',
            'group'           => 'html',
            'postion_group'   => 'general',
            'editor_elements' => array(
                'custom_html' => array(
                    'label' => 'Custom HTML',
                    'type' => 'html',
                    'group' => 'general',
                    'info' => 'You can use the following dynamic placeholder on your HTML <span>{payment_total}</span> <span>{sub_total}</span> <span>{tax_total}</span>'
                ),
                'wrapper_class' => array(
                    'label' => 'Field Wrapper CSS Class',
                    'type'  => 'text',
                    'group' => 'advanced'
                )
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $this->renderHtmlContent($element, $form);
    }
}