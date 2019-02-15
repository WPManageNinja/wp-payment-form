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
                    'type' => 'html'
                )
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $this->renderHtmlContent($element, $form);
    }
}