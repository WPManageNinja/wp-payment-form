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
        $ds = DIRECTORY_SEPARATOR;
        $path = str_replace('.', $ds, $path);
        $viewName = WPPAYFORM_DIR.$ds.'src'.$ds.'views'.$ds.$path;
        $fullPath = $viewName.'.php';
        return apply_filters('wppayform/template_view_path', $fullPath, $path);
    }
}