<template>
    <div v-loading="fetching">
        <div class="all_payforms_wrapper payform_section">
            <div class="payform_section_header">
                <h3 class="payform_section_title">
                    {{ $t('PayPal Gateway Settings') }}
                </h3>
                <div v-if="has_pro" class="payform_section_actions">
                    <el-button v-loading="saving" @click="saveSettings()" class="payform_action" size="small"
                               type="primary">
                        {{ $t( 'Save PayPal Settings' ) }}
                    </el-button>
                </div>
            </div>
            <div class="payform_section_body">
                <div v-if="!has_pro" class="payform_pro_message">
                    <h3>Paypal payment is a pro version feature.</h3>
                    <p>Please upgrade to pro to unlock this feature. WP Payform Pro comes with lots of feature which will increase the flexebility and conversion rate</p>
                    <a target="_blank" :href="pro_purchase_url">Upgrade to WP Payment Form Pro</a>
                </div>

                <el-form :label-position="labelPosition" rel="paypal_settings" :model="settings" label-width="220px">
                    <el-form-item label="PayPal Payment Mode">
                        <el-radio-group v-model="settings.payment_mode">
                            <el-radio label="test">Sandbox Mode</el-radio>
                            <el-radio label="live">Live Mode</el-radio>
                        </el-radio-group>
                    </el-form-item>
                    <el-form-item label="Paypal Email">
                        <el-input type="text" size="small" v-model="settings.paypal_email"
                                  placeholder="Paypal Email Address"/>
                    </el-form-item>
                    <el-form-item label="Disable PayPal IPN Verification">
                        <el-switch active-value="yes" inactive-value="no" v-model="settings.disable_ipn_verification"/>
                        <p>If you are unable to use Payment Data Transfer and payments are not getting marked as
                            complete, then check this box. This forces the site to use a slightly less secure method of
                            verifying purchases.</p>
                    </el-form-item>
                    <el-form-item label="Paypal Checkout Logo">
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

                    <div class="wpf_settings_section">
                        <h3>Confirmation Page Settings</h3>
                        <el-form-item label="Payment Success Page">
                            <el-select v-model="confirmation_pages.confirmation" filterable placeholder="Select Payment Success Redirect Page">
                                <el-option
                                    v-for="page in pages"
                                    :key="page.ID"
                                    :label="page.post_title"
                                    :value="parseInt(page.ID)">
                                </el-option>
                            </el-select>
                        </el-form-item>

                        <el-form-item label="Payment Failed Page">
                            <el-select v-model="confirmation_pages.failed" filterable placeholder="Select Payment Failed Redirect Page">
                                <el-option
                                    v-for="page in pages"
                                    :key="page.ID"
                                    :label="page.post_title"
                                    :value="parseInt(page.ID)">
                                </el-option>
                            </el-select>
                        </el-form-item>
                    </div>
                    <div v-if="has_pro" class="action_right">
                        <el-button @click="saveSettings()" type="primary" size="small">Save PayPal Settings</el-button>
                    </div>
                </el-form>

                <div style="margin-top: 20px">
                    If you use paypal for recurring payments please set the notification URL in paypal as bellow:
                    <pre>{{ipn_url}}</pre>
                    <p>Check the <a target="_blank" href="https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNSetup/#setting-up-ipn-notifications-on-paypal">official documentation</a> </p>
                    <p>If you don't setup the IPN notification then it will still work for single payments but recurring payments will not be marked as paid for paypal subscription payments.</p>
                </div>
            </div>
        </div>
    </div>
</template>
<script type="text/babel">
    export default {
        name: 'paypal_settings',
        data() {
            return {
                settings: {},
                uploadUrl: window.wpPayFormsAdmin.image_upload_url,
                saving: false,
                fetching: false,
                is_key_defined: false,
                pages: [],
                ipn_url: window.wpPayFormsAdmin.ipn_url,
                confirmation_pages: {},
                labelPosition: 'right'
            }
        },
        methods: {
            getSettings() {
                this.fetching = true;
                this.$get({
                    action: 'wpf_get_paypal_settings'
                })
                    .then((response) => {
                        this.settings = response.data.settings;
                        this.pages = response.data.pages;
                        this.confirmation_pages = response.data.confirmation_pages
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
                    action: 'wpf_save_paypal_settings',
                    settings: this.settings,
                    confirmation_pages: this.confirmation_pages
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
            if(this.has_pro) {
                this.getSettings();
            }
            window.WPPayFormsBus.$emit('site_title', 'Paypal Settings');
            if(window.outerWidth < 500) {
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