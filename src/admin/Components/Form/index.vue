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
                          :data-clipboard-text='`[wppayform id="${form.ID}"]`'>
                        <i class="el-icon-document"></i> [wppayform id="{{ form.ID }}"]
                    </code>
                </el-tooltip>
            </div>
            <div class="settings_right">
                <a :href="form.preview_url" target="_blank">
                    <el-button size="mini">{{ $t('Preview') }}</el-button>
                </a>
                <a
                    v-if="!has_pro"
                    target="_blank"
                    class="el-button payform_action el-button--danger el-button--mini"
                    :href="pro_purchase_url">
                    Upgrade To Pro
                </a>
            </div>
        </div>
        <el-menu mode="horizontal"
                 :router="true"
                 :default-active="current_route"
        >
            <el-menu-item
                v-for="formMenu in form_menus"
                :key="formMenu.route"
                :route="{ name: formMenu.route, params: { form_id: form_id } }"
                :index="formMenu.route">
                <i :class="formMenu.icon"></i>
                <span>{{  formMenu.title }}</span>
            </el-menu-item>
            <el-menu-item
                :route="{ name: 'form_entries', params: { form_id: form_id } }"
                index="entries"
            >
                <i class="dashicons dashicons-text"></i>
                <span>{{  $t('Form Entries') }}</span>
            </el-menu-item>
        </el-menu>
        <div class="payform_editor_wrapper">
            <router-view :form_id="form_id"></router-view>
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
    import Clipboard from 'clipboard';
    export default {
        name: 'global_wrapper',
        components: {WpEditor},
        data() {
            return {
                form_id: this.$route.params.form_id,
                current_route: this.$route.name,
                form_menus: [],
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
            },
            setFormMenu() {
                this.form_menus = this.applyFilters('wpf_set_form_menus', [
                    {
                        route: 'edit_form',
                        title: 'Form Fields',
                        icon: 'dashicons dashicons-lightbulb'
                    },
                    {
                        route: 'confirmation_settings',
                        title: 'Form Settings',
                        icon: 'dashicons dashicons-admin-settings'
                    },
                    {
                        route: 'email_settings',
                        title: 'Email Notifications',
                        icon: 'dashicons dashicons-email-alt'
                    },
                ], this.form_id);
            }
        },
        mounted() {
            this.setFormMenu();
            this.getForm();
            var clipboard = new Clipboard('.copy');
            clipboard.on('success', (e) => {
                this.$message({
                    message: 'Copied to Clipboard!',
                    type: 'success'
                });
            });
        }
    }
</script>
