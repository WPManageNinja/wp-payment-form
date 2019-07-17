<template>
    <el-container>
        <el-main class="no_shadow">
            <div class="edit_form_warpper">
                <div class="all_payforms_wrapper payform_section">
                    <div class="payform_section_header">
                        <h3 class="payform_section_title">
                            {{ $t('Form Confirmation Settings') }}
                        </h3>
                        <div class="payform_section_actions">
                            <el-button @click="saveSettings()" class="payform_action" size="small" type="primary">
                                {{ $t( 'Save Confirmation Settings' ) }}
                            </el-button>
                        </div>
                    </div>
                    <div v-loading="fetching" class="payform_section_body">
                        <el-form ref="payment_settings" :label-position="labelPosition" :model="payment_settings" label-width="220px">
                            <confirmation-settings
                                :pages="pages"
                                :editorShortcodes="editorShortcodes"
                                :confirmation="confirmation_settings">
                            </confirmation-settings>
                        </el-form>

                        <div class="recaptcha_settings wpf_settings_section">
                            <p v-if="recaptcha_settings.all_forms == 'yes'">
                                reCaptcha is enabled globally. This form will have recaptcha
                            </p>
                            <p v-else-if="recaptcha_settings.recaptcha_version == 'none'">
                                reCaptach has not been configured yet! Please configure recaptcha from Settings -> reCaptcha Settings
                            </p>
                            <div v-else>
                                <el-checkbox true-label="yes" false-label="no" v-model="form_recaptcha_status">Enable reCaptcha for this form</el-checkbox>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </el-main>
    </el-container>
</template>

<script type="text/babel">
    import ConfirmationSettings from './_AddConfirmation';
    export default {
        name: 'confirmation_settings',
        props: ['form_id'],
        components: {
            ConfirmationSettings
        },
        data() {
            return {
                payment_settings: {},
                fetching: false,
                pages: [],
                editorShortcodes: [],
                confirmation_settings: {},
                app_ready: false,
                labelPosition: 'right',
                recaptcha_settings: {},
                form_recaptcha_status: 'yes'
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
            if(window.outerWidth < 500) {
                this.labelPosition = "top";
            }
        }
    }
</script>