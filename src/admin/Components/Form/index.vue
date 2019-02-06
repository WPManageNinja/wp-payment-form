<template>
    <div class="wppaymform_editor">
        <div class="settings_header">
            <div class="settings_left">
                <el-button class="ninja_mini" size="mini" @click="editFormModalShow = true">
                    <i title="Edit" class="el-icon-edit action">{{ $t('Edit') }}</i>
                </el-button>
                <span class="section_title">{{ form.post_title }}</span>
                <el-tooltip effect="dark"
                            content="Click to copy shortcode"
                            title="Click to copy shortcode"
                            placement="top">
                    <code class="copy"
                          :data-clipboard-text='`[wp_payment_form id="${form.ID}"]`'>
                        <i class="el-icon-document"></i> [wp_payment_form id="{{ form.ID }}"]
                    </code>
                </el-tooltip>
            </div>
            <div class="settings_right">
                <a :href="form.preview_url" target="_blank">
                    <el-button size="mini">{{ $t('Preview') }}</el-button>
                </a>
            </div>
        </div>
        <div class="payform_editor_wrapper">
            <el-container>
                <el-aside width="200px">
                    <el-menu background-color="#545c64"
                             text-color="#fff"
                             :router="true"
                             :default-active="current_route"
                             active-text-color="#ffd04b"
                    >
                        <el-menu-item
                            v-for="formMenu in form_menus"
                            :key="formMenu.route"
                            :route="{ name: formMenu.route, params: { form_id: form_id } }"
                            :index="formMenu.route">
                            <i class="dashicons dashicons-editor-table"></i>
                            <span>{{  formMenu.title }}</span>
                        </el-menu-item>
                        <el-menu-item
                            :route="{ name: 'entries', params: { form_id: form_id }, query: { form_id: form_id.toString() } }"
                            index="entries"
                        >
                            <i class="dashicons dashicons-editor-table"></i>
                            <span>{{  $t('Form Entries') }}</span>
                        </el-menu-item>
                    </el-menu>
                </el-aside>
                <el-main>
                    <router-view :form_id="form_id"></router-view>
                </el-main>
            </el-container>
        </div>

        <!--Edit Form Modal-->
        <el-dialog
            title="Edit Form Title and Description"
            :visible.sync="editFormModalShow"
            width="50%">
            <div class="modal_body">
                <el-form ref="edit_form" :model="form" label-width="160px">
                    <el-form-item label="New Payment Status">
                        <el-input v-model="form.post_title" size="mini" type="text" placeholder="Form Title"/>
                    </el-form-item>
                    <el-form-item label="Form Description">
                        <wp-editor v-model="form.post_content"/>
                    </el-form-item>
                    <el-checkbox true-label="yes" false-label="no" v-model="form.show_title_description">Show Form Title
                        and Description at frontend
                    </el-checkbox>
                </el-form>
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button @click="editFormModalShow = false">Cancel</el-button>
                <el-button type="primary" @click="editForm()">Update</el-button>
            </span>
        </el-dialog>

    </div>
</template>

<script type="text/babel">
    import WpEditor from '../Common/_wp_editor';

    export default {
        name: 'global_wrapper',
        components: {WpEditor},
        data() {
            return {
                form_id: this.$route.params.form_id,
                current_route: this.$route.name,
                form_menus: [
                    {
                        route: 'edit_form',
                        title: 'Form Builder'
                    },
                    {
                        route: 'payment_options',
                        title: 'Form Settings'
                    },
                    {
                        route: 'design_options',
                        title: 'Design Settings'
                    },
                    {
                        route: 'email_settings',
                        title: 'Email Settings'
                    }
                ],
                editFormModalShow: false,
                form: {},
                fetching: false,
                saving: false,
            }
        },
        methods: {
            getForm() {
                this.fetching = true;
                this.$adminGet({
                    route: 'get_form',
                    form_id: this.form_id
                })
                    .then(response => {
                        this.form = response.data.form;
                    })
                    .fail(error => {

                    })
                    .always(() => {
                        this.fetching = false;
                    })
            },
            editForm() {
                // validate first
                if (!this.form.post_title) {
                    this.$message.error('Please provide form title');
                    return;
                }
                this.saving = true;
                this.$adminPost({
                    route: 'update_form',
                    form_id: this.form.ID,
                    post_title: this.form.post_title,
                    post_content: this.form.post_content,
                    show_title_description: this.form.show_title_description
                })
                    .then(response => {
                        this.$message.success(response.data.message);
                        this.editFormModalShow = false;
                    })
                    .fail(error => {
                        this.$message.error(error.responseJSON.data.message);
                    })
                    .always(() => {
                        this.saving = false;
                    });
            }
        },
        mounted() {
            this.getForm();
        }
    }
</script>
