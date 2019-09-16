<?php

namespace WPPayForm\Classes\Builder;

use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\GeneralSettings;
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
    public function render($formId, $show_title = false, $show_description = false)
    {
        $form = Forms::getForm($formId);

        if (!$form) {
            return;
        }

        if ($show_title) {
            $form->show_title = $show_title;
        }
        if ($show_description) {
            $form->show_description = $show_description;
        }
        if (!$show_title || !$show_description) {
            $titleDescription = get_post_meta($formId, 'wppayform_show_title_description', true);
            $form->show_title = $titleDescription;
            $form->show_description = $titleDescription;
        }
        $form->scheduleing_settings = Forms::getSchedulingSettings($formId);

        $elements = Forms::getBuilderSettings($formId);
        $form->designSettings = Forms::getDesignSettings($formId);
        $form->asteriskPosition = $form->designSettings['asteriskPlacement'];

        $form->recaptchaType = Forms::recaptchaType($form->ID);

        if($form->recaptchaType) {
            $recaptchaSettings = GeneralSettings::getRecaptchaSettings();
            $form->recaptcha_site_key = $recaptchaSettings['site_key'];
        }

        $this->registerScripts($form);

        ob_start();
        if ($elements):
            foreach ($elements as $element) {
                do_action('wppayform/render_component_' . $element['type'], $element, $form, $elements);
            }
            $form_body = ob_get_clean();
        endif;

        ob_start();
        $this->renderFormHeader($form);
        $header_html = ob_get_clean();
        ob_start();
        $this->renderFormFooter($form);
        $formFooter = ob_get_clean();

        $this->addAssets($form);

        $html = $header_html . $form_body . $formFooter;

        return apply_filters('wppayform/rendered_form_html', $html, $form);
    }

    public function renderFormHeader($form)
    {
        global $wp;
        $currentUrl = home_url(add_query_arg($_GET, $wp->request));;
        $labelPlacement = $form->designSettings['labelPlacement'];
        $btnPosition = ArrayHelper::get($form->designSettings, 'submit_button_position');

        $extraCssClasses = array_keys(array_filter($form->designSettings['extra_styles'], function ($value) {
            return $value == 'yes';
        }));

        $css_classes = array(
            'wpf_form',
            'wpf_strip_default_style',
            'wpf_form_id_' . $form->ID,
            'wpf_label_' . $labelPlacement,
            'wpf_asterisk_' . $form->asteriskPosition,
            'wpf_submit_button_pos_' . $btnPosition
        );

        if($form->recaptchaType) {
            $css_classes[] = 'wpf_has_recaptcha wpf_recaptcha_'.$form->recaptchaType;
        }

        $css_classes = array_merge($css_classes, $extraCssClasses);

        if ($labelPlacement != 'top') {
            $css_classes[] = 'wpf_inline_labels';
        }

        $css_classes = apply_filters('wppayform/form_css_classes', $css_classes, $form);

        $formAttributes = array(
            'data-wpf_form_id' => $form->ID,
            'class'            => implode(' ', $css_classes),
            'method'           => 'POST',
            'action'           => site_url(),
            'id'               => "wpf_form_id_" . $form->ID
        );

        if($form->recaptchaType) {
            $formAttributes['data-recaptcha_site_key'] = $form->recaptcha_site_key;
            if($form->recaptchaType == 'v2_visible') {
                $formAttributes['data-recaptcha_version'] = 'v2';
            } else {
                $formAttributes['data-recaptcha_version'] = 'v3';
            }
        }

        $formAttributes = apply_filters('wppayform/form_attributes', $formAttributes, $form);
        $formWrapperClasses = apply_filters('wppayform/form_wrapper_css_classes', array(
            'wpf_form_wrapper',
            'wpf_form_wrapper_' . $form->ID
        ), $form);
        ?>
        <div class="<?php echo implode(' ', $formWrapperClasses); ?>">
        <?php if ($form->show_title == 'yes'): ?>
        <h3 class="wp_form_title"><?php echo $form->post_title; ?></h3>
    <?php endif; ?>
        <?php if ($form->show_description == 'yes'): ?>
        <div class="wpf_form_description">
            <?php echo do_shortcode($form->post_content); ?>
        </div>
    <?php endif; ?>
        <?php do_action('wppayform/form_render_before', $form); ?>
        <form <?php echo $this->builtAttributes($formAttributes); ?>>
        <input type="hidden" name="__wpf_form_id" value="<?php echo $form->ID; ?>"/>
        <input type="hidden" name="__wpf_current_url" value="<?php echo $currentUrl; ?>">
        <input type="hidden" name="__wpf_current_page_id" value="<?php echo get_the_ID(); ?>">
        <?php do_action('wppayform/form_render_start_form', $form); ?>
        <?php
    }

    public function renderFormFooter($form)
    {
        $submitButton = Forms::getButtonSettings($form->ID);
        $processingText = $submitButton['processing_text'];
        if (!$processingText) {
            $processingText = __('Please Wait…', 'wpfluentform');
        }
        $button_text = $submitButton['button_text'];
        if (!$button_text) {
            $button_text = __('Submit', 'wpfluentform');
        }
        $buttonClasses = array(
            'wpf_submit_button',
            $submitButton['css_class'],
            $submitButton['button_style']
        );
        $buttonAttributes = apply_filters('wppayform/submit_button_attributes', array(
            'id'    => 'stripe_form_submit_' . $form->ID,
            'class' => implode(' ', array_unique($buttonClasses))
        ), $form);
        ?>
        <?php do_action('wppayform/form_render_before_submit_button', $form); ?>

        <?php if($form->recaptchaType): ?>
            <div class="wpf_form_group wpf_form_recaptcha">
                <div id="wpf_recaptcha_<?php echo $form->ID; ?>"></div>
            </div>
        <?php endif; ?>

        <div class="wpf_form_group wpf_form_submissions">
            <button <?php echo $this->builtAttributes($buttonAttributes); ?>>
                <span class="wpf_txt_normal"><?php echo $this->parseText($button_text, $form->ID); ?></span>
                <span style="display: none;" class="wpf_txt_loading">
                    <?php echo $this->parseText($processingText, $form->ID); ?>
                </span>
            </button>
            <div class="wpf_loading_svg">
                <svg version="1.1" id="loader-1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="30px" height="30px" viewBox="0 0 40 40" enable-background="new 0 0 40 40" xml:space="preserve"><path opacity="0.2" fill="#000" d="M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946 s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634 c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z"/><path fill="#000" d="M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0 C22.32,8.481,24.301,9.057,26.013,10.047z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 20 20" to="360 20 20" dur="0.5s" repeatCount="indefinite"/></path></svg>
            </div>
        </div>
        <?php do_action('wppayform/form_render_after_submit_button', $form); ?>
        </form>
        <div style="display: none" class="wpf_form_notices"></div>
        <?php do_action('wppayform/form_render_after', $form); ?>
        <?php do_action('wppayform/form_render_after_' . $form->ID, $form); ?>
        </div>

        <?php
            if($form->recaptchaType) {
                if(!did_action('wpf_added_recaptcha_script')) {

                    if($form->recaptchaType == 'v3_invisible') {
                        $key = $form->recaptcha_site_key;
                        $src = 'https://www.google.com/recaptcha/api.js?render='.$key.'&onload=wpf_onload_recaptcha_callback';
                    } else {
                        $src = 'https://www.google.com/recaptcha/api.js?onload=wpf_onload_recaptcha_callback&render=explicit';
                    }

                    add_action('wp_footer', function () use ($src) {
                    ?>
                        <script src="<?php echo $src; ?>" async defer></script>
                     <?php }, 11);
                     do_action('wpf_added_recaptcha_script');
                }
            }
    }

    private function addAssets($form)
    {
        $currencySettings = Forms::getCurrencyAndLocale($form->ID);
        wp_enqueue_script('wppayform_public', WPPAYFORM_URL . 'assets/js/payforms-publicv2.js', array('jquery'), WPPAYFORM_VERSION, true);
        wp_enqueue_style('wppayform_public', WPPAYFORM_URL . 'assets/css/payforms-public.css', array(), WPPAYFORM_VERSION);
        wp_localize_script('wppayform_public', 'wp_payform_' . $form->ID, apply_filters('wppayform/checkout_vars', array(
            'form_id'              => $form->ID,
            'checkout_description' => $form->post_title,
            'currency_settings'    => $currencySettings,
        ), $form));

        wp_localize_script('wppayform_public', 'wp_payform_general', array(
            'ajax_url'  => admin_url('admin-ajax.php'),
            'date_i18n' => array(
                'previousMonth'    => __('Previous Month', 'wppayform'),
                'nextMonth'        => __('Next Month', 'wppayform'),
                'months'           => [
                    'shorthand' => [
                        __('Jan', 'wppayform'),
                        __('Feb', 'wppayform'),
                        __('Mar', 'wppayform'),
                        __('Apr', 'wppayform'),
                        __('May', 'wppayform'),
                        __('Jun', 'wppayform'),
                        __('Jul', 'wppayform'),
                        __('Aug', 'wppayform'),
                        __('Sep', 'wppayform'),
                        __('Oct', 'wppayform'),
                        __('Nov', 'wppayform'),
                        __('Dec', 'wppayform')
                    ],
                    'longhand' => [
                        __('January', 'wppayform'),
                        __('February', 'wppayform'),
                        __('March', 'wppayform'),
                        __('April', 'wppayform'),
                        __('May', 'wppayform'),
                        __('June', 'wppayform'),
                        __('July', 'wppayform'),
                        __('August', 'wppayform'),
                        __('September', 'wppayform'),
                        __('October', 'wppayform'),
                        __('November', 'wppayform'),
                        __('December', 'wppayform')
                    ]
                ],
                'weekdays'         => [
                    'longhand'  => array(
                        __('Sunday', 'wppayform'),
                        __('Monday', 'wppayform'),
                        __('Tuesday', 'wppayform'),
                        __('Wednesday', 'wppayform'),
                        __('Thursday', 'wppayform'),
                        __('Friday', 'wppayform'),
                        __('Saturday', 'wppayform')
                    ),
                    'shorthand' => array(
                        __('Sun', 'wppayform'),
                        __('Mon', 'wppayform'),
                        __('Tue', 'wppayform'),
                        __('Wed', 'wppayform'),
                        __('Thu', 'wppayform'),
                        __('Fri', 'wppayform'),
                        __('Sat', 'wppayform')
                    )
                ],
                'daysInMonth'      => [
                    31,
                    28,
                    31,
                    30,
                    31,
                    30,
                    31,
                    31,
                    30,
                    31,
                    30,
                    31
                ],
                'rangeSeparator'   => __(' to ', 'wppayform'),
                'weekAbbreviation' => __('Wk', 'wppayform'),
                'scrollTitle'      => __('Scroll to increment', 'wppayform'),
                'toggleTitle'      => __('Click to toggle', 'wppayform'),
                'amPM'             => [
                    __('AM', 'wppayform'),
                    __('PM', 'wppayform')
                ],
                'yearAriaLabel'    => __('Year', 'wppayform')
            ),
            'i18n' => array(
                'verify_recapthca' => __('Please verify recaptcha first', 'wppayform'),
                'submission_error' => __('Something is wrong when submitting the form', 'wppayform'),
                'is_required' => __('is required', 'wppayform'),
                'validation_failed' => __('Validation failed, please fill-up required fields', 'wppayform')
            )
        ));
    }

    private function registerScripts($form)
    {
        do_action('wppayform/wppayform_adding_assets', $form);
        wp_register_script('flatpickr', WPPAYFORM_URL . 'assets/libs/flatpickr/flatpickr.min.js', array(), '4.5.7', true);
        wp_register_style('flatpickr', WPPAYFORM_URL . 'assets/libs/flatpickr/flatpickr.min.css', array(), '4.5.7', 'all');

        wp_register_script('dropzone', WPPAYFORM_URL . 'assets/libs/dropzone/dropzone.min.js', array('jquery'), '5.5.0', true);
        wp_register_script('wppayform_file_upload', WPPAYFORM_URL . 'assets/js/fileupload.js', array('jquery', 'wppayform_public', 'dropzone'), WPPAYFORM_VERSION, true);

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

    private function builtAttributes($attributes)
    {
        $atts = ' ';
        foreach ($attributes as $attributeKey => $attribute) {
            $atts .= $attributeKey . "='" . $attribute . "' ";
        }
        return $atts;
    }
}