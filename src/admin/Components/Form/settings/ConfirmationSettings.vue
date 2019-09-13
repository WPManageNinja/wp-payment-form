<template>
    <el-container>
        <el-main class="no_shadow">
            <el-tabs v-model="current_tab" type="border-card">
                <el-tab-pane name="confirmation" label="Form Confirmation Settings">
                    <div v-if="current_tab == 'confirmation'">
                        <div class="all_payforms_wrapper payform_section">
                            <div v-loading="fetching" class="payform_section_body">
                                <el-form ref="payment_settings" :label-position="labelPosition"
                                         :model="payment_settings" label-width="220px">
                                    <confirmation-settings
                                        :pages="pages"
                                        :editorShortcodes="editorShortcodes"
                                        :confirmation="confirmation_settings">
                                    </confirmation-settings>
                                </el-form>
                                <el-button @click="saveSettings()" class="payform_action" size="small" type="primary">
                                    {{ $t( 'Save Confirmation Settings' ) }}
                                </el-button>
                            </div>
                        </div>
                    </div>
                </el-tab-pane>
                <el-tab-pane name="receipt_customization" label="Receipt Customization">
                    <receipt-settings
                        v-if="current_tab == 'receipt_customization'"
                        :receipt="receipt_settings"
                        :form_id="form_id"
                        :editorShortcodes="editorShortcodes">
                    </receipt-settings>
                </el-tab-pane>
                <el-tab-pane name="recaptcha" label="reCaptcha Settings">
                    <div class="recaptcha_settings wpf_settings_section">
                        <p v-if="recaptcha_settings.all_forms == 'yes'">
                            reCaptcha is enabled globally. This form will have recaptcha
                        </p>
                        <p v-else-if="recaptcha_settings.recaptcha_version == 'none'">
                            reCaptach has not been configured yet! Please configure recaptcha from Settings -> reCaptcha
                            Settings
                        </p>
                        <div v-else>
                            <el-checkbox true-label="yes" false-label="no" v-model="form_recaptcha_status">Enable
                                reCaptcha for this form
                            </el-checkbox>
                        </div>
                    </div>
                    <el-button @click="saveSettings()" class="payform_action" size="small" type="primary">
                        {{ $t( 'Save reCaptcha Settings' ) }}
                    </el-button>
                </el-tab-pane>
            </el-tabs>

        </el-main>
    </el-container>
</template>

<script type="text/babel">
    import ConfirmationSettings from './_AddConfirmation';
    import ReceiptSettings from './_ReceiptCustomization';

    export default {
        name: 'confirmation_settings',
        props: ['form_id'],
        components: {
            ConfirmationSettings,
            ReceiptSettings
        },
        data() {
            return {
                payment_settings: {},
                receipt_settings: {},
                fetching: false,
                pages: [],
                editorShortcodes: [],
                confirmation_settings: {},
                app_ready: false,
                labelPosition: 'right',
                recaptcha_settings: {},
                form_recaptcha_status: 'yes',
                current_tab: 'confirmation'
            }
        },
        methods: {
            getSettings() {
                this.fetching = true;
                this.$adminGet({
                    route: 'get_form_settings',
                    form_id: this.form_id
                })
                    .then(response => {
                        this.confirmation_settings = response.data.confirmation_settings;
                        this.editorShortcodes = Object.values(response.data.editor_shortcodes);
                        this.pages = response.data.pages;
                        this.recaptcha_settings = response.data.recaptcha_settings;
                        this.form_recaptcha_status = response.data.form_recaptcha_status;
                        this.receipt_settings = response.data.receipt_settings;
                    })
                    .fail(error => {

                    })
                    .always(() => {
                        this.fetching = false;
                        this.app_ready = true;
                    })
            },
            saveSettings() {
                this.saving = true;
                this.$adminPost({
                    form_id: this.form_id,
                    confirmation_settings: this.confirmation_settings,
                    route: 'save_form_settings',
                    form_recaptcha_status: this.form_recaptcha_status
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
            this.getSettings();
            window.WPPayFormsBus.$emit('site_title', 'Form Confirmation Settings');
            if (window.outerWidth < 500) {
                this.labelPosition = "top";
            }
        }
    }
</script>