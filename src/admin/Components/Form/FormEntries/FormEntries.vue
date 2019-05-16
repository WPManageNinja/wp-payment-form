<template>
    <div class="payform_section">
        <div class="wpf_entry_actions payform_section_header">
            <div class="wpf_entry_action">
                <label>
                    <span class="item_title">Filter By Payment Status</span>
                    <el-select @change="changePaymentStatus()" size="small" v-model="selected_payment_status"
                               placeholder="All Forms">
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
            <div class="wpf_entry_action">
                    <el-input @keyup.enter.native="performSearch" size="mini" placeholder="Search" v-model="search_string">
                        <el-button @click="performSearch" size="mini" slot="append" icon="el-icon-search"></el-button>
                    </el-input>
            </div>
            <div class="wpf_entry_action">
                <el-dropdown @command="exportCSV">
                    <el-button type="info" size="mini">
                        Export <i class="el-icon-arrow-down el-icon--right"></i>
                    </el-button>
                    <el-dropdown-menu slot="dropdown">
                        <el-dropdown-item command="csv">Export as CSV</el-dropdown-item>
                        <el-dropdown-item command="xlsx">Export as Excel (xlsv)</el-dropdown-item>
                        <el-dropdown-item command="ods">Export as ODS</el-dropdown-item>
                        <el-dropdown-item command="json">Export as JSON Data</el-dropdown-item>
                    </el-dropdown-menu>
                </el-dropdown>
            </div>
        </div>
        <form-entries-table
            :entry_ticker="entry_ticker"
            :form_id="form_id"
            :search_string="search_string"
            :payment_status="selected_payment_status"
        />

        <el-dialog
            :visible.sync="show_pro"
            width="60%">
            <div class="payform_section_body payform_upgrade_wrapper">
                <div class="payform_upgrade_section">
                    <h1><i class="el-icon-lock"></i></h1>
                    <h3>Export data is a pro feature. Upgrade to pro to unlock this feature.</h3>
                    <a target="_blank" :href="pro_purchase_url" class="el-button el-button--primary">Upgrade To Pro
                        version</a>
                </div>
            </div>
        </el-dialog>
    </div>
</template>

<script>
    import formEntriesTable from '../../Common/EntriesTable';

    export default {
        name: "Entries",
        components: {
            formEntriesTable
        },
        data() {
            return {
                form_id: this.$route.params.form_id,
                available_forms: [],
                selected_payment_status: '',
                available_statuses: window.wpPayFormsAdmin.paymentStatuses,
                show_pro: false,
                search_string: '',
                entry_ticker: 1
            }
        },
        methods: {
            performSearch() {
                this.entry_ticker = this.entry_ticker + 1;
            },
            changePaymentStatus() {
                this.$router.push({query: {payment_status: this.selected_payment_status}});
            },
            exportCSV(doc_type) {
                if(!this.has_pro) {
                    this.show_pro = true;
                    return;
                }
                let query = jQuery.param({
                    action: 'wpf_export_endpoints',
                    route: 'export_data',
                    doc_type: doc_type,
                    form_id: parseInt(this.form_id),
                    payment_status: this.selected_payment_status
                });

                window.location.href = window.wpPayFormsAdmin.ajaxurl + '?' + query;
            }
        },
        mounted() {
            if (this.$route.query.payment_status) {
                this.selected_payment_status = this.$route.query.payment_status;
            }
            window.WPPayFormsBus.$emit('site_title', 'Form Entries');
        }
    }
</script>