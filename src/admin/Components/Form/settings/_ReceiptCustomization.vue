<template>
    <div class="wpf_receipt_settings">
        <el-form ref="receipt_settings" label-position="top"
                 :model="receipt" label-width="220px">
            <el-form-item>
                <template slot="label">
                    Receipt Header Content
                    <el-tooltip class="item" placement="bottom-start" effect="light">
                        <div slot="content">
                            <h3>Header Content</h3>
                            <p>
                                Enter the text you would like the user to <br>
                                show on the receipt header.
                            </p>
                        </div>
                        <i class="el-icon-info el-text-info"/>
                    </el-tooltip>
                </template>

                <wp-editor editor_id="wp_receipt_editor_header" :height="100" :editor-shortcodes="editorShortcodes"
                           v-model="receipt.receipt_header"/>
            </el-form-item>


            <el-form-item>
                <template slot="label">
                    Receipt Footer Content
                    <el-tooltip class="item" placement="bottom-start" effect="light">
                        <div slot="content">
                            <h3>Footer Content</h3>
                            <p>
                                Enter the text you would like the user to <br>
                                show on the receipt footer.
                            </p>
                        </div>
                        <i class="el-icon-info el-text-info"/>
                    </el-tooltip>
                </template>

                <wp-editor editor_id="wp_receipt_editor_footer" :height="100" :editor-shortcodes="editorShortcodes"
                           v-model="receipt.receipt_footer"/>
            </el-form-item>

            <el-form-item>
                <el-checkbox true-label="yes" false-label="no" v-model="receipt.info_modules.payment_info">Show Order Item details in the receipt (if any)</el-checkbox>
            </el-form-item>

            <el-form-item>
                <el-checkbox true-label="yes" false-label="no" v-model="receipt.info_modules.input_details">Show Submission info in the receipt</el-checkbox>
            </el-form-item>

            <el-button v-loading="saving" @click="saveSettings()" class="payform_action" size="small" type="primary">
                {{ $t( 'Save Receipt Settings' ) }}
            </el-button>
        </el-form>

        <br />
        <hr />

        <p>This content will show wherever you use the shortcode: <code>[wppayform_reciept]</code>. You may use this shortcode in email notification, success message or in your success page content</p>
    </div>
</template>

<script type="text/babel">
    import wpEditor from '../../Common/_wp_editor';

    export default {
        name: 'receipt_customization',
        props: ['editorShortcodes', 'receipt', 'form_id'],
        components: {
            wpEditor
        },
        data() {
            return {
                fetching: false,
                saving: false
            }
        },
        methods: {
            saveSettings() {
                if(!this.has_pro) {
                    alert('Sorry! Receipt customization will work only if you have pro version istalled. Please intstall pro version of this plugin');
                    return;
                }

                this.saving = true;
                this.$adminPost({
                    form_id: this.form_id,
                    receipt_settings: this.receipt,
                    route: 'save_form_settings'
                })
                    .then(response => {
                        this.$message({
                            message: response.data.message,
                            type: 'success'
                        });
                    })
                    .fail(error => {
                        this.$message({
                            message: error.responseJSON.data.message,
                            type: 'error'
                        });
                    })
                    .always(() => {
                        this.saving = false;
                    })
            }
        },
        mounted() {

        }
    }
</script>
