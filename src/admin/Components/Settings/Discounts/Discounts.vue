<template>
    <el-container>
        <el-main>
            <div class="edit_form_warpper">
                <div class="all_payforms_wrapper payform_section">
                    <div class="payform_section_header">
                        <h3 class="payform_section_title">
                            {{title}}
                        </h3>
                        <div v-show="has_pro" class="payform_section_actions">
                            <el-button v-if="editingDiscountIndex == null" @click="addDiscounts()"
                                       class="payform_action" size="small" type="primary">
                                {{ $t( 'Add New Discount' ) }}
                            </el-button>
                            <el-button @click="editingDiscountIndex = null" type="text" size="small" v-else>
                                Back
                            </el-button>
                        </div>
                    </div>
                    <template v-if="has_pro">
                        <div v-loading="loading" id="wpf_discounts" class="payform_section_body">
                            <discount-table v-if="editingDiscountIndex === null"
                                                @editDiscount="editDiscount"
                                                @deleteDiscount="deleteDiscount"
                                                @saveDiscount="saveDiscount"
                                                :discounts="discounts"/>
                            <el-container class="email_discount_editing" v-else>
                                <el-aside width="200px">
                                    <el-menu background-color="#545c64"
                                             text-color="#fff"
                                             :default-active="'index_'+editingDiscountIndex"
                                             active-text-color="#ffd04b"
                                    >
                                        <el-menu-item
                                            v-for="(discount,discountIndex) in discounts"
                                            :key="discountIndex"
                                            @click="editDiscount(discountIndex)"
                                            :index="'index_'+discountIndex">
                                            <span>{{  discount.title }}</span>
                                        </el-menu-item>
                                    </el-menu>
                                </el-aside>
                                <el-main>
                                    <edit-discount
                                        :discount_actions="discount_actions"
                                        @saveDiscounts="saveDiscount"
                                        :editingDiscountIndex="editingDiscountIndex"
                                        :discount.sync="discounts[editingDiscountIndex]"
                                        :merge_tags="merge_tags"
                                    />
                                </el-main>
                            </el-container>
                        </div>
                    </template>
                    <template v-else>
                        <div class="payform_section_body payform_upgrade_wrapper">
                            <div class="payform_upgrade_section">
                                <h1><i class="el-icon-lock"></i></h1>
                                <h3>Send automatic email when your customers submit the form. You can send email to your customers as well as to yourself.</h3>
                                <a target="_blank" :href="pro_purchase_url" class="el-button el-button--primary">Upgrade To Pro version</a>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </el-main>
    </el-container>
</template>
<script type="text/babel">
    import DiscountTable from './DiscountTable';
    import EditDiscount from './EditDiscount'

    export default {
        name: 'discounts',
        components: {
            DiscountTable,
            EditDiscount
        },
        data() {
            return {
                form_id: 691,
                discounts: [],
                loading: false,
                merge_tags: [],
                discount_actions: [],
                editingDiscountIndex: null
            }
        },
        computed: {
            title() {
                let index = this.editingDiscountIndex;
                if (index != null && this.discounts.length) {
                    let serial = index + 1;
                    return 'Editing: ' + this.discounts[this.editingDiscountIndex].title + ' (#' + serial + ')';
                }
                return 'Discounts';
            }
        },
        methods: {
            getDiscounts() {
                this.loading = true;
                this.$get({
                    action: 'wppayform_discounts_action',
                    route: 'get_discounts'
                })
                    .then(response => {
                        this.discounts = response.data.discounts;
                    })
                    .fail(error => {

                    })
                    .always(() => {
                        this.loading = false;
                    });
            },
            addDiscounts() {
                this.discounts.push({
                    id: '',
                    title: 'New Coupon',
                    discount_code: '',
                    type: 'fixed',
                    amount: '',
                    percent: '',
                    exclude_forms: '',
                    expired_at: '',
                    max_amount: '',
                    status: 'active'
                });
                this.editingDiscountIndex = this.discounts.length - 1;
            },
            editDiscount(discountIndex) {
                this.editingDiscountIndex = null;
                this.$nextTick(() => {
                    this.editingDiscountIndex = discountIndex;
                });
            },
            deleteDiscount([id, index]) {
                if (!id) {
                   return this.discounts.splice(index, 1);
                }
                this.$post({
                    action: 'wppayform_discounts_action',
                    route: 'delete',
                    id: id
                })
                    .then(response => {
                        this.$notify.success(response.data.message);
                        this.getDiscounts();
                    })
                    .fail(error => {

                    })
                    .always(() => {
                        this.saving = false;
                    });
            },
            saveDiscount(discount) {
                this.saving = true;
                this.$post({
                    action: 'wppayform_discounts_action',
                    route: 'save_discounts',
                    discounts: discount
                })
                    .then(response => {
                        this.$notify.success(response.data.message);
                        this.getDiscounts();
                        this.editingDiscountIndex = null
                    })
                    .fail(error => {

                    })
                    .always(() => {
                        this.saving = false;
                    });
            }
        },
        mounted() {
            if (this.has_pro) {
                this.getDiscounts();
            }
        }
    }
</script>
