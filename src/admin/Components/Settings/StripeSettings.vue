<template>
    <div v-loading="fetching">
        <div class="all_payforms_wrapper payform_section">
            <div class="payform_section_header">
                <h3 class="payform_section_title">
                    {{ $t('Stripe Gateway Settings') }}
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
                    <el-form-item label="Stripe Payment Mode">
                        <el-radio-group v-model="settings.payment_mode">
                            <el-radio label="test">Test Mode</el-radio>
                            <el-radio label="live">Live Mode</el-radio>
                        </el-radio-group>
                    </el-form-item>
                    <div class="wpf_settings_section">
                        <h3>Stripe Test Keys</h3>
                        <el-form-item label="Test Publishable key">
                            <el-input type="text" size="small" v-model="settings.test_pub_key"
                                      placeholder="Test Publishable key"/>
                        </el-form-item>
                        <el-form-item label="Test Secret key">
                            <el-input type="password" size="small" v-model="settings.test_secret_key"
                                      placeholder="Test Secret key"/>
                        </el-form-item>
                    </div>

                    <div v-if="!is_key_defined" class="wpf_settings_section">
                        <h3>Stripe Live Keys</h3>
                        <el-form-item label="Live Publishable key">
                            <el-input type="text" size="small" v-model="settings.live_pub_key"
                                      placeholder="Live Publishable key"/>
                        </el-form-item>
                        <el-form-item label="Live Secret key">
                            <el-input type="password" size="small" v-model="settings.live_secret_key"
                                      placeholder="Live Secret key"/>
                        </el-form-item>
                    </div>
                    <div v-else class="wpf_settings_section">
                        <p>Live Publishable key and Live Secret key is defined in wp-config.php. This is good! (y)</p>
                    </div>

                    <div class="wpf_settings_section">
                        <h3>Company Info (Will be used on checkout)</h3>

                        <el-form-item label="Company/Business Name">
                            <el-input type="text" size="small" v-model="settings.company_name"
                                      placeholder="Company/Business Name"/>
                        </el-form-item>

                        <el-form-item label="Checkout Logo">
                            <el-upload
                                class="avatar-uploader"
                                :action="uploadUrl"
                                :show-file-list="false"
                                accept_x="image/png, image/jpeg"
                                :on-error="handleUploadError"
                                :on-success="handleUploadSuccess">
                                <img v-if="settings.checkout_logo" :src="settings.checkout_logo" class="avatar"/>
                                <i v-else class="el-icon-plus avatar-uploader-icon"></i>
                            </el-upload>
                        </el-form-item>
                    </div>

                    <div class="wpf_settings_section">
                        <el-form-item label="Stripe Meta Data">
                            <el-checkbox :disabled="!has_pro" true-label="yes" false-label="no" v-model="settings.send_meta_data">Send Form input data to stripe metadata</el-checkbox>
                            <p>If you enable this then, Your form input data will be send to stripe as meta data</p>
                            <p v-if="!has_pro">This is a pro feature. Please upgrade to pro to enable this feature</p>
                        </el-form-item>
                    </div>

                    <div v-if="has_pro" class="wpf_settings_section">
                        <h3>Stripe Webhook (For Recurring Payments)</h3>
                        <p>In order for Stripe to function completely for subscription/recurring payments, you must configure your Stripe webhooks. Visit
                            your <a href="https://dashboard.stripe.com/account/webhooks" target="_blank" rel="noopener">account
                                dashboard</a> to configure them. Please add a webhook endpoint for the URL below.</p>
                        <p><b>Webhook URL: </b><code>{{webhook_url}}</code></p>
                        <p>See <a href="https://wpmanageninja.com/docs/wppayform/getting-started-with-wppayform/configure-payment-methods-and-currency/" target="_blank" rel="noopener">our documentation</a> for more information.</p>

                        <div>
                            <p><b>Please enable the following Webhook events for this URL:</b></p>
                            <ul>
                                <li><code>charge.succeeded</code></li>
                                <li><code>invoice.payment_succeeded</code></li>
                                <li><code>charge.refunded</code></li>
                                <li><code>customer.subscription.deleted</code></li>
                                <li><code>checkout.session.completed</code></li>
                            </ul>
                        </div>

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
                    action: 'wpf_get_stripe_settings'
                })
                    .then((response) => {
                        this.settings = response.data.settings;
                        this.is_key_defined = response.data.is_key_defined
                        this.webhook_url = response.data.webhook_url
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
                    action: 'wpf_save_stripe_settings',
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
            },
            handleUploadSuccess(response) {
                this.settings.checkout_logo = response.data.file.url;
            },
            handleUploadError(error) {
                this.$message.error(error.toString());
            }
        },
        mounted() {
            this.getSettings();
            window.WPPayFormsBus.$emit('site_title', 'Stripe Settings');
            if (window.outerWidth < 500) {
                this.labelPosition = "top";
            }
        }
    }
</script>

<style lang="scss">
    .avatar-uploader .el-upload {
        border: 1px dashed #d9d9d9;
        border-radius: 6px;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .avatar-uploader .el-upload:hover {
        border-color: #409EFF;
    }

    .avatar-uploader-icon {
        font-size: 28px;
        color: #8c939d;
        width: 178px;
        height: 178px;
        line-height: 178px !important;
        text-align: center;
    }

    .avatar {
        width: 178px;
        height: 178px;
        display: block;
        width: auto;
    }
</style>