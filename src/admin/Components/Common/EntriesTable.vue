<template>
    <div class="form_entries_wrapper">

        <div v-loading="fetching" class="wpf_entries">
            <el-table
                :data="allEntry"
                style="width: 100%"
                :row-class-name="tableRowClassName"
            >
                <el-table-column
                    label="ID"
                    width="60">
                    <template slot-scope="scope">
                        <router-link
                            :to="{ name: 'entry', params: { entry_id: scope.row.id, form_id: scope.row.form_id }}">
                            {{scope.row.id}}
                        </router-link>
                    </template>
                </el-table-column>
                <el-table-column
                    v-if="!router_form_id"
                    label="Form"
                    width="220">
                    <template slot-scope="scope">
                        {{ scope.row.post_title }} ({{ scope.row.form_id }})
                    </template>
                </el-table-column>
                <el-table-column
                    label="Name"
                    width="160"
                    prop="customer_name">
                </el-table-column>
                <el-table-column
                    label="Email"
                    prop="customer_email"
                >
                    <template slot-scope="scope">
                        {{ scope.row.customer_email }}
                    </template>
                </el-table-column>
                <el-table-column
                    v-if="has_payment_items"
                    label="Payment Status">
                    <template slot-scope="scope">
                    <span :class="'wpf_pay_status_'+scope.row.payment_status">
                        <i :class="getPaymentStatusIcon(scope.row.payment_status)"/>
                        {{ scope.row.payment_status }}</span>
                    </template>
                </el-table-column>
                <el-table-column
                    v-if="has_payment_items"
                    label="Payment Total">
                    <template slot-scope="scope">
                        <span v-html="getFormattedMoney(scope.row)"></span>
                    </template>
                </el-table-column>
                <el-table-column
                    label="Submitted At"
                    prop="created_at">
                </el-table-column>
                <el-table-column
                    label="Actions"
                    width="130"
                    fixed="right">
                    <template slot-scope="scope">
                        <el-button-group>
                            <el-button @click="goToViewRoute(scope.row)" type="primary" icon="el-icon-view" size="mini"></el-button>
                            <el-button @click="showDeleteConformation(scope.row)" type="danger" size="mini" icon="el-icon-delete"></el-button>
                        </el-button-group>
                    </template>
                </el-table-column>
            </el-table>
        </div>
        <div class="wpf_entry_pagination">
            <el-pagination
                @size-change="pageSizeChange"
                @current-change="changePage"
                :current-page.sync="pagination.current_page"
                :page-sizes="page_sizes"
                :page-size="pagination.per_page"
                layout="total, sizes, prev, pager, next"
                :total="pagination.total">
            </el-pagination>
        </div>

        <!--Delete Entry Confimation Modal-->
        <el-dialog
            title="Are You Sure, You want to delete this Entry?"
            :visible.sync="delete_pop_up"
            :before-close="handleDeleteClose"
            width="60%">
            <div v-if="deleting_row" class="modal_body">
                <p>All the data assoscilate with this entry will be deleted, including payment information and other associate information</p>
                <p>You are deleting entry id: <b>{{ deleting_row.id }}</b>. <br />Form Title: <b>{{ deleting_row.post_title }}</b></p>
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button @click="delete_pop_up = false">Cancel</el-button>
                <el-button type="primary" @click="deleteEntryNow()">Confirm Delete</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
    import fromatPrice from '../../../common/formatPrice';
    export default {
        name: 'entries_table',
        props: ['form_id', 'payment_status', 'entry_ticker', 'search_string'],
        watch: {
            form_id() {
                this.pagination.current_page = 1;
                this.getEntries();
            },
            payment_status() {
                this.pagination.current_page = 1;
                this.getEntries();
            },
            entry_ticker() {
                this.pagination.current_page = 1;
                this.getEntries();
            }
        },
        data() {
            return {
                fetching: false,
                allEntry: [],
                pagination: {
                    current_page: 1,
                    per_page: 20,
                    total: 0
                },
                router_form_id: parseInt(this.$route.params.form_id),
                delete_pop_up: false,
                deleting_row: null,
                deletetingItem: false,
                has_payment_items: true
            }
        },
        computed: {
            page_sizes() {
                let start = 20;
                let stop = this.pagination.total;
                let step = 20;
                const remainder = stop % step;
                if (remainder) {
                    stop = stop + step;
                }
                var result = [];
                for (var i = start; step > 0 ? i <= stop : i >= stop; i += step) {
                    result.push(i);
                }
                return result;
            }
        },
        methods: {
            getEntries() {
                this.fetching = true;
                let query = {
                    action: 'wpf_submission_endpoints',
                    route: 'get_submissions',
                    form_id: parseInt(this.form_id),
                    payment_status: this.payment_status,
                    page_number: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    search_string: this.search_string
                }

                this.$get(query)
                    .then(response => {
                        this.allEntry = response.data.submissions;
                        this.pagination.total = response.data.total;
                        this.has_payment_items = response.data.hasPaymentItem;
                    })
                    .always(() => {
                        this.fetching = false;
                    });
            },
            showDeleteConformation(row) {
                this.deleting_row = row;
                this.delete_pop_up = true;
            },
            handleDeleteClose() {
                this.deleting_row = null;
                this.delete_pop_up = false;
            },
            deleteEntryNow() {
                this.deletetingItem = true;
                this.$post({
                    action: 'wpf_submission_endpoints',
                    route: 'delete_submission',
                    submission_id: this.deleting_row.id,
                    form_id: this.deleting_row.form_id
                })
                    .then(response => {
                        this.$message.success(response.data.message);
                        this.getEntries();
                    })
                    .fail(error => {
                        this.$message.error(error.responseJSON.data.message);
                    })
                    .always(() => {
                        this.deletetingItem = false;
                        this.handleDeleteClose();
                    });
            },
            changePage(page) {
                this.pagination.current_page = page;
                this.pagination.page_number = page;
                this.getEntries()
            },
            getFormattedMoney(row) {
                let amount = row.payment_total;
                if (!amount) {
                    return 'n/a';
                }
                return fromatPrice(amount, row.currencySettings);
            },
            goToViewRoute(row) {
                this.$router.push({
                    name: 'entry',
                    params: { entry_id: row.id, form_id: row.form_id }
                });
            },
            pageSizeChange(pageSize) {
                this.pagination.per_page = pageSize;
                this.getEntries()
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
            tableRowClassName({row, rowIndex}) {
                return 'wpf_row_' + row.payment_status;
            },
        },
        mounted() {
            this.getEntries();
        }
    }
</script>