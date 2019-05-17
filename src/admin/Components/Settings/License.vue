<template>
    <div class="license">
        <div class="all_payforms_wrapper payform_section wpf_min_width">
            <div class="payform_section_header">
                <div v-if="is_valid == 'valid'">
                    <h2>{{ $t('Your License is Active') }}</h2>
                </div>
                <div v-loading="checkingLicense" v-else-if="is_valid == 'expired'">
                    <h2>{{ $t('Licensing has been expired') }}</h2>
                </div>
                <div v-else>
                    <h2>{{ $t('Licensing') }}</h2>
                    <p>
                        You have to activate your WPPayForm Pro by providing the license key bellow. If you don't have a
                        license key please <a href="https://wpmanageninja.com/checkout/purchase-history/"
                                              target="_blank">Click
                        Here</a> to get a license key from your purchase. <br/>Any questions or problems with your
                        license? <a
                        href="https://wpmanageninja.com/contact/" target="_blank">Contact us!</a>
                    </p>
                </div>
            </div>
            <div class="payform_section_body">
                <div class="ninja_content">
                    <div v-if="is_valid == 'valid'" class="license_success">
                        <h3>{{ $t('Your license is active! Enjoy WPPay Form Pro') }}</h3>
                        <el-button v-loading="doing_ajax" @click="deactivateLicense()" class="license_submit"
                                   type="default" size="mini">{{ $t('Deactivate License') }}
                        </el-button>
                        <p v-if="renewHtml" v-html="renewHtml"></p>
                    </div>
                    <div v-loading="checkingLicense" v-else-if="is_valid == 'expired'" class="license_form">
                        <div style="text-align: center" class="checking_license" v-html="renewLicenseHtml"></div>
                        <p>If you already renewed your license then please <a @click.prevent="get_license_info()"
                                                                              href="#">click here to check again</a></p>
                        <p>Have a new license key? Please <a href="#" @click.prevent="enter_new_license = true">click
                            here</a></p>

                        <div v-if="enter_new_license" class="license_form">
                            <label for="license_form_input">
                                {{ $t('Enter your license key') }}
                            </label>
                            <div class="form_input">
                                <input v-model="licenseKey" placeholder="License Key" id="license_form_input"/>
                            </div>
                            <el-button v-loading="doing_ajax" @click="activateLicense()" class="license_submit"
                                       type="primary">{{ $t('Activate WPPay Forms Pro') }}
                            </el-button>
                            <div class="nt_messages">
                                <p class="error_message" v-html="error_message" v-if="error_message"></p>
                            </div>
                        </div>

                    </div>
                    <div v-else class="license_form">
                        <label for="license_form_input">
                            {{ $t('Enter your license key') }}
                        </label>
                        <div class="form_input">
                            <input v-model="licenseKey" placeholder="License Key" id="license_form_input"/>
                        </div>
                        <el-button v-loading="doing_ajax" @click="activateLicense()" class="license_submit"
                                   type="primary">{{ $t('Activate WPPay Forms Pro') }}
                        </el-button>

                        <div class="nt_messages">
                            <p class="error_message" v-html="error_message" v-if="error_message"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'license',
        data() {
            return {
                licenseKey: '',
                error_message: '',
                enter_new_license: false,
                checkingLicense: false,
                doing_ajax: false,
                renewLicenseHtml: '',
                renewHtml: '',
                is_valid: window.wpPayFormsAdmin.hasValidLicense
            }
        },
        methods: {
            activateLicense() {
                if (!this.licenseKey) {
                    this.error_message = 'Please provide a license key';
                    return;
                }
                this.doing_ajax = true;
                this.error_message = '';
                jQuery.post(ajaxurl, {
                    action: '_wppayform_pro_license_activate_license',
                    _wppayform_pro_license_key: this.licenseKey
                })
                    .then(response => {
                        if (response.data && response.data.message) {
                            jQuery('.error_notice_wppayform_pro_license').remove();
                            this.is_valid = 'valid';
                        } else {
                            this.error_message = 'Something is wrong when contacting with license server. Please make sure you have curl installed you server';
                        }
                        this.doing_ajax = false;
                    })
                    .fail(error => {
                        if (error.responseJSON && error.responseJSON.data) {
                            this.error_message = error.responseJSON.data.message;
                        } else {
                            this.error_message = 'Sorry, Something is wrong! Please make sure curl / allow_url_fopen is activated on php.ini file installed on your server';
                        }
                        this.doing_ajax = false;
                    })
                    .always(() => {
                        this.doing_ajax = false;
                    });
            },
            deactivateLicense() {
                this.doing_ajax = true;
                this.error_message = '';
                jQuery.post(ajaxurl, {
                    action: '_wppayform_pro_license_deactivate_license'
                })
                    .then(response => {
                        this.is_valid = false;
                    })
                    .fail(error => {
                        this.error_message = error.responseJSON.data.message;
                    })
                    .always(() => {
                        this.doing_ajax = false;
                    });

            },
            get_license_info() {
                this.checkingLicense = true;
                this.error_message = '';
                jQuery.get(ajaxurl, {
                    action: '_wppayform_pro_license_get_license_info'
                })
                    .then(response => {
                        this.renewLicenseHtml = response.data.renewHtml;
                        this.is_valid = response.data.status;
                        this.renewHtml = response.data.renewHtml;
                    })
                    .fail(error => {
                        this.error_message = error.responseJSON.data.message;
                    })
                    .always(() => {
                        this.checkingLicense = false;
                    });
            }
        },
        mounted() {
            this.get_license_info();
        }
    }
</script>

<style lang="scss">
    .license_form {
        text-align: center;
        padding: 20px 0px;
        label {
            font-size: 30px;
            font-weight: normal;
            display: block;
            margin-bottom: 20px;
            text-transform: capitalize;
        }
        .form_input input {
            min-width: 400px;
            height: 48px;
            width: 50%;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid gray;
            background: #fbfdff;
            font-size: 20px;
            padding: 0px 10px;
        }
        .license_submit {

        }
        .error_message {
            margin-top: 40px;
            padding: 10px;
            background: #ffe491;
            color: #000000;
            font-weight: bold;
            border-radius: 5px;
        }
    }

    .license_success {
        text-align: center;
        padding: 40px 0px;
    }
</style>
