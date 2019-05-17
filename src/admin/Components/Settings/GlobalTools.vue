<template>
    <div>
        <div class="all_payforms_wrapper payform_section wpf_min_width">
            <div class="payform_section_header">
                <h3 class="payform_section_title">
                    {{ $t('Tools') }}
                </h3>
            </div>
            <div class="payform_section_body">
                <div class="wpf_settings_section">
                    <div class="sub_section_header">
                        <h3>{{ $t('Export From') }}</h3>
                        <p>You can export any form as .json format that you have and then you can import the form in any
                            in
                            WPPayForm</p>
                    </div>
                    <hr/>
                    <div v-loading="fetching_forms" class="sub_section_body">
                        <br/>
                        <el-form label-position="top">
                            <el-form-item label="Select the form to export">
                                <el-select size="mini" v-model="export_id" placeholder="Select Form">
                                    <el-option
                                        v-for="item in forms"
                                        :key="item.ID"
                                        :label="'#'+item.ID+' - '+item.post_title"
                                        :value="item.ID">
                                    </el-option>
                                </el-select>
                                <el-button @click="exportForm" :disabled="!export_id" type="primary" size="mini">
                                    Export
                                </el-button>
                            </el-form-item>
                        </el-form>
                    </div>
                </div>
                <div class="wpf_settings_section">
                    <div class="sub_section_header">
                        <h3>{{ $t('Import From') }}</h3>
                        <p>You can import any form that is exported from wppayfrom</p>
                    </div>
                    <hr/>
                    <div class="sub_section_body">
                        <br/>
                        <form enctype="multipart/form-data" id="upload_json_form">
                            <input type="hidden" name="action" value="wppayform_global_tools"/>
                            <input type="hidden" name="route" value="upload_form"/>
                            <label>
                                Upload .json file
                                <input required type="file" name="import_file"/>
                            </label>
                            <el-button v-loading="uploadingForm" @click="importForm" type="primary" size="mini">Import Form</el-button>
                        </form>

                        <div v-if="last_uploaded_form">
                            <hr />
                            <p>Form successfully imported. <router-link :to="{name: 'edit_form', params: { form_id: last_uploaded_form.ID }}">Click here to view</router-link></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'global_tools',
        data() {
            return {
                forms: [],
                export_id: '',
                import_file: '',
                fetching_forms: false,
                exportingForm: false,
                uploadingForm: false,
                last_uploaded_form: false
            }
        },
        methods: {
            getForms() {
                this.fetching_forms = true;
                this.$get({
                    action: 'wppayform_global_tools',
                    route: 'get_forms'
                })
                    .then(response => {
                        this.forms = response.data.forms;
                    })
                    .fail(error => {
                        this.$showAjaxError(error);
                    })
                    .always(() => {
                        this.fetching_forms = false;
                    });
            },
            exportForm() {
                let query = jQuery.param({
                    action: 'wppayform_global_tools',
                    route: 'export_form',
                    form_id: this.export_id
                });
                window.location.href = window.wpPayFormsAdmin.ajaxurl + '?' + query;
                this.$notify.success('Exporting form');
                this.export_id = '';
            },
            importForm() {
                var that = this;
                jQuery.ajax({
                    url: window.wpPayFormsAdmin.ajaxurl,
                    type: "POST",
                    data: new FormData(jQuery('#upload_json_form')[0]),
                    contentType: false,
                    cache: false,
                    processData: false,
                    beforeSend() {
                        that.uploadingForm = true;
                    },
                    success(response) {
                        that.$notify.success(response.data.message);
                        that.last_uploaded_form = response.data.form;
                        jQuery('#upload_json_form')[0].reset();
                        that.uploadingForm = false;
                    },
                    error (error) {
                        that.$showAjaxError(error);
                        that.uploadingForm = false;
                    }
                });
            }
        },
        mounted() {
            this.getForms();
        }
    }
</script>
