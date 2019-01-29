<?php

namespace WPPayForm\Classes\Builder;

use WPPayForm\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax Handler Class
 * @since 1.0.0
 */
class Render
{
    public function render($formId)
    {
        $elements = Forms::getBuilderSettings($formId);
        $form = Forms::getForm($formId);
        if (!$form) {
            return;
        }

        $this->addAssets($form);

        ob_start();
        $this->renderFormHeader($form);
        if ($elements):
            foreach ($elements as $element) {
                do_action('wppayform_render_' . $element['type'], $element, $formId, $elements);
            }
        endif;
        $this->renderFormFooter($form);
        return ob_get_clean();
    }

    public function renderFormHeader($form)
    {
        global $wp;
        $currentUrl = home_url($wp->request);
        ?>
        <div class="wpf_form_wrapper wpf_form_wrapper_<?php echo $form->ID; ?>">
        <?php do_action('wpf_form_render_before', $form); ?>
        <form data-wpf_form_id="<?php echo $form->ID; ?>" class="wpf_form wpf_form_id_<?php echo $form->ID; ?>" method="post" action="<?php site_url(); ?>" id="wpf_form_id_<?php echo $form->ID; ?>">
        <input type="hidden" name="__wpf_form_id" value="<?php echo $form->ID; ?>"/>
        <input type="hidden" name="__wpf_current_url" value="<?php echo $currentUrl; ?>">
        <?php do_action('wpf_form_render_start_form', $form); ?>
        <?php
    }

    public function renderFormFooter($form)
    {
        ?>
        <?php do_action('wpf_form_render_before_submit_button', $form); ?>
        <button>Pay Money <span class="wpf_calc_payment_total"></span></button>
        <?php do_action('wpf_form_render_after_submit_button', $form); ?>
        </form>
        <?php do_action('wpf_form_render_after', $form); ?>
        </div>
        <?php
    }

    private function addAssets($form)
    {
        wp_enqueue_script('wppayform_public', WPPAYFORM_URL.'assets/js/payforms-public.js', array('jquery'), WPPAYFORM_VERSION, true);
        wp_localize_script('wppayform_public', 'wp_payform_'.$form->ID, array(
            'form' => $form
        ));
        wp_localize_script('wppayform_public', 'wp_payform_general', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }
}