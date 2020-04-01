<?php

use WPPayForm\Classes\Modules\Component\Component;

/**
 * Declare common actions/filters/shortcodes
 */


/**
 * @var $app \FluentForm\Framework\Foundation\Application
 */

// $component = new Component($app);
// $component->addRendererActions();
// $component->addFluentFormShortCode();
// $component->addFluentFormDefaultValueParser();

// $component = new \FluentForm\App\Modules\Component\Component($app);
// $component->addFluentformSubmissionInsertedFilter();
// $component->addIsRenderableFilter();

// $app->addAction('wp', function () use ($app) {
// 	if (isset($_GET['wppayform_pages']) && $_GET['wppayform_pages'] == 1) {
// 		add_action('wp_enqueue_scripts', function () use ($app) {
// 			wp_enqueue_style('fluentform-styles');
// 			wp_enqueue_style('fluentform-public-default');
// 			wp_enqueue_script('fluent-form-submission');
// 			wp_enqueue_style('fluent-form-preview', $app->publicUrl('css/preview.css'));
// 		});
// 		(new \WPPayForm\App\Modules\ProcessExteriorModule())->handleExteriorPages();
// 	}
// });


// $elements = [
//     'select',
//     'input_checkbox',
//     'address',
//     'select_country',
//     'gdpr_agreement',
//     'terms_and_condition',
// ];

// foreach ($elements as $element) {
//     $event = 'fluentform_response_render_' . $element;
//     $app->addFilter($event, function ($response, $field, $form_id) {
//         if ($field['element'] == 'address' && isset($response->country)) {
//             $countryList = getFluentFormCountryList();
//             if (isset($countryList[$response->country])) {
//                 $response->country = $countryList[$response->country];
//             }
//         }

//         if ($field['element'] == 'select_country') {
//             $countryList = getFluentFormCountryList();
//             if (isset($countryList[$response])) {
//                 $response = $countryList[$response];
//             }
//         }

//         if (in_array($field['element'], array('gdpr_agreement', 'terms_and_condition'))) {
//             $response = __('Accepted', 'fluentform');
//         }

//         return \FluentForm\App\Modules\Form\FormDataParser::formatValue($response);
//     }, 10, 3);
// }

// $app->addFilter('fluentform_response_render_input_file', function ($response, $field, $form_id, $isHtml = false) {
//     return \FluentForm\App\Modules\Form\FormDataParser::formatFileValues($response, $isHtml);
// }, 10, 4);

// $app->addFilter('fluentform_response_render_input_image', function ($response, $field, $form_id, $isHtml = false) {
//     return \FluentForm\App\Modules\Form\FormDataParser::formatFileValues($response, $isHtml);
// }, 10, 4);

// $app->addFilter('fluentform_response_render_input_repeat', function ($response, $field, $form_id) {
//     return \FluentForm\App\Modules\Form\FormDataParser::formatRepeatFieldValue($response, $field, $form_id);
// }, 10, 3);

// $app->addFilter('fluentform_response_render_tabular_grid', function ($response, $field, $form_id) {
//     return \FluentForm\App\Modules\Form\FormDataParser::formatTabularGridFieldValue($response, $field, $form_id);
// }, 10, 3);

// $app->addFilter('fluentform_response_render_input_name', function ($response) {
//     return \FluentForm\App\Modules\Form\FormDataParser::formatName($response);
// }, 10, 1);


// $app->addFilter('fluentform_filter_insert_data', function ($data) {
//     $settings = get_option('_fluentform_global_form_settings', false);
//     if (is_array($settings) && isset($settings['misc'])) {
//         if (isset($settings['misc']['isIpLogingDisabled'])) {
//             if ($settings['misc']['isIpLogingDisabled']) {
//                 unset($data['ip']);
//             }
//         }
//     }
//     return $data;
// });


// // Register api response log hooks
// $app->addAction(
//     'fluentform_after_submission_api_response_success',
//     'fluentform_after_submission_api_response_success', 10, 6
// );

// $app->addAction(
//     'fluentform_after_submission_api_response_failed',
//     'fluentform_after_submission_api_response_failed', 10, 6
// );

// function fluentform_after_submission_api_response_success($form, $entryId, $data, $feed, $res, $msg = '')
// {
//     try {

//         $isDev = wpFluentForm()->getEnv() != 'production';
//         if (!apply_filters('fluentform_api_success_log', $isDev, $form, $feed)) return;

//         wpFluent()->table('fluentform_submission_meta')->insert([
//             'response_id' => $entryId,
//             'form_id'     => $form->id,
//             'meta_key'    => 'api_log',
//             'value'       => $msg,
//             'name'        => $feed->formattedValue['name'],
//             'status'      => 'success',
//             'created_at'  => current_time('mysql'),
//             'updated_at'  => current_time('mysql')
//         ]);
//     } catch (Exception $e) {
//         error_log($e->getMessage());
//     }
// }

add_action('init', function () use ($app) {
});