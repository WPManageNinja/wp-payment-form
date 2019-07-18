<template>
    <div class="tabular_products_wrapper">
        <h3 style="margin:0 0 20px">{{item.label}}</h3>
        <el-form-item class="wpf_line_radios" :label="item.choice_label">
            <el-radio-group @change="checkPricingPlans()" v-model="product_settings.choice_type">
                <el-radio
                    v-for="(label, type) in item.choice_types"
                    :key="type"
                    :label="type">
                    {{ label }}
                </el-radio>
            </el-radio-group>
        </el-form-item>

        <el-form-item v-if="product_settings.choice_type == 'choose_single'" class="wpf_line_radios"
                      label="Plan Selection Type">
            <el-radio-group v-model="product_settings.selection_type">
                <el-radio
                    v-for="(label, type) in item.selection_types"
                    :key="type"
                    :label="type">
                    {{ label }}
                </el-radio>
            </el-radio-group>
        </el-form-item>


        <h4>Pricing Plans</h4>
        <div class="wpf_plan_cards">
            <div class="wpf_plans" v-for="(item, index) in product_settings.pricing_options">
                <div class="plan_header">
                    <div class="plan_label">
                        #{{index+1}}: {{item.name}}
                    </div>
                    <div class="plan_actions">
                        <template v-if="product_settings.choice_type != 'simple'">
                            Default:
                            <el-switch
                                @change="changeDefaultItem(index)"
                                v-model="item.is_default"
                                active-value="yes"
                                inactive-value="no">
                            </el-switch>
                        </template>

                        <el-button @click="deleteItem(index)" size="mini"
                                   v-show="product_settings.pricing_options.length > 1" type="danger"
                                   icon="el-icon-delete"></el-button>
                    </div>
                </div>
                <div class="plan_body">
                    <div class="plan_settings plan_left_area el-form--label-top">
                        <el-form-item label="Plan Name">
                            <el-input placeholder="Item Name" size="mini" v-model="item.name"
                                      type="text"></el-input>
                        </el-form-item>
                        <el-form-item label="Price/Billing Interval">
                            <el-input-number :disabled="item.user_input == 'yes'" size="small" v-model="item.subscription_amount" :min="0"></el-input-number>
                            <el-checkbox true-label="yes" false-label="no" v-model="item.user_input">Enable User Input Amount</el-checkbox>
                        </el-form-item>

                        <template v-if="item.user_input == 'yes'">
                            <el-form-item label="User Input Amount label">
                                <el-input placeholder="ex: Please Provide amount/interval" size="mini" v-model="item.user_input_label"
                                          type="text"></el-input>
                            </el-form-item>
                            <el-form-item class="inline_two_item">
                                <label>Minimum Amount
                                    <el-input-number size="mini" v-model="item.user_input_min_value"></el-input-number>
                                </label>
                                <label>Default Value Amount
                                    <el-input-number size="mini" v-model="item.user_input_default_value"></el-input-number>
                                </label>
                            </el-form-item>
                        </template>

                        <el-form-item label="Billing Interval">
                            <el-select size="mini" v-model="item.billing_interval" placeholder="Select">
                                <el-option
                                    v-for="(label,value) in interval_options"
                                    :key="value"
                                    :label="label"
                                    :value="value">
                                </el-option>
                            </el-select>
                        </el-form-item>
                    </div>
                    <div class="plan_settings plan_right_area el-form--label-top">
                        <el-form-item label="Has Signup Fee?">
                            <el-switch
                                v-model="item.has_signup_fee"
                                active-value="yes"
                                :disabled="item.has_trial_days == 'yes'"
                                inactive-value="no">
                            </el-switch>
                            <el-input-number placeholder="Signup Fee" v-if="item.has_signup_fee == 'yes'" size="mini"
                                             v-model="item.signup_fee" :min="0"></el-input-number>
                        </el-form-item>

                        <el-form-item label="Has Trial Days? (in days)">
                            <el-switch
                                v-model="item.has_trial_days"
                                :disabled="item.has_signup_fee == 'yes'"
                                active-value="yes"
                                inactive-value="no">
                            </el-switch>
                            <el-input-number v-if="item.has_trial_days == 'yes'" placeholder="Trial Days" size="mini"
                                             v-model="item.trial_days" :min="0"></el-input-number>
                        </el-form-item>

                        <el-form-item label="Total Billing times">
                            <el-input-number placeholder="Trial Days" size="mini" v-model="item.bill_times"
                                             :min="0"></el-input-number>
                            <p>Keep blank or 0 for billing unlimited period of times</p>
                        </el-form-item>
                    </div>
                </div>
                <div class="plan_footer">
                    <span v-html="getAdvancedText(item)"></span>
                </div>
            </div>

            <div v-if="product_settings.choice_type != 'simple'" style="text-align: right;" class="wpf_plan_actions">
                <el-button @click="add" size="mini">Add New Plan</el-button>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
    import each from 'lodash/each';

    export default {
        name: 'recurring_product_template',
        props: ['item', 'product_settings'],
        data() {
            return {
                interval_options: {
                    daily: 'Daily',
                    week: 'Weekly',
                    month: 'Monthly',
                    year: 'Yearly'
                }
            }
        },
        methods: {
            deleteItem(index) {
                this.product_settings.pricing_options.splice(index, 1);
            },
            add() {
                this.product_settings.pricing_options.push({
                    name: 'Plan Name Here',
                    trial_days: 0,
                    has_trial_days: 'no',
                    trial_preriod_days: 0,
                    billing_interval: 'month',
                    bill_times: 0,
                    has_signup_fee: 'no',
                    signup_fee: 0,
                    subscription_amount: '19.99',
                    plan_features: []
                });
            },
            getAdvancedText(item) {

                let billAmount = item.subscription_amount;

                if(item.user_input == 'yes') {
                    billAmount = 'USER_INPUT_AMOUNT';
                }

                let text = `Bill <b>${billAmount}/${item.billing_interval}</b> `;

                if (item.has_trial_days == 'yes') {
                    text += `with ${item.trial_days} trial days `;
                }
                if (item.has_signup_fee == 'yes') {
                    text += `and Inital <b>Signup Fee ${item.signup_fee}</b> `;
                }
                if (parseInt(item.bill_times)) {
                    text += `and Total <b>Billing times ${item.bill_times}</b> `;
                } else {
                    text += `and will be billed untill cancel`;
                }
                return text;
            },
            changeDefaultItem(index) {
                each(this.product_settings.pricing_options, (option, itemIndex) => {
                    if (itemIndex != index) {
                        option.is_default = 'no';
                    }
                });
            },
            checkPricingPlans() {
                if (this.product_settings.choice_type == 'simple' && this.product_settings.pricing_options.length > 1) {
                    this.product_settings.pricing_options = [this.product_settings.pricing_options[0]];
                }
            }
        }
    }
</script>

<style lang="scss">
    .plan_settings.el-form--label-top {
        .el-form-item__content {
            margin-left: 0 !important;
        }
    }

    .wpf_plans {
        display: block;
        width: 100%;
        border: 1px solid #dcdfe6;
        border-radius: 4px;
        margin-bottom: 20px;
        .plan_header {
            padding: 10px 15px;
            border-bottom: 1px solid #dcdfe6;
            font-weight: bold;
            display: block;
            overflow: hidden;
            .plan_actions {
                float: right;
                button {
                    padding: 4px 10px;
                }
            }

            .plan_label {
                float: left;
            }
        }
        .plan_body {
            overflow: hidden;
            padding: 15px;
            background: white;
        }
        .plan_settings {
            width: 50%;
            display: block;
            float: left;
            padding-right: 50px;
        }
        .plan_footer {
            border-top: 1px solid #dcdfe6;
            padding: 10px 15px;
            p {
                padding: 0;
                margin: 0;
            }
        }
    }


</style>
