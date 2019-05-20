const addQueryString = function (url, queryString) {
    var isQuestionMarkPresent = url && url.indexOf('?') !== -1,
        separator = '';

    if (queryString) {
        separator = isQuestionMarkPresent ? '&' : '?';
        url += separator + queryString;
    }

    return url;
};
window.Dropzone.autoDiscover = false;

jQuery(document).ready(function ($) {
    const wpfFileUploader = {
        initUploaders() {
            let $forms = $('.wpf_form_has_file_upload');
            $.each($forms, (index, fileForm) => {
                this.initUploader($(fileForm));
            });

            $('.upload_error_message').on('click', function () {
                $(this).html('').removeClass('wpf_has_error');
            });
        },

        initUploader($theForm) {
            var that = this;
            $theForm.find('input.wpf_file_upload_element').each(function (key, el) {
                var element = $(el);

                let elementName = element.data('target_name');
                let formData = {
                    form_id: $theForm.data('wpf_form_id'),
                    element_name: elementName,
                    action: 'wpf_file_upload_process'
                }
                let uploadUrl = window.wp_payform_general.ajax_url;
                uploadUrl = addQueryString(uploadUrl, $.param(formData));
                let $dropZoneParent = element.parent();
                let dropZoneInstance = $dropZoneParent.dropzone({
                    url: uploadUrl,
                    maxFiles: element.data('max_files'),
                    maxFilesize: element.data('max_file_size'),
                    addRemoveLinks: true,
                    filesizeBase: 1024,
                    dictDefaultMessage: element.data('btn_txt'),
                    acceptedFiles: element.attr('accept'),
                    success(file, response) {
                        $('<input>', {
                            type: 'hidden',
                            'data-file_id': file.upload.uuid,
                            name: element.data('associate_key')+'[]',
                            value: response.file
                        }).appendTo(file.previewElement);
                    },
                    error(file, message, serverResponse){
                        this.removeFile(file);
                        that.showErrorMessage(message, serverResponse, $dropZoneParent);
                    }
                });

                $theForm.trigger('file_upload_initiated', [dropZoneInstance, element]);
            });
        },

        showErrorMessage(message, serverResponse, $dropZoneParent)
        {
            let $errorWrapper = $dropZoneParent.parent().find('.upload_error_message');
            if(serverResponse && serverResponse.response) {
                let response = $.parseJSON(serverResponse.response);
                if(response.data && response.data.message) {
                    message = response.data.message;
                } else {
                    message = serverResponse.responseText;
                }
            }
            $errorWrapper.html(message).addClass('wpf_has_error');
        }

    }
    wpfFileUploader.initUploaders();
});



