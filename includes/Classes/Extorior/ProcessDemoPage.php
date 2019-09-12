<?php

namespace WPPayForm\Classes\Extorior;

use WPPayForm\Classes\AccessControl;
use WPPayForm\Classes\Models\Forms;

class ProcessDemoPage
{
    public function handleExteriorPages()
    {
        if (isset($_GET['wp_paymentform_preview']) && $_GET['wp_paymentform_preview']) {
            $hasDemoAccess = AccessControl::hasTopLevelMenuPermission();
            $hasDemoAccess = apply_filters('wppayform/can_see_demo_form', $hasDemoAccess);
            if($hasDemoAccess) {
                $formId = intval($_GET['wp_paymentform_preview']);
                $this->loadDefaultPageTemplate();
                $this->renderPreview($formId);
            }
        }
    }

    public function renderPreview($formId)
    {
        $form = Forms::getForm($formId);
        if ($form) {
            add_action('pre_get_posts', array($this, 'pre_get_posts'), 100, 1);
            add_filter('post_thumbnail_html', '__return_empty_string');
            add_filter('get_the_excerpt', function ($content) use ($form) {
                if (in_the_loop()) {
                    $content = '<div style="text-align: center" class="demo"><h4>WP PayForm Demo Preview ( From ID: ' . $form->ID . ' )</h4></div><hr />';
                    $content .= do_shortcode('[wppayform id="' . $form->ID . '"]');
                }
                return $content;
            },999, 1);
            add_filter('the_title', function ($title) use ($form) {
                if (in_the_loop()) {
                    return $form->post_title;
                }
                return $title;
            }, 100, 1);
            add_filter('the_content', function ($content) use ($form) {
                if (in_the_loop()) {
                    $content = '<div style="text-align: center" class="demo"><h4>WP PayForm Demo Preview ( From ID: ' . $form->ID . ' )</h4></div><hr />';
                    $content .= do_shortcode('[wppayform id="' . $form->ID . '"]');
                }
                return $content;
            },999,1);
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