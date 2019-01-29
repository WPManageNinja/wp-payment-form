<?php
/**
 * Plugin Name: WP Pay Form - Simply Accept Payment using Stripe
 * Plugin URI:  https://wpmanageninja.com
 * Description: Create and Accept Payments in minutes with Stripe
 * Author: WPManageNinja
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

        add_action('wppayform_render_admin_app', function () {
            $adminApp = new \WPPayForm\Classes\AdminApp();
            $adminApp->bootView();
        });

        // Ajax Handler
        new \WPPayForm\Classes\AdminAjaxHandler();
    }

    public function commonActions()
    {
        // Register the shortcode
        add_shortcode('wp_payment_form', function ($args) {
            $args = shortcode_atts(array(
                'id' => ''
            ), $args);
            if (!$args['id']) {
                return;
            }
            $builder = new \WPPayForm\Classes\Builder\Render();
            return $builder->render($args['id']);
        });

        // Form Submission Handler
        $submissionHandler = new \WPPayForm\Classes\SubmissionHandler();
        $submissionHandler->registerActions();

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
    }

    public function loadDependecies()
    {
        require_once(WPPAYFORM_DIR . 'includes/autoload.php');
        require_once( WPPAYFORM_DIR . 'vendor/autoload.php' );
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