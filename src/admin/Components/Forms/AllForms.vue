<template>
    <div v-loading="loading" class="wppayforms">
        <welcome v-if="!forms_count && !hasForms" @create="createFormModal = true"/>
        <!--We Have forms Now-->
        <div class="all_payforms_wrapper payform_section" v-else>
            <div class="payform_section_header all_payment_form_wrapper">
                <h1 class="payform_section_title">
                    {{ $t('All Forms') }}
                </h1>
                <div class="payform_section_actions">
                    <div class="payform_action search_action">
                        <el-input @keyup.enter.native="fetchForms()" size="small" placeholder="Search" v-model="search_string" class="input-with-select">
                            <el-button @click="fetchForms()" slot="append" icon="el-icon-search"></el-button>
                        </el-input>
                    </div>
                    <el-button class="payform_action" @click="createFormModal = true" size="small" type="primary">
                        {{ $t( 'Add New Form' ) }}
                    </el-button>
                </div>
            </div>
            <div v-loading.fullscreen.lock="duplicatingForm" element-loading-text="Duplicating the form.. Please wait..." class="payform_section_body">
                <el-table
                    class="payform_tables"
                    v-loading.body="loading"
                    :data="paymentForms"
                    border>

                    <el-table-column :label="$t('ID')" width="70">
                        <template slot-scope="scope">
                            <router-link :to="{ name: 'edit_form', params: { form_id: scope.row.ID } }">
                                {{ scope.row.ID }}
                            </router-link>
                        </template>
                    </el-table-column>

                    <el-table-column :label="$t('Title')">
                        <template slot-scope="scope">
                            <strong>
                                {{ scope.row.post_title }}
                            </strong>
                            <div class="row-actions">
                                <router-link :to="{ name: 'edit_form', params: { form_id: scope.row.ID } }">
                                    {{ $t('Edit') }}
                                </router-link>
                                |
                                <router-link :to="{ name: 'form_entries', params: { form_id: scope.row.ID } }">
                                    {{ $t('Entries') }}
                                </router-link>
                                |
                                <a :href="scope.row.preview_url" target="_blank">{{ $t('Preview') }}</a>
                                |
                                <a @click.prevent="confirmDeleteForm(scope.row)" href="#">{{ $t('Delete') }}</a>
                                |
                                <a href="#" @click.prevent="duplicateForm(scope.row.ID)">{{ $t('Duplicate Form') }}</a>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column label="Submissions" width="120">
                        <template slot-scope="scope">
                            <router-link :to="{ name: 'form_entries', params: { form_id: scope.row.ID } }">
                                {{scope.row.entries_count}}
                            </router-link>

                        </template>
                    </el-table-column>
                    <el-table-column label="Craete Date" width="120">
                        <template slot-scope="scope">
                            {{scope.row.post_date_gmt | dateFormat}}
                        </template>
                    </el-table-column>
                    <el-table-column width="250" :label="$t('ShortCode')">
                        <template slot-scope="scope">
                            <el-tooltip effect="dark"
                                        content="Click to copy shortcode"
                                        title="Click to copy shortcode"
                                        placement="top">
                                <code class="copy"
                                      :data-clipboard-text='`[wppayform id="${scope.row.ID}"]`'>
                                    <i class="el-icon-document"></i> [wppayform id="{{ scope.row.ID }}"]
                                </code>
                            </el-tooltip>
                        </template>
                    </el-table-column>
                </el-table>
                <div class="wpf_pagination">
                    <el-pagination
                        background
                        @size-change="handleSizeChange"
                        @current-change="handleCurrentChange"
                        :current-page="page_number"
                        :page-size="per_page"
                        :page-sizes="pageSizes"
                        layout="total, sizes, prev, pager, next"
                        :total="total">
                    </el-pagination>
                </div>
            </div>
        </div>
        <!-- Load Modals-->
        <create-form v-if="createFormModal" :demo_forms="demo_forms" :modalVisible.sync="createFormModal"/>

        <!--Delete form Confimation Modal-->
        <el-dialog
            title="Are You Sure, You want to delete this form?"
            :visible.sync="deleteDialogVisible"
            :before-close="handleDeleteClose"
            width="60%">
            <div class="modal_body">
                <p>All the data assoscilate with this form will be deleted, including payment information and other
                    associate information</p>
                <p>You are deleting form id: <b>{{ deleteingForm.ID }}</b>. <br/>Form Title: <b>{{
                    deleteingForm.post_title }}</b></p>
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button @click="deleteDialogVisible = false">Cancel</el-button>
                <el-button type="primary" @click="deleteFormNow()">Confirm</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
    import Welcome from '../Common/Welcome';
    import CreateForm from './CreateForm';
    import Clipboard from 'clipboard';

    export default {
        name: 'AllForms',
        components: { CreateForm, Welcome },
        comments: {
            Welcome
        },
        data() {
            return {
                createFormModal: false,
                paymentForms: [],
                hasForms: false,
                per_page: 20,
                page_number: 1,
                search_string: '',
                total: 0,
                loading: false,
                deleteDialogVisible: false,
                deleteingForm: {},
                pageSizes: [10, 20, 30, 40, 50, 100, 200],
                forms_count: parseInt(window.wpPayFormsAdmin.forms_count),
                duplicatingForm: false,
                loadingDemoForms: false,
                demo_forms: {}
            }
        },
        methods: {
            fetchForms() {
                this.loading = true;
                this.$adminGet({
                    route: 'get_forms',
                    per_page: this.per_page,
                    page_number: this.page_number,
                    search_string: this.search_string
                })
                    .then(response => {
                        this.paymentForms = response.data.forms;
                        this.hasForms = !!response.data.total;
                        this.total = response.data.total;
                    })
                    .fail(error => {
                        this.$showAjaxError(error);
                    })
                    .always(() => {
                        this.loading = false;
                    });
            },
            confirmDeleteForm(form) {
                this.deleteingForm = form;
                this.deleteDialogVisible = true;
            },
            deleteFormNow() {
                this.$adminPost({
                    action: 'wppayform_forms_admin_ajax',
                    route: 'delete_form',
                    form_id: this.deleteingForm.ID
                })
                    .then(response => {
                        this.$message.success({
                            message: response.data.message
                        });
                        this.fetchForms();
                    })
                    .fail(error => {
                        this.$message.error({
                            message: error.responseJSON.data.message
                        });
                    })
                    .always(() => {
                        this.deleteDialogVisible = false;
                        this.deleteingForm = {};
                    });
            },
            handleDeleteClose() {
                this.this.deleteingForm = {};
            },
            handleCurrentChange(val) {
                this.page_number = val;
                this.fetchForms();
            },
            handleSizeChange(val) {
                this.per_page = val;
                this.fetchForms();
            },
            duplicateForm(formId) {
                this.duplicatingForm = true;
                this.$post({
                    action: 'wppayform_forms_admin_ajax',
                    route: 'duplicate_form',
                    form_id: formId
                })
                    .then(response => {
                        if(response.data.form.ID) {
                            this.$notify.success(response.data.message);
                            this.$router.push({
                                name: 'edit_form',
                                params: {
                                    form_id: response.data.form.ID
                                }
                            });
                        } else {
                            this.$notify.error('Something is wrong! Please try again');
                        }
                    })
                    .fail((error) => {
                        this.$showAjaxError(error);
                    })
                    .always(() => {
                        this.duplicatingForm = false;
                    });
            },
            getDemoForms() {
                this.loadingDemoForms = true;
                this.$get({
                    action: 'wppayform_demo_forms',
                    route: 'get_forms'
                })
                    .then(response => {
                        this.demo_forms = response.data.demo_forms;
                    })
                    .fail(error => {
                        this.$showAjaxError(error);
                    })
                    .always(() => {
                        this.loadingDemoForms = false;
                    });
            }
        },
        mounted() {
            this.fetchForms();
            this.getDemoForms();
            var clipboard = new Clipboard('.copy');
            clipboard.on('success', (e) => {
                this.$message({
                    message: 'Copied to Clipboard!',
                    type: 'success'
                });
            });

            window.WPPayFormsBus.$emit('site_title', 'All Forms');
        }
    }
</script>