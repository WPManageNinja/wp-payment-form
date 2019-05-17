<template>
    <div>
        <div class="all_payforms_wrapper payform_section wpf_min_width">
            <div class="payform_section_header">
                <h3 class="payform_section_title">
                    {{ $t('General Settings') }}
                </h3>
                <div class="payform_section_actions">
                    <el-button v-loading="saving" @click="saveSettings()" class="payform_action" size="small"
                               type="primary">
                        {{ $t( 'Save Settings' ) }}
                    </el-button>
                </div>
            </div>
            <div v-loading="fetching" class="payform_section_body">
                <el-form rel="currency_settings" :label-position="labelPosition" :model="settings" label-width="220px">
                    <div class="wpf_settings_section">
                        <div class="sub_section_header">
                            <h3>{{ $t('Currency Settings') }}</h3>
                        </div>
                        <div class="sub_section_body">
                            <el-form-item label="Stripe Checkout Locale">
                                <el-select size="small" filterable v-model="settings.locale"
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
                                <el-select size="small" filterable v-model="settings.currency" placeholder="Select Currency">
                                    <el-option
                                        v-for="(currencyName, currenyKey) in currencies"
                                        :key="currenyKey"
                                        :label="currencyName"
                                        :value="currenyKey">
                                    </el-option>
                                </el-select>
                            </el-form-item>
                            <el-form-item label="Currency Sign Position">
                                <el-radio-group v-model="settings.currency_sign_position">
                                    <el-radio v-for="(sign, sign_key) in currency_sign_positions" :key="sign_key"
                                              :label="sign_key">{{sign}}
                                    </el-radio>
                                </el-radio-group>
                            </el-form-item>
                            <el-form-item label="Currency Separators">
                                <el-select class="item_full_width" size="small" v-model="settings.currency_separator">
                                    <el-option value="dot_comma" label="Comma as Thousand and Dot as Decimal (EG: 12,000.00)" />
                                    <el-option value="comma_dot" label="Dot as Thousand and Comma as Decimal ( EG: 12.000,00 )" />
                                </el-select>
                            </el-form-item>
                            <el-form-item label="">
                                <el-checkbox :true-label="0" :false-label="2" v-model="settings.decimal_points">Hide decimal points for rouned numbers</el-checkbox>
                            </el-form-item>
                        </div>
                    </div>
                    <div class="wpf_settings_section">
                        <div class="sub_section_header">
                            <h3>{{ $t('Other Settings') }}</h3>
                        </div>
                        <div class="sub_section_body">
                            <el-checkbox true-label="no" false-label="yes" v-model="ip_logging_status">Disable IP Address Logging (If you check this then advanced analytics can not be performed)</el-checkbox>
                        </div>
                    </div>
                </el-form>

                <div class="action_right">
                    <el-button @click="saveSettings()" type="primary" size="small">{{$t('Save Settings')}}</el-button>
                </div>
            </div>
        </div>
    </div>
</template>
<script type="text/babel">
    export default {
        name: 'global_currency_settings',
        data() {
            return {
                settings: {},
                fetching: false,
                saving: false,
                currencies: {},
                locales: {},
                labelPosition: 'right',
                currency_sign_positions: {
                    left: 'Left ($100)',
                    right: 'Right (100$)',
                    left_space: 'Left Space ($ 100)',
                    right_space: 'Right Space 100 $'
                },
                ip_logging_status: 'yes'
            }
        },
        methods: {
            getSettings() {
                this.fetching = true;
                this.$get({
                    action: 'wpf_global_settings_handler',
                    route: 'get_global_currency_settings'
                })
                    .then(response => {
                        this.settings = response.data.currency_settings;
                        this.currencies = response.data.currencies;
                        this.locales = response.data.locales;
                        this.ip_logging_status = response.data.ip_logging_status
                    })
                    .fail(error => {
                        this.$message.error(error.responseJSON.data.message);
                    })
                    .always(() => {
                        this.fetching = false;
                    })
            },
            saveSettings() {
                this.saving = true;
                this.$post({
                    action: 'wpf_global_settings_handler',
                    route: 'update_global_currency_settings',
                    settings: this.settings,
                    ip_logging_status: this.ip_logging_status
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
        },
        mounted() {
            this.getSettings();
            window.WPPayFormsBus.$emit('site_title', 'General Settings');
            if(window.outerWidth < 500) {
                this.labelPosition = "top";
            }
        }
    }
</script>