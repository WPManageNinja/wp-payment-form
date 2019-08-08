<?php

namespace WPPayForm\Classes\FormComponents;

use WPPayForm\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

class DemoFileUploadComponent extends BaseComponent
{
    protected $componentName = 'file_upload_input';

    public function __construct()
    {
        parent::__construct($this->componentName, 12);
    }

    public function component()
    {
        return array(
            'type'            => $this->componentName,
            'editor_title'    => __('File Upload (Pro)', 'wppayform'),
            'group'           => 'input',
            'postion_group'   => 'general',
            'disabled' => true,
            'disabled_message' => 'File Upload Module requires Pro version of WPPayForm. Please install Pro version to make it work.',
            'editor_elements' => array(
                'info' => array(
                    'type' => 'info_html',
                    'info' => '<h3 style="color: firebrick; text-align: center;">File Upload Module require Pro version of WPPayForm. Please install Pro version to make it work.</h3><br />'
                ),
                'label'             => array(
                    'label' => 'Upload Label',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'button_text'       => array(
                    'label' => 'Upload Button Text',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'max_file_size'     => array(
                    'label' => 'Max File Size (in MegaByte)',
                    'type'  => 'number',
                    'group' => 'general'
                ),
                'max_allowed_files' => array(
                    'label' => 'Max Upload Files',
                    'type'  => 'number',
                    'group' => 'general'
                ),
                'allowed_files'     => array(
                    'label'         => 'Allowed file types',
                    'type'          => 'checkbox',
                    'wrapper_class' => 'checkbox_new_lined',
                    'options'       => $this->getFileTypes('label')
                ),
                'admin_label' => array(
                    'label' => 'Admin Label',
                    'type'  => 'text',
                    'group' => 'advanced'
                ),
                'wrapper_class' => array(
                    'label' => 'Field Wrapper CSS Class',
                    'type'  => 'text',
                    'group' => 'advanced'
                ),
                'element_class' => array(
                    'label' => 'Input element CSS Class',
                    'type'  => 'text',
                    'group' => 'advanced'
                ),
            ),
            'field_options'   => array(
                'label'             => 'Upload Your File',
                'button_text'       => 'Drag & Drop your files or Browse',
                'required'          => 'no',
                'max_file_size'     => 2,
                'max_allowed_files' => 1,
                'allowed_files'     => ['images'],
            )
        );
    }

    public function render($element, $form, $elements)
    {
        return '';
    }

    private function getFileTypes($pairType = false)
    {
        $types = array(
            'images' => array(
                'label'   => 'Images (jpg, jpeg, gif, png, bmp)',
                'accepts' => '.jpg,.jpeg,.gif,.png,.bmp'
            ),
            'audios' => array(
                'label'   => 'Audio (mp3, wav, ogg, wma, mka, m4a, ra, mid, midi)',
                'accepts' => '.mp3, .wav, .ogg, .wma, .mka, .m4a, .ra, .mid, .midi'
            ),
            'pdf'    => array(
                'label'   => 'pdf',
                'accepts' => '.pdf'
            ),
            'docs'   => array(
                'label'   => 'Docs (doc, ppt, pps, xls, mdb, docx, xlsx, pptx, odt, odp, ods, odg, odc, odb, odf, rtf, txt)',
                'accepts' => '.doc,.ppt,.pps,.xls,.mdb,.docx,.xlsx,.pptx,.odt,.odp,.ods,.odg,.odc,.odb,.odf,.rtf,.txt'
            ),
            'zips'   => array(
                'label'   => 'Zip Archives (zip, gz, gzip, rar, 7z)',
                'accepts' => '.zip,.gz,.gzip,.rar,.7z'
            ),
            'csv'    => array(
                'label'   => 'CSV (csv)',
                'accepts' => '.csv'
            )
        );

        $types = apply_filters('wppayform/upload_files_available', $types);

        if ($pairType) {
            $pairs = [];
            foreach ($types as $typeName => $type) {
                $pairs[$typeName] = ArrayHelper::get($type, $pairType);
            }
            return $pairs;
        }

        return $types;
    }
}