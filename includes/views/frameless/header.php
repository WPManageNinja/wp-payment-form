<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link rel="profile" href="https://gmpg.org/xfn/11"/>
    <title><?php echo $title; ?></title>

    <?php foreach ($css_files as $file): ?>
        <link rel='stylesheet' href='<?php echo $file; ?>' type='text/css' media='all'/>
    <?php endforeach; ?>

    <?php foreach ($js_files as $file): ?>
        <script type='text/javascript' src='<?php echo $file; ?>'></script>
    <?php endforeach; ?>

    <?php do_action('wppayform/frameless_header', $action); ?>
</head>
<body>
<div class="wppayform_frameless_body_start">

