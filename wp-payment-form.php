<?php
/**
 * Plugin Name: WPPayForm - Build Payment Forms and Accept Payment using Stripe
 * Plugin URI:  https://wpmanageninja.com/downloads/wppayform-pro-wordpress-payments-form-builder/
 * Description: Create and Accept Payments in minutes with Stripe, PayPal with built-in form builder
 * Author: WPManageNinja LLC
 * Author URI:  https://wpmanageninja.com
 * Version: 1.1.0
 * Text Domain: wppayform
 */

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright 2019 WPManageNinja LLC. All rights reserved.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WPPAYFORM_VERSION')) {
    define('WPPAYFORM_VERSION_LITE', true);
    define('WPPAYFORM_VERSION', '1.1.0');
    // Stripe API version should be in 'YYYY-MM-DD' format.
    define('WPPAYFORM_STRIPE_API_VERSION', '2018-10-31');
    define('WPPAYFORM_MAIN_FILE', __FILE__);
    define('WPPAYFORM_URL', plugin_dir_url(__FILE__));
    define('WPPAYFORM_DIR', plugin_dir_path(__FILE__));

    class WPPayForm
    {
        public function boot()
        {
            $this->loadDependecies();
            if (is_admin()) {
                $this->adminHooks();
            }
            $this->commonActions();
            $this->registerShortcodes();
            $this->loadComponents();
        }

        public function adminHooks()
        {
            // Init The Classes
            // Register Post Type
            new \WPPayForm\Classes\PostType();
            // Register Admin menu
            $menu = new \WPPayForm\Classes\Menu();
            $menu->register();

            add_action('wppayform/render_admin_app', function () {
                $adminApp = new \WPPayForm\Classes\AdminApp();
                $adminApp->bootView();
            });

            // Top Level Ajax Handlers
            $ajaxHandler = new \WPPayForm\Classes\AdminAjaxHandler();
            $ajaxHandler->registerEndpoints();

            // Submission Ajax Handler
            $submissionHandler = new \WPPayForm\Classes\SubmissionView();
            $submissionHandler->registerEndpoints();

            // General Settings Handler
            $globalSettingHandler = new \WPPayForm\Classes\GlobalSettingsHandler();
            $globalSettingHandler->registerHooks();

            // Handle Globla Tools
            $globalTools = new \WPPayForm\Classes\Tools\GlobalTools();
            $globalTools->registerEndpoints();

            // Handle Demo Forms
            $demoForms = new \WPPayForm\Classes\Tools\DemoForms();
            $demoForms->registerEndpoints();

            // init tinymce
            $tinyMCE = new \WPPayForm\Classes\Integrations\TinyMceBlock();
            $tinyMCE->register();

        }

        public function registerShortcodes()
        {
            // Register the shortcode
            add_shortcode('wppayform', function ($args) {
                $args = shortcode_atts(array(
                    'id'               => '',
                    'show_title'       => false,
                    'show_description' => false
                ), $args);

                if (!$args['id']) {
                    return;
                }

                $builder = new \WPPayForm\Classes\Builder\Render();
                return $builder->render($args['id'], $args['show_title'], $args['show_description']);
            });
            add_shortcode('wppayform_reciept', function () {
                if (isset($_REQUEST['wpf_submission']) && $_REQUEST['wpf_submission']) {
                    $submissionHash = sanitize_text_field($_REQUEST['wpf_submission']);
                    $submission = wpPayFormDB()->table('wpf_submissions')
                        ->where('submission_hash', '=', $submissionHash)
                        ->first();
                    if ($submission) {
                        $receiptHandler = new \WPPayForm\Classes\Builder\PaymentReceipt();
                        return $receiptHandler->render($submission->id);
                    } else {
                        return '<p class="wpf_no_recipt_found">' . __('Sorry, no submission receipt found, Please check your receipt URL', 'wppayform') . '</p>';
                    }
                } else {
                    return '<p class="wpf_no_recipt_found">' . __('Sorry, no submission receipt found, Please check your receipt URL', 'wppayform') . '</p>';
                }
            });
        }

        public function commonActions()
        {
            // Form Submission Handler
            $submissionHandler = new \WPPayForm\Classes\SubmissionHandler();
            add_action('wp_ajax_wpf_submit_form', array($submissionHandler, 'handeSubmission'));
            add_action('wp_ajax_nopriv_wpf_submit_form', array($submissionHandler, 'handeSubmission'));

            // Stripe Paument Method Init Here
            $stripe = new \WPPayForm\Classes\PaymentMethods\Stripe\Stripe();
            $stripe->registerHooks();

            // Handle Extorior Pages
            add_action('init', function () {
                $demoPage = new \WPPayForm\Classes\ProcessDemoPage();
                $demoPage->handleExteriorPages();
            });
        }

        public function loadComponents()
        {
            // Load Form Components
            new \WPPayForm\Classes\FormComponents\CustomerNameComponent();
            new \WPPayForm\Classes\FormComponents\CustomerEmailComponent();
            new \WPPayForm\Classes\FormComponents\TextComponent();
            new \WPPayForm\Classes\FormComponents\NumberComponent();
            new \WPPayForm\Classes\FormComponents\SelectComponent();
            new \WPPayForm\Classes\FormComponents\RadioComponent();
            new \WPPayForm\Classes\FormComponents\CheckBoxComponent();
            new \WPPayForm\Classes\FormComponents\TextAreaComponent();
            new \WPPayForm\Classes\FormComponents\HtmlComponent();
            new \WPPayForm\Classes\FormComponents\PaymentItemComponent();
            new \WPPayForm\Classes\FormComponents\ItemQuantityComponent();
            new \WPPayForm\Classes\FormComponents\DateComponent();
            new \WPPayForm\Classes\FormComponents\CustomAmountComponent();
            new \WPPayForm\Classes\FormComponents\ChoosePaymentMethodComponent();
            new \WPPayForm\Classes\FormComponents\HiddenInputComponent();
        }

        public function textDomain()
        {
            load_plugin_textdomain('wppayform', false, basename(dirname(__FILE__)) . '/languages');
        }

        public function loadDependecies()
        {
            require_once(WPPAYFORM_DIR . 'includes/autoload.php');
        }
    }

    add_action('plugins_loaded', function () {
        // Let's check again if Pro version is available or not
        if (defined('WPPAYFORM_PRO_INSTALLED')) {
            deactivate_plugins(plugin_basename(__FILE__));
        } else {
            (new WPPayForm())->boot();
        }
    });
    register_activation_hook(__FILE__, function ($newWorkWide) {
        require_once(WPPAYFORM_DIR . 'includes/Classes/Activator.php');
        $activator = new \WPPayForm\Classes\Activator();
        $activator->migrateDatabases($newWorkWide);
    });

    // Handle Newtwork new Site Activation
    add_action( 'wpmu_new_blog', function ($blogId) {
        require_once(WPPAYFORM_DIR . 'includes/Classes/Activator.php');
        switch_to_blog( $blogId );
        \WPPayForm\Classes\Activator::migrate();
        restore_current_blog();
    } );

} else {
    add_action('admin_init', function () {
        deactivate_plugins(plugin_basename(__FILE__));
    });
}


add_action( "ninja_table_before_render_table_source", function () {
    wp_dequeue_style( "footable_styles" ); // Remove The Syyles
    wp_dequeue_script( "footable" ); // Remove the Footable Library
    wp_dequeue_script( "footable_init" ); // Remove the Custom Scripts
    wp_dequeue_script( "ninja-tables-pro" ); // Remove Pro Version Script. It does not load always.
    wp_enqueue_style( "footable_custom_styles",'PATH_TO_YOUR_COPY_STYLE' );
}, 100 );
