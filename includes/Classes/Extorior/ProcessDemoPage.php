<?php

namespace WPPayForm\Classes\Extorior;

use WPPayForm\Classes\AccessControl;
use WPPayForm\Classes\Models\Forms;
use WPPayForm\Classes\View;

class ProcessDemoPage
{
    public function handleExteriorPages()
    {
        if (defined('CT_VERSION')) {
            // oxygen page compatibility
            remove_action('wp_head', 'oxy_print_cached_css', 999999);
        }

        if (isset($_GET['wp_paymentform_preview']) && $_GET['wp_paymentform_preview']) {
            $hasDemoAccess = AccessControl::hasTopLevelMenuPermission();
            $hasDemoAccess = apply_filters('wppayform/can_see_demo_form', $hasDemoAccess);
            if ($hasDemoAccess) {
                $formId = intval($_GET['wp_paymentform_preview']);
                wp_enqueue_style('dashicons');
                $this->loadDefaultPageTemplate();
                $this->renderPreview($formId);
            }
        }
    }

    public function renderPreview($formId)
    {
        $form = Forms::getForm($formId);
        if ($form) {
            echo View::make('admin.show_review', [
                'form_id' => $formId,
                'form' => $form
            ]);
            exit();
        }
    }

    private function loadDefaultPageTemplate()
    {
        add_filter('template_include', function ($original) {
            return locate_template(array('page.php', 'single.php', 'index.php'));
        }, 999);
    }

    /**
     * Set the posts to one
     *
     * @param  WP_Query $query
     *
     * @return void
     */
    public function pre_get_posts($query)
    {
        if ($query->is_main_query()) {
            $query->set('posts_per_page', 1);
            $query->set('ignore_sticky_posts', true);
        }
    }

}