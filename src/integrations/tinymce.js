(function() {
    tinymce.PluginManager.add( 'wpf_mce_payment_button', function( editor, url ) {
        // Add Button to Visual Editor Toolbar
        editor.addButton('wpf_mce_payment_button', {
            title: 'Insert Button Link',
            cmd: 'wpf_mce_payment_command',
            image: url + '/tinymce_icon.png'
        });
        // Add Command when Button Clicked
        editor.addCommand('wpf_mce_payment_command', function() {
            editor.windowManager.open({
                title: window.wpf_tinymce_vars.title,
                body: [
                    {
                        type   : 'listbox',
                        name   : 'wppayform_shortcode',
                        label  : window.wpf_tinymce_vars.label,
                        values : window.wpf_tinymce_vars.forms
                    },
                    {
                        type   : 'checkbox',
                        name   : 'wppayform_show_title',
                        label  : 'Show Form Title',
                        values : 'yes'
                    },
                    {
                        type   : 'checkbox',
                        name   : 'wppayform_show_description',
                        label  : 'Show Form Description',
                        values : 'yes'
                    }
                ],
                width: 768,
                height: 150,
                onsubmit: function( e ) {
                    if( e.data.wppayform_shortcode ) {
                        let extraString = '';
                        if(e.data.wppayform_show_title) {
                            extraString += ' show_title="yes"';
                        }
                        if(e.data.wppayform_show_description) {
                            extraString += ' show_description="yes"';
                        }
                        let shortcodec = `[wppayform id="${e.data.wppayform_shortcode}"]`
                        if(extraString) {
                            shortcodec = `[wppayform id="${e.data.wppayform_shortcode}" ${extraString}]`;
                        }
                        editor.insertContent( shortcodec );
                    } else {
                        alert(window.wpf_tinymce_vars.select_error);
                        return false;
                    }
                },
                buttons: [
                    {
                        text: window.wpf_tinymce_vars.insert_text,
                        subtype: 'primary',
                        onclick: 'submit'
                    }
                ]
            }, {
                'tinymce': tinymce
            });
        });

    });
})();