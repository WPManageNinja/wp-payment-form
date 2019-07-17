<template>
    <div class="wpf_photo_card">
        <div v-if="app_ready" class="wpf_photo_holder">
            <img v-if="product.photo.image_thumb" :src="product.photo.image_thumb"/>
            <el-button @click="initUploader" type="text">+ Photo</el-button>
        </div>
    </div>

</template>

<script type="text/babel">
    import each from 'lodash/each';
    export default {
        name: 'photo_widget',
        props: ['product'],
        data() {
            return {
                app_ready: false
            }
        },
        methods: {
            initUploader(event) {
                var that = this;
                var send_attachment_bkp = wp.media.editor.send.attachment;
                wp.media.editor.send.attachment = function (props, attachment) {
                    that.product.photo['alt_text'] = attachment.alt || attachment.title;
                    that.product.photo['image_full'] = attachment.url;
                    that.product.photo['image_thumb'] = that.getThumb(attachment);
                    wp.media.editor.send.attachment = send_attachment_bkp;
                }
                wp.media.editor.open();
                return false;
            },
            getThumb(attachment) {
                let highestSize = attachment.width;
                let maybeUrl = attachment.url;
                let finalUrl = false;
                each(attachment.sizes, (image, name) => {
                    if(name == this.preferedThum) {
                        finalUrl = image.url;
                    }
                    if(!finalUrl || image.width > 300) {
                        if (image.width < 400) {
                            finalUrl = image.url;
                        } else if (image.width < highestSize) {
                            highestSize = image.width;
                            maybeUrl = image.url;
                        }
                    }
                });
                return finalUrl || maybeUrl;
            },
        },
        mounted() {
            if (!this.product.photo || typeof this.product.photo != 'object') {
                this.$set(this.product, 'photo', {
                    'alt_text': '',
                    'image_full': '',
                    'image_thumb': ''
                });
            }

            if(!window.wpActiveEditor) {
                window.wpActiveEditor = null;
            }

            this.app_ready = true;
        }
    }
</script>
