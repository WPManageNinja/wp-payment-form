<template>
    <div class="payform_section">
        <div v-if="is_payment_form" class="wpf_report_cards">
            <div
                v-for="(report, report_name) in reports"
                @click="changePaymentStatus(report_name)"
                :class="(selected_payment_status == report_name) ? 'wpf_active_card card_type_'+report_name : ''"
                class="wpf_report_card">
                <div class="wpf_report_header">
                    {{report.label}}
                </div>
                <div class="wpf_report_body">
                    <div class="wpf_report_line"><i class="el-icon-document-copy"></i> {{report.submission_count}}</div>
                    <div v-if="is_payment_form" class="wpf_report_line"><i class="el-icon-money"></i> <span v-html="formatMoney(report.payment_total)"></span></div>
                </div>
            </div>
        </div>

        <div class="wpf_entry_actions payform_section_header">
            <h3 class="payform_section_title">
                Form Entries
            </h3>
            <div class="payform_section_actions">
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
        </div>
        <div :class="is_payment_form ? 'has_payments_table' : 'no_payments_table'">
        <form-entries-table
            :entry_ticker="entry_ticker"
            :form_id="form_id"
            :search_string="search_string"
            :payment_status="selected_payment_status"
        />
        </div>

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
    import fromatPrice from '../../../../common/formatPrice';

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
                entry_ticker: 1,
                reports: {},
                currencySettings: {},
                is_payment_form: false
            }
        },
        methods: {
            performSearch() {
                this.entry_ticker = this.entry_ticker + 1;
            },
            changePaymentStatus(reportName) {
                this.selected_payment_status = reportName;
                this.$router.push({query: {payment_status: this.selected_payment_status}});
            },
            exportCSV(doc_type) {
                if (!this.has_pro) {
                    this.show_pro = true;
                    return;
                }
                let query = jQuery.param({
                    action: 'wpf_export_endpoints',
                    route: 'export_data',
                    doc_type: doc_type,
                    search_string: this.search_string,
                    form_id: parseInt(this.form_id),
                    payment_status: this.selected_payment_status
                });

                window.location.href = window.wpPayFormsAdmin.ajaxurl + '?' + query;
            },
            getFormReport() {
                this.$get({
                    action: 'wpf_submission_endpoints',
                    route: 'get_form_report',
                    form_id: this.form_id
                })
                    .then(response => {
                        this.reports = response.data.reports;
                        this.currencySettings = response.data.currencySettings;
                        this.is_payment_form = response.data.is_payment_form;
                    })
                    .fail(error => {
                        console.log(error);
                    })
                    .always(() => {

                    });
            },
            formatMoney(price) {
                return fromatPrice(price, this.currencySettings);
            }
        },
        mounted() {
            if (this.$route.query.payment_status) {
                this.selected_payment_status = this.$route.query.payment_status;
            }
            this.getFormReport();
            window.WPPayFormsBus.$emit('site_title', 'Form Entries');
        }
    }
</script>