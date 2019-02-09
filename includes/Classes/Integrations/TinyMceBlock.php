<?php

namespace WPPayForm\Classes\Integrations;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add Button To TinyMCE Editor
 *
 * @since 1.0.0
 */
class TinyMceBlock
{
    public function register()
    {
        $pages_with_editor_button = array('post.php', 'post-new.php');
        foreach ($pages_with_editor_button as $editor_page) {
            add_action("load-{$editor_page}", array($this, 'registerButton'));
        }
    }

    public function registerButton()
    {
        // Check if the logged in WordPress User can edit Posts or Pages
        // If not, don't register our TinyMCE plugin
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
            return;
        }

        // Check if the logged in WordPress User has the Visual Editor enabled
        // If not, don't register our TinyMCE plugin
        if (get_user_option('rich_editing') !== 'true') {
            return;
        }

        // We are adding localized vars here
        wp_localize_script('jquery','wpf_tinymce_vars', array(
                "label" => __('Select a Form to insert', 'wppayform'),
                "title" => __('Insert Form Shortcode', 'wppayform'),
                "select_error" => __('Please select a Form', 'wppayform'),
                "insert_text" => __('Insert Shortcode', 'wppayform'),
                "forms" => $this->getAllFormsForMce(),
        ));

        // Setup filters
        add_filter('mce_external_plugins', array(&$this, 'addTinymcePlugin'));
        add_filter('mce_buttons', array(&$this, 'addTinymceToolbarButton'));
    }

    public function addTinymcePlugin($plugin_array)
    {
        $plugin_array['wpf_mce_payment_button'] = WPPAYFORM_URL . 'assets/js/tinymce.js';
        return $plugin_array;
    }

    public function addTinymceToolbarButton($buttons)
    {
        array_push($buttons, '|', 'wpf_mce_payment_button');
        return $buttons;
    }

    private function getAllFormsForMce()
    {
        $args = array(
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_type' => 'wp_payform',
            'post_status' => 'any'
        );

        $tables = get_posts($args);
        $formatted = array();
        foreach ($tables as $table) {
            $formatted[] = array(
                'text' => $table->post_title .' ('.$table->ID.')',
                'value' => $table->ID
            );
        }
        return $formatted;
    }
}