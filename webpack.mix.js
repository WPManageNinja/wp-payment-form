let mix = require('laravel-mix');
mix.setPublicPath('assets');
mix.setResourceRoot('../');

require('laravel-mix-polyfill');


mix
    .js('src/admin/Boot.js', 'assets/js/payforms-boot.js')
    .js('src/admin/main.js', 'assets/js/payforms-admin.js')
    .js('src/public/publicv2.js', 'assets/js/payforms-publicv2.js')
    .js('src/public/fileupload.js', 'assets/js/fileupload.js')
    .js('src/integrations/tinymce.js', 'assets/js/tinymce.js')
    .sass('src/scss/admin/app.scss', 'assets/css/payforms-admin.css')
    .sass('src/scss/admin/payforms-print.scss', 'assets/css/payforms-print.css')
    .sass('src/scss/public/public.scss', 'assets/css/payforms-public.css')
    .sass('src/scss/public/frameless.scss', 'assets/css/frameless.css')
    .copy('src/images', 'assets/images')
    .copy('src/integrations/tinymce_icon.png', 'assets/js/tinymce_icon.png')
    .copy('src/libs', 'assets/libs')
    .polyfill({
        enabled: true,
        useBuiltIns: "usage",
        targets: {"firefox": "50", "ie": 11}
    });