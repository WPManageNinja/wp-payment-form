<template>
    <div v-loading="loading" class="wpf_method_settings">
        <div class="wpf_pre_settings_wrapper" v-if="!coupon_status">
            <h2>WpPayment Forms Coupon Module</h2>
            <p>Enable your users to apply coupon/discount code while purchasing something using WpPayment Forms Payment Module. Just activate this module and setup your your coupons.</p>
            <el-button @click="enableCouponModule()" type="success" size="large">Enable WpPayment Forms Coupon Module</el-button>
        </div>
        <div v-else>
            <el-row class="setting_header">
                <el-col :md="18">
                    <h2>Available Coupons</h2>
                </el-col>
                <el-col :md="6">
                    <div class="pull-right">
                        <el-button @click="showAddCoupon()" type="primary" size="small">Add New Coupon</el-button>
                    </div>
                </el-col>
            </el-row>
            <el-table :data="coupons" :row-class-name="activeCoupon" stripe>
                <el-table-column width="100" label="ID" prop="id" />
                <el-table-column label="Title" prop="title" />
                <el-table-column label="Code" prop="code" />
                <el-table-column width="140" label="Amount">
                    <template slot-scope="scope">
                        {{scope.row.amount}}<span v-if="scope.row.coupon_type == 'percent'">%</span>
                    </template>
                </el-table-column>
                <el-table-column width="140" label="Status">
                    <template slot-scope="scope">
                        {{scope.row.status}}
                    </template>
                </el-table-column>
                <el-table-column width="140" label="Actions">
                    <template slot-scope="scope">
                        <el-button-group>
                            <el-button @click="editCoupon(scope.row)" type="info" size="mini" icon="el-icon-edit"></el-button>
                            <el-button @click="deleteCoupon(scope.row)" type="danger" size="mini" icon="el-icon-delete"></el-button>
                        </el-button-group>
                    </template>
                </el-table-column>
            </el-table>
            <div style="margin-top: 20px" class="wpf_pagination pull-right">
                <pagination :pagination="pagination" @fetch="getCoupons" />
            </div>

        </div>
        <el-dialog
            top="40px"
            :title="(editing_coupon.id) ? 'Edit Coupon' : 'Add a new Coupon'"
            :visible.sync="show_modal"
            :append-to-body="true"
            width="60%">
            <div v-if="show_modal" class="wpf_coupon_form">
                <el-form :data="editing_coupon" label-position="top">
                    <el-form-item label="Coupon Title">
                        <el-input type="text" v-model="editing_coupon.title" placeholder="Coupon Title" />
                        <p>The name of this discount</p>
                    </el-form-item>
                    <el-form-item label="Coupon Code">
                        <el-input type="text" v-model="editing_coupon.code" placeholder="Coupon Code" />
                        <p>Enter a code for this discount, such as 10PERCENT. Only alphanumeric characters are allowed.</p>
                    </el-form-item>
                    <el-row :gutter="30">
                        <el-col :span="12">
                            <el-form-item label="Discount Amount / Percent">
                                <el-input placeholder="Discount Amount / Percent" type="number" v-model="editing_coupon.amount" :min="0" />
                                <p v-if="editing_coupon.coupon_type == 'percent'">Enter the discount percentage. 10 = 10%</p>
                            </el-form-item>
                        </el-col>
                        <el-col :span="12">
                            <el-form-item label="Discount Type">
                                <el-radio-group v-model="editing_coupon.coupon_type">
                                    <el-radio label="percent">Percent based discount</el-radio>
                                    <el-radio label="fixed">Fixed Discount</el-radio>
                                </el-radio-group>
                                <p>The kind of discount to apply for this discount.</p>
                            </el-form-item>
                        </el-col>
                    </el-row>
                    <el-row :gutter="30">
                        <el-col :span="12">
                            <el-form-item label="Min Purchase Amount">
                                <el-input placeholder="Min Purchase Amount" type="number" v-model="editing_coupon.min_amount" :min="0" />
                                <p>The minimum amount that must be purchased before this discount can be used. Leave blank for no minimum.</p>
                            </el-form-item>
                        </el-col>
                        <el-col :span="12">
                            <el-form-item label="Stackable">
                                <el-radio-group v-model="editing_coupon.stackable">
                                    <el-radio label="yes">Yes</el-radio>
                                    <el-radio label="no">No</el-radio>
                                </el-radio-group>
                                <p>Can this coupon code can be used with other coupon code</p>
                            </el-form-item>
                        </el-col>
                    </el-row>
                    <el-row :gutter="30">
                        <el-col :span="12">
                            <el-form-item label="Start Date">
                                <el-date-picker value-format="yyyy-MM-dd" format="yyyy-MM-dd" placeholder="Start Date" v-model="editing_coupon.start_date"  />
                                <p>Enter the start date for this discount code in the format of yyyy-mm-dd. For no start date, leave blank.</p>
                            </el-form-item>
                        </el-col>
                        <el-col :span="12">
                            <el-form-item label="End Date">
                                <el-date-picker value-format="yyyy-MM-dd" format="yyyy-MM-dd" placeholder="End Date" v-model="editing_coupon.expire_date" />
                                <p>Enter the expiration date for this discount code in the format of yyyy-mm-dd. For no expiration, leave blank</p>
                            </el-form-item>
                        </el-col>
                    </el-row>

                    <el-form-item label="Applicable Forms">
                        <el-select placeholer="Select Forms" style="width: 100%;" multiple v-model="editing_coupon.settings.allowed_form_ids">
                            <el-option v-for="(formName, formId) in available_forms" :key="formId" :label="formName" :value="formId"></el-option>
                        </el-select>
                        <p>Leave blank for applicable for all payment forms</p>
                    </el-form-item>

                    <el-form-item label="Status">
                        <el-radio-group v-model="editing_coupon.status">
                            <el-radio label="active">Active</el-radio>
                            <el-radio label="inactive">Inactive</el-radio>
                        </el-radio-group>
                    </el-form-item>
                </el-form>
                <div v-if="errors">
                    <ul style="color: red;">
                        <li v-for="(error, i) in errors" :key="i">{{Object.values(error).join(', ')}}</li>
                    </ul>
                </div>
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button type="primary" v-loading="saving" @click="saveCoupon()">Save Coupon</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
    import Pagination from './_Pagination'
    export default {
        name: 'Coupons',
        components: {
            Pagination
        },
        data() {
            return {
                loading: false,
                saving: false,
                coupons: [],
                coupon_status: true,
                pagination: {
                    current_page: 1,
                    total: 0,
                    per_page: 10
                },
                editing_coupon: {},
                show_modal: false,
                available_forms: {},
                errors: false
            }
        },
        methods: {
            activeCoupon({row, index}) {
                if (row.status == 'active') {
                    return 'active-coupon-color';
                } else {
                    return 'inactive-coupon-color';
                }
            },
            getCoupons() {
                this.loading = true;
                this.$get({
                    action: 'wppayform_coupons_action',
                    route: 'get_coupons'
                })
                    .then(response => {
                        this.coupon_status = response.coupon_status;
                        if(response.coupon_status) {
                            this.coupons = response.coupons;
                            this.pagination.total = response.coupons.total;
                            if(response.available_forms) {
                                this.available_forms = response.available_forms;
                            }
                        }
                    })
                    .fail(error => {
                        this.$notify.error(error.responseJSON.message);
                    })
                    .always(() => {
                        this.loading = false;
                    })
            },
            enableCouponModule() {
                this.loading = true;
                this.$get({
                    action: 'wppayform_coupons_action',
                    route: 'enable_coupons'
                })
                    .then(response => {
                        this.coupon_status = response.coupon_status;
                        this.getCoupons();
                    })
                    .fail(error => {
                        this.$notify.error(error.responseJSON.message);
                    })
                    .always(() => {
                        this.loading = false;
                    });
            },
            showAddCoupon() {
                this.editing_coupon = {
                    title: '',
                    code: '',
                    amount: '',
                    coupon_type: 'percent',
                    status: 'active',
                    stackable: 'no',
                    settings: {
                        allowed_form_ids: [],
                    },
                    min_amount: '',
                    max_use: '',
                    start_date: '',
                    expire_date: ''
                }
                this.show_modal = true;
            },
            saveCoupon() {
                this.saving = true;
                this.errors = false;
                this.$post({
                    action: 'wppayform_coupons_action',
                    route: 'save_coupon',
                    coupon: this.editing_coupon
                })
                    .then(response => {
                        this.getCoupons();
                        this.show_modal = false;
                        this.editing_coupon = {};
                        this.$notify.success(response.message);
                    })
                    .fail(error => {
                        this.$notify.error(error.responseJSON.message);
                        this.errors = error.responseJSON.errors;
                    })
                    .always(() => {
                        this.saving = false;
                    });
            },
            editCoupon(coupon) {
                const editing_coupon = JSON.parse(JSON.stringify(coupon));
                if(!editing_coupon.settings && !editing_coupon.settings.allowed_form_ids) {
                    if(!editing_coupon.settings) {
                        editing_coupon.settings = {};
                    }
                    editing_coupon.settings.allowed_form_ids = [];
                }
                this.$set(this, 'editing_coupon', editing_coupon);
                this.$nextTick(() => {
                    this.show_modal = true;
                });

            },
            deleteCoupon(coupon) {
                this.loading = true;
                this.$post({
                    action: 'wppayform_coupons_action',
                    route: 'delete_coupon',
                    coupon_id: coupon.id
                })
                    .then(response => {
                        this.getCoupons();
                        this.$notify.success(response.message);
                    })
                    .fail(error => {

                    })
                    .always(() => {
                        this.loading = false;
                    });
            }
        },
        mounted() {
            this.getCoupons();
        }
    }
</script>


<style lang="scss">
.wpf_coupon_form {
    .el-form-item {
        > label {
            font-weight: 500;
            line-height: 100%;
        }

        p {
            margin-top: 5px;
            color: gray;
            font-size: 12px;
        }
    }
}
.wpf_method_settings {
    .pull-right {
        float: right;
    }
    .active-coupon-color {
        color: #4caf50;
    }
    .inactive-coupon-color {
        color: #909394;
    }
}

</style>
