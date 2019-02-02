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
        <form data-stripe_pub_key="<?php echo wpfGetStripePubKey(); ?>" data-wpf_form_id="<?php echo $form->ID; ?>" class="wpf_form wpf_strip_default_style wpf_form_id_<?php echo $form->ID; ?>" method="POST" action="<?php site_url(); ?>" id="wpf_form_id_<?php echo $form->ID; ?>">
        <input type="hidden" name="__wpf_form_id" value="<?php echo $form->ID; ?>"/>
        <input type="hidden" name="__wpf_current_url" value="<?php echo $currentUrl; ?>">
        <?php do_action('wpf_form_render_start_form', $form); ?>
        <?php
    }

    public function renderFormFooter($form)
    {
        $submitButton = Forms::getButtonSettings($form->ID);
        $processingText = $submitButton['processing_text'];
        if(!$processingText) {
            $processingText  = __('Please Wait…', 'wpfluentform');
        }
        $button_text = $submitButton['button_text'];
        if(!$button_text) {
            $button_text = __('Submit', 'wpfluentform');
        }
        ?>
        <?php do_action('wpf_form_render_before_submit_button', $form); ?>
        <br/>
        <button class="wpf_submit_button <?php echo $submitButton['css_class']; ?> <?php echo $submitButton['button_style']; ?>"
                id="stripe_form_submit_<?php echo $form->ID; ?>">
            <span class="wpf_txt_normal"><?php echo $this->parseText($button_text, $form->ID); ?></span>
            <span style="display: none;" class="wpf_txt_loading"><?php echo $this->parseText($submitButton['processing_text'], $form->ID); ?></span>
        </button>
        <?php do_action('wpf_form_render_after_submit_button', $form); ?>
        </form>
        <div style="display: none" class="wpf_form_notices wpf_form_errors"></div>
        <div style="display: none" class="wpf_form_notices wpf_form_success"></div>
        <?php do_action('wpf_form_render_after', $form); ?>
        </div>
        <?php
    }

    private function addAssets($form)
    {
        $currencySettings = Forms::getCurrencyAndLocale( $form->ID );
        wp_enqueue_script('wppayform_public', WPPAYFORM_URL . 'assets/js/payforms-public.js', array('jquery'), WPPAYFORM_VERSION, true);
        wp_enqueue_style('wppayform_public', WPPAYFORM_URL . 'assets/css/payforms-public.css', array(), WPPAYFORM_VERSION);
        wp_localize_script('wppayform_public', 'wp_payform_' . $form->ID, array(
            'form_id' => $form->ID,
            'currency_settings' => $currencySettings
        ));
        wp_localize_script('wppayform_public', 'wp_payform_general', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }

    private function parseText($text, $formId)
    {
        return str_replace(
            array(
                '{sub_total}',
                '{tax_total}',
                '{payment_total}'
            ),
            array(
                '<span class="wpf_calc_sub_total"></span>',
                '<span class="wpf_calc_tax_total"></span>',
                '<span class="wpf_calc_payment_total"></span>',
            ),
            $text
        );
    }
}