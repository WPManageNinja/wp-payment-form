<template>
    <div>
        <div class="wpf_entry_actions">
            <div class="wpf_entry_action">
                <label>
                    <span class="item_title">Filter By Form</span>
                    <el-select @change="changeForm()" size="small" v-model="form_id" placeholder="All Forms">
                        <el-option label="All Forms" value="0"></el-option>
                        <el-option
                            v-for="form in available_forms"
                            :key="form.ID"
                            :label="form.post_title"
                            :value="form.ID">
                        </el-option>
                    </el-select>
                </label>
            </div>
            <div class="wpf_entry_action">
                <label>
                    <span class="item_title">Filter By Payment Status</span>
                    <el-select @change="changePaymentStatus()" size="small" v-model="selected_payment_status" placeholder="All Forms">
                        <el-option label="All" value=""></el-option>
                        <el-option
                            v-for="(status, status_key) in available_statuses"
                            :key="status_key"
                            :label="status"
                            :value="status_key">
                        </el-option>
                    </el-select>
                </label>
            </div>
        </div>
        <div v-loading="fetching" class="wpf_entries">
            <el-table
                :data="allEntry"
                style="width: 100%"
                :row-class-name="tableRowClassName"
            >
                <el-table-column
                    label="ID"
                    fixed
                    width="60">
                    <template slot-scope="scope">
                        <router-link :to="{ name: 'entry', params: { entry_id: scope.row.id }, query: { form_id: form_id } }">
                            {{scope.row.id}}
                        </router-link>
                    </template>
                </el-table-column>
                <el-table-column
                    label="Payment Form"
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
                    label="Payment Status">
                    <template slot-scope="scope">
                    <span :class="'wpf_pay_status_'+scope.row.payment_status">
                        <i :class="getPaymentStatusIcon(scope.row.payment_status)"/>
                        {{ scope.row.payment_status }}</span>
                    </template>
                </el-table-column>
                <el-table-column
                    label="Payment Total">
                    <template slot-scope="scope">
                        <span v-html="getFormattedMoney(scope.row)"></span>
                    </template>
                </el-table-column>
                <el-table-column
                    label="Submitted At"
                    prop="created_at">
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
    </div>
</template>

<script>
    import fromatPrice from '../../../common/formatPrice';

    export default {
        name: "Entries",
        data() {
            return {
                fetching: false,
                allEntry: [],
                pagination: {
                    current_page: 1,
                    per_page: 20,
                    total: 0
                },
                form_id: '0',
                available_forms: [],
                selected_payment_status: '',
                available_statuses: {
                    'pending': 'Pending',
                    'paid' : 'Paid',
                    'failed' : 'Failed',
                }
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
                    action: 'wpf_get_submissions',
                    form_id: parseInt(this.form_id),
                    payment_status:  this.selected_payment_status,
                    page_number: this.pagination.current_page,
                    per_page: this.pagination.per_page
                }

                this.$get(query)
                    .then(response => {
                        this.allEntry = response.data.submissions;
                        this.pagination.total = response.data.total;
                    })
                    .always(() => {
                        this.fetching = false;
                    });
            },
            changeForm() {
                this.$router.push( {query: {form_id: this.form_id}});
                this.pagination.current_page = 1;
                this.getEntries();
            },
            changePaymentStatus() {
                this.pagination.current_page = 1;
                this.$router.push( {query: {payment_status: this.selected_payment_status}});
                this.getEntries();
            },
            pageSizeChange(pageSize) {
                this.query.per_page = pageSize;
                this.getEntries()
            },
            changePage(page) {
                this.pagination.current_page = page;
                this.query.page_number = page;
                this.getEntries()
            },
            getFormattedMoney(row) {
                let amount = row.payment_total;
                if (!amount) {
                    return 'n/a';
                }
                return fromatPrice(amount, row.currencySettings);
            },
            getPaymentStatusIcon(status) {
                if(status == 'pending') {
                    return 'el-icon-time';
                } else if(status == 'paid') {
                    return 'el-icon-check';
                } else if(status == 'failed') {
                    return 'el-icon-error';
                }
                return '';
            },
            tableRowClassName({row, rowIndex}) {
                console.log(row);
                return 'wpf_row_'+row.payment_status;
            },
            getFormTitles() {
                this.$get({
                    action: 'wpf_get_available_forms'
                })
                    .then(response => {
                        this.available_forms = response.data.available_forms;
                    });
            }
        },
        mounted() {
            if(this.$route.query.form_id) {
                this.form_id = this.$route.query.form_id;
            }
            if(this.$route.query.payment_status) {
                this.selected_payment_status = this.$route.query.payment_status;
            }
            this.getEntries();
            this.getFormTitles();
        }
    }
</script>