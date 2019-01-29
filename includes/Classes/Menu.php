<?php

namespace WPPayForm\Classes;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Menu and Admin Pages
 * @since 1.0.0
 */
class Menu
{
    public function register()
    {
        add_action( 'admin_menu', array($this, 'addMenus') );
    }


    public function addMenus()
    {
        $capability = 'manage_options';
        add_menu_page(
            __( 'Payment Forms', 'wppayform' ),
            __( 'Payment Forms', 'wppayform' ),
            $capability,
            'wppayform.php',
            array($this, 'render'),
            '',
            6
        );
    }

    public function render() {
        do_action('wppayform_render_admin_app');
    }
}