<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <meta http-equiv="Imagetoolbar" content="No"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('Preview Form', 'wppayform') ?></title>
    <?php
    wp_head();
    ?>
    <style type="text/css">
        .wpf_preview_title {
            display: inline-block;
            font-weight: bold;
            color: black !important;
        }
        .wpf_preview_title ul {
            list-style: none;
        }

        .wpf_preview_title ul li{
            display: inline-block;
            padding: 15px 20px 15px 20px;
            margin: 0;
        }

        .wpf_preview_action {
            display: inline-block;
            background: #dedede;
            color: #545454;
            border-radius: 4px;
            padding: 0px 8px;
            margin: 22px 0px;
            height: 30px;
        }
        .wpf_preview_body {
            padding: 40px 0px 40px 0px;
            width: 100%;
            background-color: #dedede;
            /* overflow: hidden; */
            min-height: 85vh;
        }

        .wpf_form_preview_wrapper {
            padding: 30px;
            /* width: 60%; */
            max-width: 900px;
            background: white;
            padding: 23px;
            margin: auto;
        }

        .wpf_preview_header {
            top: 0px;
            left: 0;
            right: 0px;
            padding: 0px 20px 0px 0px;
            background-color: #ebedee;
            color: black;
        }

        .wpf_preview_footer {
            display: block;
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
            padding: 30px 0px;
        }

        html.wpf_go_full {
            padding-top: 0;
        }
        .wpf_go_full body {
            background: white;
        }
        .wpf_go_full .wpf_preview_body {
            background: white;
        }
        .wpf_go_full #wpf_preview_top {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0;
            background: #feffff;
            color: #596075;
            box-shadow: 0px 5px 5px 6px #e6e6e6;
        }
        .wpf_preview_container_action {
            margin:23px;
        }
        .wpf_preview_container_action span {
            font-size:28px;
            color: #605858;
            cursor: pointer;
        }
        .wpf_hide {
            display: none;
        }
        .wpf_go_full .wpf_preview_footer {
            display: none;
        }
    </style>
</head>
<body>
<div id="wpf_preview_top">
    <div class="wpf_preview_header">
        <div class="wpf_preview_title">
            <ul>
                <li class="wpf_form_name">
                    <?php echo $form->ID .' - '. $form->post_name . ' ( Preview )';  ?>
                </li>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=wppayform.php#/edit-form/' . $form_id . '/form-builder') ?>">Edit Fields</a>
                </li>
            </ul>
        </div>
        <div style="float: right;display: flex;">
            <div class="wpf_preview_action">
                [wppayform id="<?php echo $form_id; ?>"]
            </div>
            <div class="wpf_preview_container_action">
                <span class=" wpf_hide wpf-preview-expand dashicons dashicons-editor-expand"></span>
                <span class="wpf-preview-contrast dashicons dashicons-editor-contract"></span>
            </div>
        </div>

    </div>
    <div class="wpf_preview_body">
        <div class="wpf_form_preview_wrapper">
            <?php echo do_shortcode('[wppayform id="' . $form_id . '"]'); ?>
        </div>
    </div>
    <div class="wpf_preview_footer">
        <p>You are seeing preview version of WPPayForm. This form is only accessible for Admin users. Other users
            may not access this page. To use this for in a page please use the following shortcode: [wppayform
            id='<?php echo $form_id ?>']</p>
    </div>
</div>
<?php
wp_footer();
?>

<script type="text/javascript">


    jQuery(document).ready(function ($) {
        var status = window.localStorage.getItem('wpf_full_screen_preview');
        if ( status == 'no') {
            jQuery('html').toggleClass('wpf_go_full');
            $('.wpf-preview-contrast').toggleClass("wpf_hide");
            $('.wpf-preview-expand').toggleClass("wpf_hide");
        }

        $('.wpf-preview-contrast').on('click', function () {
            jQuery('html').toggleClass('wpf_go_full');
            $(this).toggleClass("wpf_hide")
            $('.wpf-preview-expand').toggleClass("wpf_hide");
            window.localStorage.setItem('wpf_full_screen_preview', 'no');
        });

        $('.wpf-preview-expand').on('click', function () {
            jQuery('html').toggleClass('wpf_go_full');
            $(this).toggleClass("wpf_hide")
            $('.wpf-preview-contrast').toggleClass("wpf_hide");
            window.localStorage.setItem('wpf_full_screen_preview', 'yes');
        });
    });
</script>

</body>
</html>