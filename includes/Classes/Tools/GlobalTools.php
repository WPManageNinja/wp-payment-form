<?php

namespace WPPayForm\Classes\Tools;

use WPPayForm\Classes\AccessControl;
use WPPayForm\Classes\ArrayHelper;
use WPPayForm\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Managing Global Tools
 * @since 1.1.0
 */
class GlobalTools
{
    public function registerEndpoints()
    {
        add_action('wp_ajax_wppayform_global_tools', array($this, 'handeEndPoint'));
    }

    public function handeEndPoint()
    {
        $route = sanitize_text_field($_REQUEST['route']);

        $validRoutes = array(
            'get_forms'   => 'getForms',
            'export_form' => 'exportFormJson',
            'upload_form' => 'handleImportForm'
        );

        if (isset($validRoutes[$route])) {
            AccessControl::checkAndPresponseError($route, 'tools');
            do_action('wppayform/doing_ajax_forms_' . $route);
            return $this->{$validRoutes[$route]}();
        }
    }

    public function getForms()
    {
        $forms = wpPayFormDB()->table('posts')
            ->select(['ID', 'post_title'])
            ->where('post_type', 'wp_payform')
            ->where('post_status', 'publish')
            ->orderBy('ID', 'DESC')
            ->get();
        wp_send_json_success(array(
            'forms' => $forms
        ), 200);
    }

    public function exportFormJson()
    {
        $formId = absint($_REQUEST['form_id']);
        $form = $this->getForm($formId);

        if (!$form) {
           exit('No Form Found');
        }

        header('Content-disposition: attachment; filename=' . sanitize_title($form['post_title'] . '-export-form-', 'export-form-', 'view') . '-' . gmdate('Y-m-d') . '.json');
        header('Content-type: application/json');
        echo json_encode($form);
        exit();
    }

    public function getForm($formId)
    {
        $form = get_post($formId, 'ARRAY_A');
        if (!$form || $form['post_type'] != 'wp_payform') {
            return false;
        }

        $metas = wpPayFormDB()->table('postmeta')
            ->select(['meta_key', 'meta_value'])
            ->where('post_id', $formId)
            ->get();

        $formattedMeta = array();
        foreach ($metas as $meta) {
            $formattedMeta[$meta->meta_key] = maybe_unserialize($meta->meta_value);
        }

        $form['form_meta'] = $formattedMeta;

        return $form;
    }

    public function handleImportForm()
    {
        $importFile = $_FILES['import_file'];
        $tmpName = $importFile['tmp_name'];
        $form = json_decode(file_get_contents($tmpName), true);
        $form = apply_filters('wppayform/impor_form_json_data', $form);

        // validate the $form and it's content
        if (
            !is_array($form) ||
            !isset($form['post_title']) ||
            $form['post_type'] != 'wp_payform'
        ) {
            wp_send_json_error(array(
                'message' => 'Invalid FIle, Please upload right json file'
            ), 423);
        }

        add_action('wppayform/before_form_json_import', $form);

        $newForm = $this->createFormFromData($form);

        add_action('wppayform/form_json_imported', $newForm);

        wp_send_json_success(array(
            'message' => 'Form successfully imported',
            'form' => $newForm
        ), 200);
    }

    public function createFormFromData($form)
    {
        // Create the form post type
        $postId = wp_insert_post(array(
            'post_title'   => ArrayHelper::get($form, 'post_title', 'imported form '.gmdate('Y-m-d')),
            'post_content' => ArrayHelper::get($form, 'post_content', ''),
            'post_type'    => 'wp_payform',
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id()
        ));

        if (is_wp_error($postId) && wp_doing_ajax()) {
            wp_send_json_error(array(
                'message' => 'Something is wrong when processing the file. Please try again'
            ), 423);
        } else if(is_wp_error($postId)) {
            return false;
        }

        $metas = ArrayHelper::get($form, 'form_meta', array());
        if (is_array($metas)) {
            foreach ($metas as $metaKey => $metaValue) {
                update_post_meta($postId, $metaKey, $metaValue);
            }
        }

        $newForm = Forms::getForm($postId);

        return $newForm;

    }
}