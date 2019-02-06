<template>
    <div v-loading="fetching">
        <div class="all_payforms_wrapper payform_section">
            <div class="payform_section_header">
                <h3 class="payform_section_title">
                    {{ $t('Currency Settings') }}
                </h3>
                <div class="payform_section_actions">
                    <el-button v-loading="saving" @click="saveSettings()" class="payform_action" size="small"
                               type="primary">
                        {{ $t( 'Save Currency Settings' ) }}
                    </el-button>
                </div>
            </div>
            <div class="payform_section_body">
                <el-form rel="currency_settings" :model="settings" label-width="220px">
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

                    <div class="action_right">
                        <el-button @click="saveSettings()" type="primary" size="small">Save Currency Settings</el-button>
                    </div>

                </el-form>
            </div>
        </div>
    </div>
</template>
<script type="text/babel">
    export default {
        name: 'stripe_currency_settings',
        data() {
            return {
                settings: {},
                fetching: false,
                saving: false,
                currencies: {},
                locales: {},
                currency_sign_positions: {
                    left: 'Left ($100)',
                    right: 'Right (100$)',
                    left_space: 'Left Space ($ 100)',
                    right_space: 'Right Space 100 $'
                }
            }
        },
        methods: {
            getSettings() {
                this.fetching = true;
                this.$adminGet({
                    route: 'get_global_currency_settings'
                })
                    .then(response => {
                        this.settings = response.data.currency_settings;
                        this.currencies = response.data.currencies;
                        this.locales = response.data.locales;
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
                this.$adminPost({
                    route: 'update_global_currency_settings',
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
        },
        mounted() {
            this.getSettings();
        }
    }
</script>