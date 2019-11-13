<?php

namespace WPPayForm\Classes\File;

class FileHandler
{
    private $originalFile;
    private $validations;
    private $file;

    public function __construct($file, $validations = [])
    {
        $this->originalFile = $file;
        $this->validations = $validations;
        $this->file = new File($file['tmp_name'], $file['name']);
    }

    public function validate($rules)
    {
        $errors = [];
        foreach ($rules as $ruleName => $ruleValue) {
            if ($ruleName == 'extensions') {
                $fileExtension = $this->file->guessExtension();
                if(in_array('.mp3', $ruleValue)) {
                    $ruleValue[] = '.mpga';
                }
                if (!in_array('.' . $fileExtension, $ruleValue)) {
                    $errors[$ruleName] = __('Invalid File Extension');
                }
            } else if ($ruleName == 'max_file_size' && $ruleValue) {
                $valueInBytes = $ruleValue * 1024 * 1024;
                if ($this->file->getSize() > $valueInBytes) {
                    $errors[$ruleName] = __('File size needs to be less than ' . $ruleValue . 'MB');
                }
            }
        }
        return $errors;
    }

    public function upload()
    {
        $uploadedFile = wp_handle_upload(
            $this->originalFile,
            ['test_form' => false]
        );
        return $uploadedFile;
    }

    /**
     * Register filters for custom upload dir
     */
    public function overrideUploadDir()
    {
        add_filter('wp_handle_upload_prefilter', function ($file) {
            add_filter('upload_dir', [$this, 'setCustomUploadDir']);

            add_filter('wp_handle_upload', function ($fileinfo) {
                remove_filter('upload_dir', [$this, 'setCustomUploadDir']);
                $fileinfo['file'] = basename($fileinfo['file']);
                return $fileinfo;
            });

            return $this->renameFileName($file);
        });
    }

    /**
     * Set plugin's custom upload dir
     * @param  array $param
     * @return array $param
     */
    public function setCustomUploadDir($param)
    {
        $param['url'] = $param['baseurl'] . WPPAYFORM_UPLOAD_DIR;
        $param['path'] = $param['basedir'] . WPPAYFORM_UPLOAD_DIR;
        if (!is_dir($param['path'])) {
            mkdir($param['path'], 0755);
            file_put_contents(
                wp_upload_dir()['basedir'] . WPPAYFORM_UPLOAD_DIR . '/.htaccess',
                file_get_contents(__DIR__ . '/Stubs/htaccess.stub')
            );
        }

        return $param;
    }


    /**
     * Rename the uploaded file name before saving
     * @param  array $file
     * @return array $file
     */
    public function renameFileName($file)
    {
        $prefix = 'wpf-' . md5(uniqid(rand())) . '-wpf-';
        $file['name'] = $prefix . $file['name'];
        return $file;
    }

}