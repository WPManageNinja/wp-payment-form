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
        if (!$capability) {
            return;
        }
        global $submenu;
        add_menu_page(
            __( 'Payment Forms', 'wppayform' ),
            __( 'Payment Forms', 'wppayform' ),
            $capability,
            'wppayform.php',
            array($this, 'render'),
            '',
            6
        );
        $submenu['wppayform.php']['all_forms'] = array(
            __('All Forms', 'wppayform'),
            $capability,
            'admin.php?page=wppayform.php#/',
        );
        $submenu['wppayform.php']['entries'] = array(
            __('Entries', 'wppayform'),
            $capability,
            'admin.php?page=wppayform.php#/entries',
        );
        $submenu['wppayform.php']['settings'] = array(
            __('Settings', 'wppayform'),
            $capability,
            'admin.php?page=wppayform.php#/settings/stripe-settings',
        );
        $submenu['wppayform.php']['support'] = array(
            __('Support', 'wppayform'),
            $capability,
            'admin.php?page=wppayform.php#/support',
        );
    }

    public function render() {
        do_action('wppayform_render_admin_app');
    }
}