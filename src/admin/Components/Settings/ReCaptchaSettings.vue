<template>
    <div v-loading="fetching">
        <div class="all_payforms_wrapper payform_section">
            <div class="payform_section_header">
                <h3 class="payform_section_title">
                    {{ $t('reCaptcha Settings') }}
                </h3>
                <div class="payform_section_actions">
                    <el-button v-loading="saving" @click="saveSettings()" class="payform_action" size="small"
                               type="primary">
                        {{ $t( 'Save Settings' ) }}
                    </el-button>
                </div>
            </div>
            <div class="payform_section_body">
                <el-form :label-position="labelPosition" rel="stripe_settings" :model="settings" label-width="220px">
                    <el-form-item label="reCaptcha Type">
                        <el-radio-group v-model="settings.recaptcha_version">
                            <el-radio label="none">Disable recaptcha</el-radio>
                            <el-radio label="v2_visible">Visible Recaptcha (V2)</el-radio>
                            <el-radio label="v3_invisible">Invisible reCaptcha (v3)</el-radio>
                        </el-radio-group>
                    </el-form-item>
                    <div v-show="settings.recaptcha_version != 'none'" class="wpf_settings_section">
                        <h3>reCAPTCHA Keys</h3>
                        <p>You may find the API keys from here: <a href="https://www.google.com/recaptcha/admin/" target="_blank" rel="noopener">Google reCaptcha Site</a>. Please select appropriate recaptcha version when creating the API key</p>
                        <hr />
                        <br />
                        <el-form-item label="Site Key">
                            <el-input type="text" size="small" v-model="settings.site_key"
                                      placeholder="reCaptcha Site Key"/>
                        </el-form-item>
                        <el-form-item label="Secret key">
                            <el-input type="password" size="small" v-model="settings.secret_key"
                                      placeholder="reCaptcha Secret Key"/>
                        </el-form-item>
                    </div>
                    <div class="action_right">
                        <el-button @click="saveSettings()" type="primary" size="small">Save Settings</el-button>
                    </div>
                </el-form>
            </div>
        </div>
    </div>
</template>
<script type="text/babel">
    export default {
        name: 'stripe_settings',
        data() {
            return {
                settings: {},
                uploadUrl: window.wpPayFormsAdmin.image_upload_url,
                saving: false,
                fetching: false,
                is_key_defined: false,
                labelPosition: 'right',
                webhook_url: ''
            }
        },
        methods: {
            getSettings() {
                this.fetching = true;
                this.$get({
                    action: 'wpf_global_settings_handler',
                    route: 'get_recaptcha_settings'
                })
                    .then((response) => {
                        this.settings = response.data.settings;
                    })
                    .fail(error => {
                        this.$message.error(error.responseJSON.data.message);
                    })
                    .always(() => {
                        this.fetching = false;
                    });
            },
            saveSettings() {
                this.saving = true;
                this.$post({
                    action: 'wpf_global_settings_handler',
                    route: 'save_recaptcha_settings',
                    settings: this.settings
                })
                    .then(response => {
                        this.$message.success(response.data.message);
                    })
                    .fail(error => {
                        this.$message.error(error.responseJSON.data.message);
                    })
                    .always(() => {
                        this.saving = false;
                    });
            }
        },
        mounted() {
            this.getSettings();
            window.WPPayFormsBus.$emit('site_title', 'reCaptcha Settings');
            if (window.outerWidth < 500) {
                this.labelPosition = "top";
            }
        }
    }
</script>

<style lang="scss" scoped>
    .el-radio {
        margin-bottom: 10px;
    }
</style>