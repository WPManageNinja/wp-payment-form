<template>
    <div class="wpf_payment_view">

        <div class="payment_header">
            <div v-if="parseInt(submission.order_items.length)" class="payment_head_top">
                <div class="payment_header_left">
                    <p class="head_small_title">Payment</p>
                    <div class="head_payment_amount">
                        <span class="pay_amount">{{ getFormattedMoney(submission.payment_total) }} </span>
                        <span class="payment_currency">{{submission.currency}}</span>
                        <span :class="'wpf_paystatus_badge wpf_pay_status_'+submission.payment_status">
                            <i :class="getPaymentStatusIcon(submission.payment_status)"></i> {{submission.payment_status}}
                        </span>
                    </div>
                </div>
                <div class="payment_header_right">
                    <p class="head_small_title">{{ firstTransaction.charge_id }}</p>
                    <a v-if="firstTransaction.transaction_url" target="_blank" :href="firstTransaction.transaction_url" class="el-button el-button--default el-button--mini">View on {{ firstTransaction.payment_method }} dashboard</a>
                </div>
            </div>
            <div class="payment_head_bottom">
                <div class="info_block">
                    <div class="info_header">Date</div>
                    <div class="info_value">{{submission.created_at}}</div>
                </div>
                <div class="info_block">
                    <div class="info_header">Email</div>
                    <div class="info_value">
                        <span v-if="submission.customer_email"><a target="_blank" :href="'mailto:'+submission.customer_email">{{submission.customer_email}}</a></span>
                        <span v-else>n/a</span>
                    </div>
                </div>
                <div class="info_block">
                    <div class="info_header">Name</div>
                    <div class="info_value">
                        <span v-if="submission.customer_name">{{submission.customer_name}}</span>
                        <span v-else>n/a</span>
                    </div>
                </div>
                <div v-if="submission.payment_method" class="info_block">
                    <div class="info_header">Payment Method</div>
                    <div class="info_value">{{submission.payment_method}}</div>
                </div>
            </div>
        </div>

        <div class="entry_info_box">
            <div class="entry_info_header">
                <div class="info_box_header">Form Entry Data</div>
                <div class="info_box_header_actions">
                    <el-checkbox true-label="yes" false-label="no" v-model="show_empty">Show empty fields</el-checkbox>
                </div>
            </div>
            <div class="entry_info_body">
                <div class="wpf_entry_details">
                    <div v-for="(entry, entry_id) in entry_items" v-show="show_empty == 'yes' || entry.value"
                         :key="entry_id" class="wpf_each_entry">
                        <div class="wpf_entry_label">
                            {{entry.label}}
                        </div>
                        <div class="wpf_entry_value" v-html="entry.value"></div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="parseInt(submission.order_items.length)" class="entry_info_box">
            <div class="entry_info_header">
                <div class="info_box_header">Order Items</div>
            </div>
            <div class="entry_info_body">
                <div class="wpf_entry_order_items">
                    <table class="wp-list-table widefat striped">
                        <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Item Price</th>
                            <th>Line Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="item in submission.order_items">
                            <td>{{item.item_name}}</td>
                            <td>{{item.quantity}}</td>
                            <td>{{getFormattedMoney(item.item_price)}}</td>
                            <td>{{getFormattedMoney(item.line_total)}}</td>
                        </tr>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th style="text-align: right" colspan="3">Total:</th>
                            <th>{{ getFormattedMoney(orderTotal) }}</th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="parseInt(submission.order_items.length)" class="entry_info_box">
            <div class="entry_info_header">
                <div class="info_box_header">Transaction Details</div>
            </div>
            <div class="entry_info_body">
                <div class="wpf_entry_transactions">
                    <div v-for="(transaction,index) in submission.transactions" class="wpf_entry_transaction">
                        <h4 v-show="submission.transactions.length > 1">Transaction #{{ index+1 }}</h4>
                        <ul class="wpf_list_items">
                            <li>
                                <div class="wpf_list_header">Transaction ID</div>
                                <div class="wpf_list_value">{{ transaction.id }}</div>
                            </li>
                            <li>
                                <div class="wpf_list_header">Payment Method</div>
                                <div class="wpf_list_value">{{ transaction.payment_method }}</div>
                            </li>
                            <li v-if="transaction.charge_id">
                                <div class="wpf_list_header">Charge ID</div>
                                <div class="wpf_list_value">{{ transaction.charge_id }}</div>
                            </li>
                            <li v-show="transaction.card_last_4">
                                <div class="wpf_list_header">Card Last 4</div>
                                <div class="wpf_list_value"><span
                                    class="wpf_card_badge">{{ transaction.card_brand }}</span> <i
                                    class="el-icon-more"></i> {{ transaction.card_last_4 }}
                                </div>
                            </li>
                            <li>
                                <div class="wpf_list_header">Payment Total</div>
                                <div class="wpf_list_value">{{ getFormattedMoney(transaction.payment_total) }}</div>
                            </li>
                            <li>
                                <div class="wpf_list_header">Payment Status</div>
                                <div class="wpf_list_value">{{ transaction.status }}</div>
                            </li>
                            <li>
                                <div class="wpf_list_header">Date</div>
                                <div class="wpf_list_value">{{ transaction.created_at }}</div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <pre>
            {{submission}}
        </pre>
    </div>

</template>

<script type="text/babel">
    import each from 'lodash/each';
    import formatMoney from 'accounting-js/lib/formatMoney.js'

    export default {
        name: "Entry",
        data() {
            return {
                submission: {},
                entry_id: this.$route.params.entry_id,
                entry_items: {},
                form_id: 0,
                fething: false,
                show_empty: false
            }
        },
        watch: {
            show_empty() {
                this.setStoreData('show_empty_entry_field', this.show_empty);
            }
        },
        computed: {
            orderTotal() {
                let total = 0;
                each(this.submission.order_items, (item) => {
                    total = total = parseInt(item.line_total);
                });
                return total;
            },
            firstTransaction() {
                if(this.submission.transactions && this.submission.transactions.length) {
                    return this.submission.transactions[0];
                }
                return false;
            }
        },
        methods: {
            getEntry() {
                this.fething = true;
                const query = {
                    action: 'wpf_get_submission',
                    form_id: this.form_id,
                    submission_id: this.entry_id
                }
                this.$get(query)
                    .then(response => {
                        this.submission = response.data.submission;
                        this.entry_items = response.data.entry;
                    })
                    .always(() => {
                        this.fething = false;
                    });
            },
            getFormattedMoney(amount) {
                if (!amount) {
                    return 'n/a';
                }
                amount = amount / 100;
                return formatMoney(amount);
            },
            getPaymentStatusIcon(status) {
                if (status == 'pending') {
                    return 'el-icon-time';
                } else if (status == 'paid') {
                    return 'el-icon-check';
                } else if (status == 'failed') {
                    return 'el-icon-error';
                }
                return '';
            },
        },
        mounted() {
            if (this.$route.query.form_id) {
                this.form_id = parseInt(this.$route.query.form_id);
            }
            this.getEntry();
            this.show_empty = this.getFromStore('show_empty_entry_field', false);
        }
    }
</script>
