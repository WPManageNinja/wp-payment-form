<template>
    <div class="notification_edit">
        <el-form v-if="editingDiscountIndex != null" ref="discountForm" class="payform_hide_valdate_messages" :show-message="showErrorMessage" :rules="rules" label-position="top" :model="form" label-width="120px">
            <el-form-item prop="title" label="Discount Title">
                <el-input size="small" placeholder="Discount Title" v-model="form.title"></el-input>
            </el-form-item>
            <el-form-item prop="discount_code" class="payform_item_half" label="Discount Code">
                    <el-input size="small" placeholder="Code" v-model="form.discount_code">
                        <popover
                            @command="(code) => { form.discount_code += code }"
                            slot="suffix" :data="merge_tags"
                            btnType="text"
                            buttonText='<i class="el-icon-menu"></i>'>
                        </popover>
                    </el-input>
            </el-form-item>
            <el-form-item prop="type" class="payform_item_half" label="Discount Type">
                     <el-select class="select-width-full" size="small" v-model="form.type" placeholder="Select">
                        <el-option
                        v-for="item in typeOptions"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value">
                        </el-option>
                    </el-select>
            </el-form-item>
            <el-form-item prop="amount" v-if="form.type === 'fixed'" class="payform_item_half" label="Amount">
                    <el-input type="number" size="small" placeholder="Amount" v-model="form.amount">
                        <template slot="append">$</template>
                    </el-input>
            </el-form-item>
            <el-form-item  v-else prop="percent" class="payform_item_half" label="Percent">
                    <el-input type="number" size="small" placeholder="Code" v-model="form.percent">
                        <template slot="append">%</template>
                    </el-input>
            </el-form-item>
            <el-form-item prop="exclude_forms" class="payform_item_half" label="Exclude form">
                     <el-select multiple class="select-width-full" size="small" v-model="form.exclude_forms" placeholder="Select">
                        <el-option
                        v-for="item in allForms"
                        :key="item.ID"
                        :label="item.post_title"
                        :value="item.ID">
                        </el-option>
                    </el-select>
            </el-form-item>
            <el-form-item prop="expired_at" class="payform_item_half" label="Expired at">
                <el-date-picker
                    size="small"
                    class="select-width-full"
                    v-model="form.expired_at"
                    type="datetime"
                    format="yyyy-MM-dd"
                    placeholder="Select date and time"
                    :picker-options="pickerOptions">
                </el-date-picker>
            </el-form-item>
            <el-form-item prop="min_purchase" class="payform_item_half" label="Min Purchase">
                    <el-input type="number" size="small" placeholder="minimum purchase required to apply this code" v-model="form.min_purchase">
                        <template slot="append">$</template>
                    </el-input>
            </el-form-item>
            <el-form-item class="submit_btn_right">
                <el-button size="small" type="primary" @click="update('discountForm')">Update</el-button>
            </el-form-item>
        </el-form>
    </div>
</template>

<script type="text/babel">
    import popover from '../../Common/input-popover-dropdown.vue'

    export default {
        name: 'editDiscount',
        components: {
            popover
        },
        props: ['discount', 'editingDiscountIndex', 'merge_tags', 'discount_actions'],
        data() {
            return {
                form: {},
                showAdvanced: '',
                typeOptions:[
                    {
                        label: 'Percent',
                        value: 'percent'
                    },
                                        {
                        label: 'Fixed',
                        value: 'fixed'
                    }
                ],
                allForms: [],
                rules: {
                    title: [
                        {
                            required: true, message: 'Please Provide Discount Title',
                        }
                    ],
                    discount_code: [
                        {
                            required: true, message: 'Please Provide Email To', trigger: 'change'
                        }
                    ],
                    type: [
                        {
                            required: true, message: 'Please Provide Email Subject',
                        }
                    ],
                    amount: [
                        {
                            required: false, message: 'Please Provide Email Body',
                        }
                    ]
                },
                pickerOptions: {
                    shortcuts: [{
                        text: 'Today',
                        onClick(picker) {
                        picker.$emit('pick', new Date());
                        }
                    }]
                },
                showErrorMessage: false
            }
        },
        watch: {
            editingDiscountIndex() {
                if (this.editingDiscountIndex != null) {
                    this.showErrorMessage = false;
                    this.form = JSON.parse(JSON.stringify(this.discount));
                }
            }
        },
        methods: {
            update(formName) {
                // validate first please
                this.showErrorMessage = true;
                this.$refs[formName].validate((valid) => {
                    if (valid) {
                        // this.$emit('update:discount', JSON.parse(JSON.stringify(this.form)));
                        this.$emit('saveDiscounts', this.form);
                    } else {
                        this.$notify.error('Please provide all required fields');
                        return false;
                    }
                });
            },
            getAllForms() {

                this.$get({
                    action: 'wpf_submission_endpoints',
                    route: 'get_available_forms'
                })
                    .then(response => {
                        this.allForms = response.data.available_forms;
                    })
                    .fail(error => {

                    })
                    .always(() => {
                        // this.saving = false;
                    });
            }
        },
        mounted() {
            this.showErrorMessage = false;
            this.form = JSON.parse(JSON.stringify(this.discount));
            this.getAllForms();
        }
    }
</script>
<style lang="scss">
    .select-width-full {
        width: 100% !important;
        input {
            background: white;
        }
    }
</style>
