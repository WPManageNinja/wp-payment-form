<template>
    <el-container>
        <el-main class="no_shadow">
            <div class="edit_form_warpper">
                <div class="all_payforms_wrapper payform_section">
                    <div class="payform_section_header">
                        <h3 class="payform_section_title">
                            {{ $t('Currency and Language Settings') }}
                        </h3>
                        <div class="payform_section_actions">
                            <el-button @click="saveSettings()" class="payform_action" size="small" type="primary">
                                {{ $t( 'Save Currency Settings' ) }}
                            </el-button>
                        </div>
                    </div>
                    <div v-loading="fetching"  class="payform_section_body">
                        <el-form ref="payment_settings" :label-position="labelPosition" :model="currency_settings" label-width="220px">
                            <div class="wpf_sub_section">
                                <div class="sub_section_body">
                                    <el-form-item label="Currency & Locale Setting">
                                        <el-radio-group v-model="currency_settings.settings_type">
                                            <el-radio border label="global">{{ $t('As Per Global Settings') }}
                                            </el-radio>
                                            <el-radio border label="custom">{{ $t('Custom Settings') }}</el-radio>
                                        </el-radio-group>
                                    </el-form-item>
                                    <template v-if="currency_settings.settings_type == 'custom'">
                                        <el-form-item label="Stripe Checkout Locale">
                                            <el-select size="small" filterable v-model="currency_settings.locale"
                                                       placeholder="Select Stripe Checkput Language">
                                                <el-option
                                                    v-for="(locale_name, locale_key) in locales"
                                                    :key="locale_key"
                                                    :label="locale_name"
                                                    :value="locale_key">
                                                </el-option>
                                            </el-select>
                                        </el-form-item>
                                        <el-form-item label="Currency">
                                            <el-select size="small" filterable v-model="currency_settings.currency"
                                                       placeholder="Select Currency">
                                                <el-option
                                                    v-for="(currencyName, currenyKey) in currencies"
                                                    :key="currenyKey"
                                                    :label="currencyName"
                                                    :value="currenyKey">
                                                </el-option>
                                            </el-select>
                                        </el-form-item>
                                    </template>
                                </div>
                            </div>
                        </el-form>
                    </div>
                </div>
            </div>
        </el-main>
    </el-container>
</template>

<script type="text/babel">
    import ConfirmationSettings from './_AddConfirmation';
    export default {
        name: 'currency_settings',
        props: ['form_id'],
        components: {
            ConfirmationSettings
        },
        data() {
            return {
                fetching: false,
                currency_settings: {},
                app_ready: false,
                currencies: {},
                locales: {},
                labelPosition: 'right'
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
                        this.currency_settings = response.data.currency_settings;
                        this.currencies = response.data.currencies;
                        this.locales = response.data.locales;
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
                    currency_settings: this.currency_settings,
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
            window.WPPayFormsBus.$emit('site_title', 'Form Currency Settings');
            if(window.outerWidth < 500) {
                this.labelPosition = "top";
            }
        }
    }
</script>