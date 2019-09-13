<template>
    <div id="print_view">
        <div style="min-height: 300px"
             v-loading="fething"
             :class="print_hides"
             element-loading-text="Loading Entry..."
             class="wpf_payment_view">
            <template v-if="submission.id">
                <div class="payment_head_info">
                    <router-link class="payhead_nav_item payhead_back_icon"
                                 :to="{ name: 'form_entries', params: { form_id: form_id } }"><span
                        class="dashicons dashicons-admin-home"></span></router-link>
                    <div class="payhead_title">
                        {{ submission.post_title }} #{{submission.id}}
                    </div>
                    <div class="wpf_header_actions">
                        <el-button-group>
                            <el-button size="mini" @click="handleNavClick('next')" type="info"
                                       icon="el-icon-d-arrow-left">
                                Prev
                            </el-button>
                            <el-button readonly size="mini" disabled type="plain">{{submission.id}}</el-button>
                            <el-button size="mini" @click="handleNavClick('prev')" type="info">Next <i
                                class="el-icon-d-arrow-right el-icon-right"></i></el-button>
                            <el-dropdown v-if="submission.order_items && parseInt(submission.order_items.length)"
                                         @command="handleActionCommand">
                                <el-button size="mini" type="primary">
                                    Actions <i class="el-icon-arrow-down el-icon--right"></i>
                                </el-button>
                                <el-dropdown-menu slot="dropdown">
                                    <el-dropdown-item command="payment_status">
                                        Change Payment Status
                                    </el-dropdown-item>
                                </el-dropdown-menu>
                            </el-dropdown>
                        </el-button-group>
                    </div>
                </div>

                <div class="payment_header">
                    <div v-if="parseInt(submission.order_items.length) || submission.subscription_payment_total"
                         class="payment_head_top">
                        <div class="payment_header_left">
                            <p class="head_small_title">Payment</p>
                            <div class="head_payment_amount">
                                <template v-if="parseInt(submission.payment_total)">
                                    <span class="pay_amount"
                                          v-html="getFormattedMoney(submission.payment_total)"></span>
                                    <span class="payment_currency">{{submission.currency}}</span>
                                    <span :class="'wpf_paystatus_badge wpf_pay_status_'+submission.payment_status">
                                    <i :class="getPaymentStatusIcon(submission.payment_status)"></i> {{submission.payment_status}}
                                </span>
                                </template>
                                <template v-if="submission.subscription_payment_total">
                                    <span><span v-show="parseInt(submission.payment_total)"> & </span><span
                                        class="pay_amount"
                                        v-html="getFormattedMoney(submission.subscription_payment_total)"></span> (From Subscriptions)</span>
                                </template>
                            </div>
                        </div>
                        <div class="payment_header_right">
                            <p class="head_small_title">{{ firstTransaction.charge_id }}</p>
                            <a
                                v-if="isUrl(firstTransaction.transaction_url)"
                                target="_blank"
                                :href="firstTransaction.transaction_url"
                                class="el-button el-button--default el-button--mini">
                                View on {{ firstTransaction.payment_method }} dashboard
                            </a>
                            <span v-else>{{firstTransaction.payment_method}}</span>
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
                        <span v-if="submission.customer_email"><a target="_blank"
                                                                  :href="'mailto:'+submission.customer_email">{{submission.customer_email}}</a></span>
                                <span v-else>n/a</span>
                            </div>
                        </div>
                        <div class="info_block">
                            <div class="info_header">Name</div>
                            <div class="info_value">
                        <span class="wpf_capitalize" v-if="submission.customer_name">
                            <a :href="submission.user_profile_url" target="_blank" v-if="submission.user_profile_url">
                                {{submission.customer_name}}
                            </a>
                            <span v-else>
                                {{submission.customer_name}}
                            </span>
                        </span>
                                <span v-else>n/a</span>
                            </div>
                        </div>
                        <div v-if="submission.payment_method" class="info_block">
                            <div class="info_header">Payment Method</div>
                            <div class="info_value wpf_capitalize">
                                <span>{{submission.payment_method}}</span>
                            </div>
                        </div>
                        <div v-if="submission.payment_mode" class="info_block">
                            <div class="info_header">Payment Mode</div>
                            <div class="info_value wpf_capitalize">{{submission.payment_mode}}</div>
                        </div>
                    </div>
                </div>
                <div class="wpf_submisson_body" :class="(hide_sidebar == 'yes') ? 'wpf_hide_sidebar' : ''">
                    <div class="wpf_submission_details">
                        <div class="entry_info_box entry_input_data">
                            <div class="entry_info_header">
                                <div class="info_box_header">Form Entry Data</div>
                                <div class="info_box_header_actions">
                                    <el-checkbox true-label="yes" false-label="no" v-model="show_empty">Show empty fields
                                    </el-checkbox>
                                </div>
                            </div>
                            <div class="entry_info_body">
                                <div class="wpf_entry_details">
                                    <div v-for="(entry, entry_id) in entry_items"
                                         v-show="show_empty == 'yes' || entry.value"
                                         :key="entry_id" class="wpf_each_entry">
                                        <div class="wpf_entry_label">
                                            {{entry.label}}
                                        </div>
                                        <div class="wpf_entry_value" v-html="entry.value"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="parseInt(submission.order_items.length)" class="entry_info_box entry_order_items">
                            <div class="entry_info_header">
                                <div class="info_box_header">Order Items</div>
                            </div>
                            <div class="entry_info_body">
                                <div class="wpf_entry_order_items">
                                    <table class="wp-list-table widefat table table-bordered striped">
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
                                            <td v-html="getFormattedMoney(item.item_price)"></td>
                                            <td v-html="getFormattedMoney(item.line_total)"></td>
                                        </tr>
                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <th style="text-align: right" colspan="3">Sub Total:</th>
                                            <th v-html="getFormattedMoney(subTotal)"></th>
                                        </tr>
                                        <template v-if="submission.tax_items.length">
                                            <tr v-for="taxItem in submission.tax_items">
                                                <td style="text-align: right" colspan="3">
                                                    {{taxItem.item_name}}:
                                                </td>
                                                <td v-html="getFormattedMoney(taxItem.line_total)"></td>
                                            </tr>
                                        </template>
                                        <tr>
                                            <th style="text-align: right" colspan="3">Total:</th>
                                            <th v-html="getFormattedMoney(orderTotal)"></th>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <subscription-payments
                            :payment_method="submission.payment_method"
                            :payment_mode="submission.payment_mode"
                            :currencySetting="submission.currencySetting"
                            :subscriptions="submission.subscriptions"/>

                        <div v-if="parseInt(submission.transactions.length)" class="entry_info_box entry_transactions">
                            <div class="entry_info_header">
                                <div class="info_box_header">Transaction Details</div>
                            </div>
                            <div class="entry_info_body">
                                <div class="wpf_entry_transactions">
                                    <div v-for="(transaction,index) in submission.transactions"
                                         class="wpf_entry_transaction">
                                        <h4 v-show="submission.transactions.length > 1">Transaction #{{ index+1 }}</h4>
                                        <ul class="wpf_list_items">
                                            <li>
                                                <div class="wpf_list_header">ID</div>
                                                <div class="wpf_list_value">{{ transaction.id }}</div>
                                            </li>
                                            <li>
                                                <div class="wpf_list_header">Payment Method</div>
                                                <div class="wpf_list_value">
                                            <span
                                                v-if="transaction.payment_method">{{ transaction.payment_method }}</span>
                                                    <span v-else>n/a</span>
                                                </div>
                                            </li>
                                            <li v-if="transaction.charge_id">
                                                <div class="wpf_list_header">Transaction ID</div>
                                                <div class="wpf_list_value">

                                                    <a v-if="transaction.transaction_url" target="_blank"
                                                       :href="transaction.transaction_url">{{ transaction.charge_id }}</a>
                                                    <span v-else>{{ transaction.charge_id }}</span>

                                                </div>
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
                                                <div class="wpf_list_value"
                                                     v-html="getFormattedMoney(transaction.payment_total)"></div>
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


                        <div v-if="parseInt(submission.refunds.length)" class="entry_info_box entry_refunds">
                            <div class="entry_info_header">
                                <div class="info_box_header">Refunds</div>
                                <div class="info_box_header_actions">
                                    Total: <span v-html="getFormattedMoney(submission.refundTotal)"></span>
                                </div>
                            </div>
                            <div class="entry_info_body">
                                <div class="wpf_entry_transactions">
                                    <div v-for="refund in submission.refunds" :key="refund.id"
                                         class="wpf_each_entry">
                                        <div class="wpf_entry_label">
                                            <span v-html="getFormattedMoney(refund.payment_total)"></span> refunded due to {{refund.payment_note}} at  <span>{{refund.created_at}}</span>
                                        </div>
                                        <div class="wpf_entry_value">
                                            <span>Refund ID: {{ refund.charge_id }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="entry_info_box entry_submission_activity">
                            <div class="entry_info_header">
                                <div class="info_box_header">Submission Activity Events</div>
                                <div class="info_box_header_actions">
                                    <el-button @click="add_note_box = !add_note_box" size="mini" type="info">Add Note
                                    </el-button>
                                </div>
                            </div>
                            <div class="entry_info_body">
                                <div class="wpf_entry_details">
                                    <div v-if="add_note_box" class="wpf_add_note_box">
                                        <el-input
                                            type="textarea"
                                            :autosize="{ minRows: 3}"
                                            placeholder="Please Provide Note Content"
                                            v-model="new_note_content">
                                        </el-input>
                                        <el-button @click="submitNote()" size="small" type="success">Submit Note</el-button>
                                    </div>
                                    <template v-if="submission.activities && submission.activities.length">
                                        <div v-for="activity in submission.activities" :key="activity.id"
                                             class="wpf_each_entry">
                                            <div class="wpf_entry_label">
                                                {{activity.created_by}} - {{ activity.created_at }}
                                            </div>
                                            <div class="wpf_entry_value" v-html="activity.content"></div>
                                        </div>
                                    </template>

                                    <div class="wpf_each_entry text-center" v-else>
                                        <p>No Activity found</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wpf_submission_sidebar">
                        <template v-if="has_pro">
                            <div class="entry_info_box">
                                <div class="entry_info_header">
                                    <div class="info_box_header">Meta Info</div>
                                </div>
                                <div class="entry_info_body wpf_meta_info">
                                    <ul>
                                        <li>User Browser: {{submission.browser}}</li>
                                        <li>Platform: {{submission.device}}</li>
                                        <li>IP Address: <a target="_blank" rel="noopener" :href="'https://ipinfo.io/'+submission.ip_address">{{submission.ip_address}}</a></li>
                                        <li v-if="submission.user">User: <a target="_blank" rel="noopener" :href="submission.user.profile_url">{{submission.user.display_name}}</a></li>
                                    </ul>
                                </div>
                            </div>
                            <el-button @click="hide_sidebar = 'yes'" size="mini">Hide Sidebar Info</el-button>
                        </template>
                        <template v-else>
                            <div v-if="submission.order_items && parseInt(submission.order_items.length)" class="entry_info_box">
                                <div class="entry_info_header">
                                    <div class="info_box_header">WPPayForm Pro Features</div>
                                </div>
                                <div class="entry_info_body wpf_meta_info">
                                    <p>Upgrade to WPPayForm Pro and get more information about your submission, Plus you will get the following features</p>
                                    <ul class="wpf_no_style">
                                        <li><i class="el-icon-circle-check"></i> Accept Subscription/Recurring Payments</li>
                                        <li><i class="el-icon-circle-check"></i> Accept Payment via PayPal</li>
                                        <li><i class="el-icon-circle-check"></i> Collect Tax with Payment Items</li>
                                        <li><i class="el-icon-circle-check"></i> Tabular Product Order Field</li>
                                        <li><i class="el-icon-circle-check"></i> Get Email Notification on Form Submission</li>
                                        <li><i class="el-icon-circle-check"></i> Send Customized Email to form submitter</li>
                                        <li><i class="el-icon-circle-check"></i> Poweful Email Notification System</li>
                                        <li><i class="el-icon-circle-check"></i> File Upload Field</li>
                                        <li><i class="el-icon-circle-check"></i> Export and Print Your Data (Excel/CSV/JSON)</li>
                                        <li><i class="el-icon-circle-check"></i> Form Scheduling and Subsmission Restriction Module</li>
                                        <li><i class="el-icon-circle-check"></i> Get Powerful reporting features</li>
                                    </ul>
                                    <a target="_blank" href="https://wpmanageninja.com/wppayform-pro-wordpress-payments-form-builder/?utm_source=upgrade&amp;utm_medium=url&amp;utm_campaign=wppayform_upgrade" class="el-button payform_action el-button--danger el-button--mini">
                                        Upgrade To Pro
                                    </a>
                                </div>
                            </div>

                            <div class="entry_info_box">
                                <div class="entry_info_header">
                                    <div class="info_box_header">Recommended Plugins</div>
                                </div>
                                <div class="entry_info_body wpf_meta_info">
                                    <p>We have developed few awesome plugin that you can give a try too</p>
                                    <ul class="support_items">
                                        <li>
                                            <b><a target="_blank" href="https://wpmanageninja.com/downloads/ninja-tables-pro-add-on/">Ninja Tables Pro</a></b> - The Fastest and Most Diverse WordPress Table Plugin
                                        </li>
                                        <li>
                                            <b><a target="_blank" href="https://wpmanageninja.com/wp-fluent-form/">WP Fluent Form</a></b> - Make Effortless Contact Forms In Minutes!
                                        </li>
                                    </ul>
                                </div>
                            </div>

                        </template>
                    </div>
                </div>
            </template>

            <div class="print_button_controls">
                <el-button @click="show_print_settings = !show_print_settings" size="mini"><i
                    class="el-icon-setting"></i></el-button>
                <el-button @click="print" type="info" size="mini" icon="el-icon-printer">Print this entry</el-button>
                <el-button @click="exportJSON()" type="info" size="mini" icon="el-icon-document-copy">Export JSON
                </el-button>
                <div v-if="show_print_settings">
                    <h3>Mark Sections to hide from print screen</h3>
                    <el-checkbox-group v-model="print_hides">
                        <el-checkbox label="print_hide_activities">Hide Activities</el-checkbox>
                        <el-checkbox label="print_hide_transactions">Hide Transactions</el-checkbox>
                        <el-checkbox label="print_hide_input_items">Hide Input Items</el-checkbox>
                        <el-checkbox label="print_hide_header_items">Hide Header Bar</el-checkbox>
                        <el-checkbox label="print_hide_order_items">Hide Order Items</el-checkbox>
                    </el-checkbox-group>
                </div>

                <el-button v-if="hide_sidebar == 'yes'" @click="hide_sidebar = 'no'" size="mini">Show Meta Info</el-button>

            </div>
            <!--Edit Payment Status Modal-->
            <el-dialog
                title="Edit Payment Status"
                :visible.sync="editPaymentStatusModal"
                width="50%">
                <div class="modal_body">
                    <p>Current Payment Status: <b>{{ submission.payment_status }}</b></p>
                    <el-form ref="payment_status_form" :model="payment_status_edit_model" label-width="160px">
                        <el-form-item label="New Payment Status">
                            <el-radio-group v-model="payment_status_edit_model.status">
                                <el-radio v-for="(status, status_key) in available_payment_statuses" :key="status"
                                          :label="status_key">{{status}}
                                </el-radio>
                            </el-radio-group>
                        </el-form-item>
                        <el-form-item label="Note">
                            <el-input type="textarea" placeholder="You may add a note for this status change (optional)"
                                      size="mini"
                                      v-model="payment_status_edit_model.note"></el-input>
                        </el-form-item>
                    </el-form>
                </div>
                <span slot="footer" class="dialog-footer">
                <el-button @click="editPaymentStatusModal = false">Cancel</el-button>
                <el-button type="primary" @click="changePaymentStatus()">Confirm</el-button>
            </span>
            </el-dialog>
            <el-dialog
                :visible.sync="show_print_pro"
                width="60%">
                <div class="payform_section_body payform_upgrade_wrapper">
                    <div class="payform_upgrade_section">
                        <h1><i class="el-icon-lock"></i></h1>
                        <h3>Print Entry is a pro feature. Upgrade to pro to unlock this feature.</h3>
                        <a target="_blank" :href="pro_purchase_url" class="el-button el-button--primary">Upgrade To Pro
                            version</a>
                    </div>
                </div>
            </el-dialog>
        </div>
    </div>
</template>

<script type="text/babel">
    import each from 'lodash/each';
    import fromatPrice from '../../../../common/formatPrice';
    import omit from 'lodash/omit';
    import SubscriptionPayments from './_subscriptions';

    export default {
        name: "Entry",
        components: {
            SubscriptionPayments
        },
        data() {
            return {
                submission: {},
                entry_id: this.$route.params.entry_id,
                entry_items: {},
                form_id: this.$route.params.form_id,
                fething: false,
                loading: false,
                show_empty: false,
                add_note_box: false,
                new_note_content: '',
                adding_note: false,
                editPaymentStatusModal: false,
                payment_status_edit_model: {
                    status: '',
                    note: ''
                },
                available_payment_statuses: window.wpPayFormsAdmin.paymentStatuses,
                print_hides: [
                    'print_hide_activities'
                ],
                show_print_settings: false,
                show_print_pro: false,
                hide_sidebar: 'no'
            }
        },
        watch: {
            show_empty() {
                this.setStoreData('show_empty_entry_field', this.show_empty);
            },
            hide_sidebar() {
                this.setStoreData('entry_sidebar_status', this.hide_sidebar);
            }
        },
        computed: {
            subTotal() {
                let total = 0;
                each(this.submission.order_items, (item) => {
                    total += parseInt(item.line_total);
                });
                return total;
            },
            orderTotal() {
                let total = 0;
                each(this.submission.tax_items, (item) => {
                    total += parseInt(item.line_total);
                });
                return total + this.subTotal;
            },
            firstTransaction() {
                if (this.submission.transactions && this.submission.transactions.length) {
                    return this.submission.transactions[0];
                }
                return false;
            }
        },
        methods: {
            getEntry() {
                this.fething = true;
                const query = {
                    action: 'wpf_submission_endpoints',
                    route: 'get_submission',
                    form_id: parseInt(this.form_id),
                    submission_id: parseInt(this.entry_id)
                }
                this.$get(query)
                    .then(response => {
                        this.submission = response.data.submission;
                        this.entry_items = response.data.entry;
                        window.WPPayFormsBus.$emit('site_title', 'Entry#' + response.data.submission.id);
                    })
                    .always(() => {
                        this.fething = false;
                    });
            },
            getFormattedMoney(amount) {
                if (!amount) {
                    return 'n/a';
                }
                return fromatPrice(amount, this.submission.currencySetting);
            },
            getPaymentStatusIcon(status) {
                if (status == 'pending') {
                    return 'el-icon-time';
                } else if (status == 'paid') {
                    return 'el-icon-check';
                } else if (status == 'failed') {
                    return 'el-icon-error';
                } else if (status == 'refunded') {
                    return 'el-icon-warning';
                }
                return '';
            },
            handleNavClick(type) {
                this.loading = true;
                const query = {
                    action: 'wpf_submission_endpoints',
                    route: 'get_next_prev_submission',
                    form_id: parseInt(this.form_id),
                    type: type,
                    current_submission_id: parseInt(this.entry_id)
                }
                this.$get(query)
                    .then(response => {
                        this.submission = response.data.submission;
                        this.entry_items = response.data.entry;
                        this.entry_id = response.data.submission.id;
                        window.WPPayFormsBus.$emit('site_title', 'Entry#' + response.data.submission.id);
                        this.$router.push({
                            name: 'entry',
                            params: {entry_id: response.data.submission.id},
                            query: {form_id: this.form_id}
                        })
                    })
                    .fail(error => {
                        this.$message.error({
                            message: error.responseJSON.data.message
                        });
                    })
                    .always(() => {
                        this.loading = false;
                    });
            },
            submitNote() {
                if (!this.new_note_content) {
                    this.$message({
                        message: 'Please provide note',
                        type: 'error'
                    });
                    return;
                }
                this.adding_note = true;
                this.$post({
                    action: 'wpf_submission_endpoints',
                    route: 'add_submission_note',
                    form_id: this.submission.form_id,
                    submission_id: this.submission.id,
                    note: this.new_note_content
                })
                    .then(response => {
                        this.submission.activities = response.data.activities;
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
                        this.new_note_content = '';
                        this.adding_note = false;
                    });
            },
            handleActionCommand(command) {
                if (command == 'payment_status') {
                    this.editPaymentStatusModal = true;
                }
            },
            changePaymentStatus() {
                this.$post({
                    action: 'wpf_submission_endpoints',
                    route: 'change_payment_status',
                    form_id: this.submission.form_id,
                    submission_id: this.submission.id,
                    new_payment_status: this.payment_status_edit_model.status,
                    status_change_note: this.payment_status_edit_model.note
                })
                    .then(response => {
                        this.editPaymentStatusModal = false;
                        this.$message.success(response.data.message);
                        this.payment_status_edit_model = {
                            status: '',
                            note: ''
                        };
                        this.getEntry();
                    })
                    .fail(error => {
                        this.$message.error(error.responseJSON.data.message);
                    })
                    .always(() => {

                    });
            },
            isUrl(maybeUrl) {
                var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
                return regexp.test(maybeUrl);
            },
            print() {
                if (!this.has_pro) {
                    this.show_print_pro = true;
                    return;
                }
                this.$htmlToPaper('print_view');
            },
            exportJSON() {
                if (!this.has_pro) {
                    this.$notify.error('Export JSON is a pro feature. Please upgrade');
                }
                let submission = JSON.parse(JSON.stringify(this.submission));
                submission = omit(submission, 'currencySetting');
                this.downloadObjectAsJson(submission, 'entry_' + submission.id);
            },
            downloadObjectAsJson(exportObj, exportName) {
                var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(exportObj));
                var downloadAnchorNode = document.createElement('a');
                downloadAnchorNode.setAttribute("href", dataStr);
                downloadAnchorNode.setAttribute("download", exportName + ".json");
                document.body.appendChild(downloadAnchorNode); // required for firefox
                downloadAnchorNode.click();
                downloadAnchorNode.remove();
            }
        },
        mounted() {
            if (this.$route.query.form_id) {
                this.form_id = this.$route.query.form_id;
            }
            this.getEntry();
            this.show_empty = this.getFromStore('show_empty_entry_field', false);

            this.hide_sidebar = this.getFromStore('entry_sidebar_status', 'no');
        }
    }
</script>
