<?php
/**
 * Plugin Name: WPPayForm
 * Plugin URI:  https://wpmanageninja.com/downloads/wppayform-pro-wordpress-payments-form-builder/
 * Description: Create and Accept Payments in minutes with Stripe, PayPal with built-in form builder
 * Author: WPManageNinja LLC
 * Author URI:  https://wpmanageninja.com
 * Version: 1.9.3
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
    define('WPPAYFORM_VERSION', '1.9.3');
    define('WPPAYFORM_DB_VERSION', 120);
    // Stripe API version should be in 'YYYY-MM-DD' format.
    define('WPPAYFORM_STRIPE_API_VERSION', '2019-05-16');
    define('WPPAYFORM_MAIN_FILE', __FILE__);
    define('WPPAYFORM_URL', plugin_dir_url(__FILE__));
    define('WPPAYFORM_DIR', plugin_dir_path(__FILE__));
    if(!defined('WPPAYFORM_UPLOAD_DIR')) {
        define('WPPAYFORM_UPLOAD_DIR', '/wppayform');
    }

    class WPPayForm
    {
        public function boot()
        {
            $this->textDomain();
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

            // Dashboard Widget Here
            $dashboardWidget = new \WPPayForm\Classes\DashboardWidgetModule();
            $dashboardWidget->register();

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
            add_shortcode('wppayform_reciept', function ($atts) {

                $args = shortcode_atts( array(
                    'hash' => ''
                ), $atts, 'wppayform_reciept' );

                if(!$args['hash']) {
                    $hash = \WPPayForm\Classes\ArrayHelper::get($_REQUEST, 'wpf_submission');
                    if(!$hash) {
                        $hash = \WPPayForm\Classes\ArrayHelper::get($_REQUEST, 'wpf_hash');
                    }
                } else {
                    $hash = $args['hash'];
                }

                if ($hash) {
                    $submission = wpPayFormDB()->table('wpf_submissions')
                        ->where('submission_hash', '=', $hash)
                        ->first();

                    if ($submission) {
                        $receiptHandler = new \WPPayForm\Classes\Builder\PaymentReceipt();
                        return $receiptHandler->render($submission->id);
                    }
                }

                return '<p class="wpf_no_recipt_found">' . __('Sorry, no submission receipt found, Please check your receipt URL', 'wppayform') . '</p>';

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

            // Stripe Inline Handler
            $stripeInlineHandler = new \WPPayForm\Classes\PaymentMethods\Stripe\StripeInlineHandler();
            $stripeInlineHandler->registerHooks();

            // Stripe Hosted Checkout Handler
            $stripeHostedHandler = new \WPPayForm\Classes\PaymentMethods\Stripe\StripeHostedHandler();
            $stripeHostedHandler->registerHooks();

            // Handle Extorior Pages
            add_action('init', function () {
                $demoPage = new \WPPayForm\Classes\Extorior\ProcessDemoPage();
                $demoPage->handleExteriorPages();

                $frameLessPage = new \WPPayForm\Classes\Extorior\FramelessProcessor();
                $frameLessPage->init();
            });
        }

        public function loadComponents()
        {
            require_once WPPAYFORM_DIR . 'includes/Classes/FormComponents/init.php';
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