<?php

namespace WPPayForm\Classes\FormComponents;

if (!defined('ABSPATH')) {
    exit;
}

class HtmlComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('custom_html', 18);
    }

    public function component()
    {
        return array(
            'type'            => 'custom_html',
            'editor_title'    => 'Custom HTML',
            'group'           => 'html',
            'editor_elements' => array(
                'custom_html' => array(
                    'label' => 'Custom HTML',
                    'type' => 'html'
                )
            )
        );
    }

    public function render($element, $formId, $elements)
    {
        $this->renderHtmlContent($element, $formId);
    }
}