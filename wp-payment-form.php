<?php
/**
 * Plugin Name: WP Payment Form
 * Plugin URI:  https://github.com/WPManageNinja/wp-payment-form
 * Description: Create and Accept Payments in minutes with Stripe
 * Author: WPManageNinja LLC
 * Author URI:  https://wpmanageninja.com
 * Version: 1.0.0
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
 * Copyright 2014-2018 Moonstone Media Group. All rights reserved.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPPAYFORM_VERSION', '1.0.0');
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

        // init tinymce
        $tinyMCE = new \WPPayForm\Classes\Integrations\TinyMceBlock();
        $tinyMCE->register();

    }

    public function registerShortcodes()
    {
        // Register the shortcode
        add_shortcode('wppayform', function ($args) {
            $args = shortcode_atts(array(
                'id' => '',
                'show_title' => false,
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
                $submission = wpPayformDB()->table('wpf_submissions')
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
        $submissionHandler->registerActions();

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
    }

    public function textDomain()
    {
        load_plugin_textdomain( 'wppayform', false, basename( dirname( __FILE__ ) ) . '/languages' );
    }

    public function loadDependecies()
    {
        require_once(WPPAYFORM_DIR . 'includes/autoload.php');
        if(file_exists(WPPAYFORM_DIR.'includes/Pro/init.php')) {
            require_once WPPAYFORM_DIR.'includes/Pro/init.php';
        }
    }
}

add_action('plugins_loaded', function () {
    (new WPPayForm())->boot();
});

register_activation_hook(__FILE__, function ($newWorkWide) {
    require_once(WPPAYFORM_DIR . 'includes/Classes/Activator.php');
    $activator = new \WPPayForm\Classes\Activator();
    $activator->migrateDatabases($newWorkWide);
});

// Development Purpose only
add_action('shutdown', 'sql_logger');
function sql_logger()
{
    return;
    global $wpdb;
    $log_file = fopen(ABSPATH . '/sql_log.txt', 'a');
    fwrite($log_file, "//////////////////////////////////////////\n\n" . date("F j, Y, g:i:s a") . "\n");
    foreach ($wpdb->queries as $q) {
        if (strpos($q[0], 'wp_wpf_') != false || strpos($q[0], 'pay') != false) {
            fwrite($log_file, $q[0] . " - ($q[1] s)" . " [Stack]: $q[2]" . "\n\n");
        }
    }
    fclose($log_file);
}

function wpf_logger($data) {
    $log_file = fopen(ABSPATH . '/ipn_log.txt', 'a');
    fwrite($log_file, "//////////////////////////////////////////\n\n" . date("F j, Y, g:i:s a") . "\n");
    fwrite($log_file, json_encode($data));
    fclose($log_file);
}
