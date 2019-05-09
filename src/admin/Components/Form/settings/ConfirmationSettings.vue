<template>
    <el-container>
        <el-main class="no_shadow">
            <div class="edit_form_warpper">
                <div class="all_payforms_wrapper payform_section">
                    <div class="payform_section_header">
                        <h3 class="payform_section_title">
                            {{ $t('Form Confirmation Settings') }}
                        </h3>
                        <div class="payform_section_actions">
                            <el-button @click="saveSettings()" class="payform_action" size="small" type="primary">
                                {{ $t( 'Save Confirmation Settings' ) }}
                            </el-button>
                        </div>
                    </div>
                    <div class="payform_section_body">
                        <el-form ref="payment_settings" :model="payment_settings" label-width="220px">
                            <confirmation-settings
                                :pages="pages"
                                :editorShortcodes="editorShortcodes"
                                :confirmation="confirmation_settings">
                            </confirmation-settings>
                        </el-form>
                    </div>
                </div>
            </div>
        </el-main>
    </el-container>
</template>

<script type="text/babel">
    import ConfirmationSettings from './_AddConfirmation';
    export default {
        name: 'confirmation_settings',
        props: ['form_id'],
        components: {
            ConfirmationSettings
        },
        data() {
            return {
                payment_settings: {},
                fetching: false,
                pages: [],
                editorShortcodes: [],
                confirmation_settings: {},
                app_ready: false
            }
        },
        methods: {
            getSettings() {
                this.fetching = true;
                this.$adminGet({
                    route: 'get_form_settings',
                    form_id: this.form_id
                })
                    .then(response => {
                        this.confirmation_settings = response.data.confirmation_settings;
                        this.editorShortcodes = Object.values(response.data.editor_shortcodes);
                        this.pages = response.data.pages;
                    })
                    .fail(error => {

                    })
                    .always(() => {
                        this.fetching = false;
                        this.app_ready = true;
                    })
            },
            saveSettings() {
                this.saving = true;
                this.$adminPost({
                    form_id: this.form_id,
                    confirmation_settings: this.confirmation_settings,
                    route: 'save_form_settings'
                })
                    .then(response => {
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
                        this.saving = false;
                    })
            }
        },
        mounted() {
            this.getSettings();
            window.WPPayFormsBus.$emit('site_title', 'Form Confirmation Settings');
        }
    }
</script>