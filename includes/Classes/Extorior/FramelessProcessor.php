<?php

namespace WPPayForm\Classes\Extorior;

use WPPayForm\Classes\Models\Forms;
use WPPayForm\Classes\View;

class FramelessProcessor
{
    public function init()
    {
        if (!isset($_GET['wpf_page']) || $_GET['wpf_page'] != 'frameless') {
            // It's not our page. so skip altogether
            return;
        }
        $action = sanitize_text_field($_REQUEST['wpf_action']);
        $this->processFramePage($action);
    }


    public function processFramePage($action = '')
    {
        if (!$action) {
            return;
        }
        status_header(200);
        do_action('wppayform/frameless_pre_render_page', $action);
        do_action('wppayform/frameless_pre_render_page_' . $action, $action);

        echo $this->getHeader($action);
        do_action('wppayform/frameless_body', $action);
        do_action('wppayform/frameless_body_'.$action, $action);
        echo $this->getFooter($action);
        exit(200);
    }

    private function getHeader($action)
    {
        $title = get_bloginfo('name');

        $title = __('Payment Success', 'wppayform') . ' - ' . $title;

        $title = apply_filters('wppayform/frameless_browser_title', $title, $action);

        $headerJsFiles = [
            includes_url('js/jquery/jquery.js?ver=1.12.4-wp'),
            includes_url('js/jquery/jquery-migrate.min.js?ver=1.4.1')
        ];

        $headerJsFiles = apply_filters('wppayform/frameless_header_scripts', $headerJsFiles, $action);

        $cssFiles = [
            WPPAYFORM_URL . 'assets/css/frameless.css'
        ];

        $cssFiles = apply_filters('wppayform/frameless_header_css_files', $cssFiles, $action);

        return View::make('frameless.header', [
            'css_files' => $cssFiles,
            'js_files'  => $headerJsFiles,
            'title'     => $title
        ]);

    }

    private function getFooter($action)
    {
        $footerJsFiles = [];
        $footerJsFiles = apply_filters('wppayform/frameless_header_scripts', $footerJsFiles, $action);

        return View::make('frameless.footer', [
            'js_files'  => $footerJsFiles,
            'action' => $action
        ]);

    }
}