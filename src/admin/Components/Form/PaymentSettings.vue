<template>
    <div class="edit_form_warpper">
        <div class="all_payforms_wrapper payform_section">
            <div class="payform_section_header">
                <h3 class="payform_section_title">
                    {{ $t('Payment Options') }}
                </h3>
                <div class="payform_section_actions">
                    <el-button @click="saveSettings()" class="payform_action" size="small" type="primary">
                        {{ $t( 'Save Payment Settings' ) }}
                    </el-button>
                </div>
            </div>
            <div class="payform_section_body">
                <el-form ref="payment_settings" :model="payment_settings" label-width="220px">
                    <el-form-item label="Amount Type">
                        <el-radio-group v-model="payment_settings.payment_type">
                            <el-radio label="one_time">{{ $t('One Time Payment') }}</el-radio>
                            <el-radio label="one_time_custom">{{ $t('One Time Custom Amount') }}</el-radio>
                        </el-radio-group>
                    </el-form-item>

                    <template v-if="payment_settings.payment_type == 'one_time'">
                        <el-form-item label="One Time Payment Amount">
                            <el-input-number size="small" v-model="payment_settings.payment_amount" :min="0"></el-input-number>
                        </el-form-item>
                    </template>
                    <template v-if="payment_settings.payment_type == 'one_time_custom'">
                        <el-form-item label="Minimum Custom Amount">
                            <el-input-number size="small" v-model="payment_settings.min_amount" :min="0"></el-input-number>
                        </el-form-item>
                        <el-form-item label="Default Custom Amount">
                            <el-input-number size="small" v-model="payment_settings.default_amount" :min="0"></el-input-number>
                        </el-form-item>
                        <el-form-item label="Custom Amount Label">
                            <el-input size="small" placeholder="Custom Amount Label" v-model="payment_settings.custom_amount_label"></el-input>
                        </el-form-item>
                    </template>

                    <el-form-item label="Currency & Locale Setting">
                        <el-radio-group v-model="payment_settings.currency_setting">
                            <el-radio label="global">{{ $t('As Per Global Settings') }}</el-radio>
                            <el-radio label="custom">{{ $t('Custom Settings') }}</el-radio>
                        </el-radio-group>
                    </el-form-item>

                    <template v-if="payment_settings.currency_setting == 'custom'">
                        <el-form-item label="Stripe Checkout Locale">
                            <el-select size="small" filterable v-model="payment_settings.custom_locale" placeholder="Select Checkput Language">
                                <el-option
                                    v-for="(locale_name, locale_key) in locales"
                                    :key="locale_key"
                                    :label="locale_name"
                                    :value="locale_key">
                                </el-option>
                            </el-select>
                        </el-form-item>

                        <el-form-item label="Currency">
                            <el-select size="small" filterable v-model="payment_settings.custom_currency" placeholder="Select Currency">
                                <el-option
                                    v-for="(currencyName, currenyKey) in currencies"
                                    :key="currenyKey"
                                    :label="currencyName"
                                    :value="currenyKey">
                                </el-option>
                            </el-select>
                        </el-form-item>
                    </template>
                </el-form>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'payment_settings',
        props: ['form_id'],
        data() {
            return {
                payment_settings: {},
                fetching: false,
                currencies: {},
                locales: {}
            }
        },
        methods: {
            getSettings() {
                this.fetching = true;
                this.$adminGet({
                    route: 'get_payment_settings',
                    form_id: this.form_id
                })
                    .then(response => {
                        this.payment_settings = response.data.payment_settings;
                        this.currencies = response.data.currencies;
                        this.locales = response.data.locales;
                    })
                    .fail(error => {

                    })
                    .always(() => {
                        this.fetching = false;
                    })
            },
            saveSettings() {
                this.saving = true;
                this.$adminPost({
                    action: 'save_form_settings',
                    form_id: this.form_id,
                    settings: this.payment_settings,
                    settings_key: '_wp_paymentform_payment_settings',
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
            this.getSettings();
        }
    }
</script>