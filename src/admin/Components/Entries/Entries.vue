<template>
    <div class="all_payforms_wrapper payform_section">
        <div class="payform_section_header">
            <h3 class="payform_section_title">
                {{ $t('Entries') }}
            </h3>
        </div>
        <el-table
                :data="allEntry"
                style="width: 100%">
            <el-table-column
                    label="Name"
                    prop="customer_name">
            </el-table-column>
            <el-table-column
                    label="Customer Email"
                    prop="customer_email"
                    fixed
            >
                <template slot-scope="scope">
                    <router-link :to="{ name: 'entry', params: { entry_id: scope.row.id } }">
                        {{ scope.row.customer_email }}
                    </router-link>
                </template>
            </el-table-column>
            <el-table-column
                    label="Payment status"
                    prop="payment_status">
            </el-table-column>
            <el-table-column
                    sortable
                    label="Payment total"
                    prop="payment_total">
            </el-table-column>
            <el-table-column
                    label="currency"
                    prop="currency">
            </el-table-column>
            <el-table-column
                    label="created_at"
                    prop="created_at">
            </el-table-column>
        </el-table>
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
</template>

<script>
    export default {
        name: "Entries",
        props: ['form_id'],
        data() {
            return {
                allEntry: [],
                pagination: {
                    current_page: 1,
                    per_page: 3,
                    total: 0
                },
                query: {
                    action: 'wpf_get_submissions',
                    form_id: this.form_id,
                    page_number: 1,
                    per_page: 4
                }

            }
        },
        computed: {
            page_sizes() {
                let start = 4;
                let stop = this.pagination.total;
                let step = 4;

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
                this.$get(this.query)
                    .then(response => {
                        this.allEntry = response.data.submissions;
                        this.pagination.total = response.data.total;
                    })
            },
            pageSizeChange(pageSize) {
                this.query.per_page = pageSize;
                this.getEntries()
            },
            changePage(page) {
                this.pagination.current_page = page;
                this.query.page_number = page;
                this.getEntries()
            }
        },
        mounted() {
            this.getEntries()
        }
    }
</script>
