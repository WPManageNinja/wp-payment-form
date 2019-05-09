<?php

namespace WPPayForm\Classes;

class View
{

    /**
     * Generate and echo/print a view file
     * @param  string $path
     * @param  array  $data
     * @return void
     */
    public static function render($path, $data = [])
    {
        echo self::make($path, $data);
    }

    /**
     * Generate a view file
     * @param  string $path
     * @param  array  $data
     * @return string [generated html]
     */
    public static function make($path, $data = [])
    {
        if (file_exists($path = self::getFilePath($path))) {
            ob_start();
            extract($data);
            include $path;
            return ob_get_clean();
        }
        return '';
    }

    /**
     * Resolve the view file path
     * @param  string $path
     * @return string
     */
    protected static function getFilePath($path)
    {
        $path = str_replace('.', DIRECTORY_SEPARATOR, $path);
        $viewName = WPPAYFORM_DIR.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.$path;
        $fullPath = $viewName.'.php';
        return apply_filters('wppayform/template_view_path', $fullPath, $path);
    }
}