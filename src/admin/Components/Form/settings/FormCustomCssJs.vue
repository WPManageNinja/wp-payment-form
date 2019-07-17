<template>
    <div class="custom_css_js">
        <div v-loading="fetching" class="edit_form_warpper">
            <div class="all_payforms_wrapper payform_section">
                <div class="payform_section_header">
                    <h3 class="payform_section_title">
                        {{ $t('Custom CSS and JS') }}
                    </h3>
                    <div v-if="has_pro" class="payform_section_actions">
                        <el-button v-loading="saving" @click="saveSettings()" class="payform_action" size="small"
                                   type="primary">
                            {{ $t( 'Save CSS and JS' ) }}
                        </el-button>
                    </div>
                </div>
                <div class="payform_section_body">
                    <div v-if="!has_pro" class="payform_pro_message">
                        <h3>Custom CSS and JS require pro version of WPPayForm.</h3>
                        <p>Please upgrade to pro to unlock this feature. WP Payform Pro comes with lots of feature which
                            will increase the flexebility and conversion rate</p>
                        <a target="_blank" :href="pro_purchase_url">Upgrade to WP Payment Form Pro</a>
                    </div>

                    <div class="wpf_settings_section">
                        <div class="sub_section_header">
                            <h3>Custom CSS</h3>
                            <p>You can write your custom CSS here for this form. This css will be applied in this
                                current form only.</p>
                        </div>
                        <hr/>
                        <div v-if="app_ready" class="sub_section_body">
                            <p>You may add <code>.wpf_form_wrapper_{{form_id}} </code> as your css selector prefix to
                                target this specific form</p>
                            <ace-editor-css editor_id="wpf_custom_css" mode="css" v-model="custom_css"/>
                            <br/>
                            <span>Please don't include <code>&lt;style&gt;&lt;/style&gt;</code> tag</span>
                        </div>
                    </div>

                    <div class="wpf_settings_section">
                        <div class="sub_section_header">
                            <h3>Custom Javascript</h3>
                            <p>Your additional JS code will run after this form initialized. Please provide valid
                                javascript code. Invalid JS code may break the Form.</p>
                        </div>
                        <hr/>
                        <div v-if="app_ready" class="sub_section_body">
                            <div class="js_instruction">
                                The Following JavaScrip variables are available that you can use: <br/>
                                <b>$form</b> : The Javascript DOM object of the Form
                            </div>
                            <br/>
                            <ace-editor-js editor_id="wpf_custom_js" mode="javascript" v-model="custom_js"/>
                            <br/>
                            <span>Please don't include <code>&lt;script>&lt;/script&gt;</code> tag</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
    import AceEditorCss from '../../common/_ace_editor_css';
    import AceEditorJs from '../../common/_ace_editor_js';

    export default {
        name: 'custom_css_js',
        components: {
            AceEditorCss,
            AceEditorJs
        },
        props: ['form_id'],
        data() {
            return {
                fetching: false,
                saving: false,
                custom_css: '',
                custom_js: '',
                app_ready: false
            }
        },
        methods: {
            fetchSettings() {
                this.fetching = true;

                this.$get({
                    action: 'wpf_css_js_endpoints',
                    route: 'get_settings',
                    form_id: this.form_id
                })
                    .then(response => {
                        this.custom_css = response.data.custom_css;
                        this.custom_js = response.data.custom_js;
                    })
                    .fail(error => {
                        this.$showAjaxError(error);
                    })
                    .always(() => {
                        this.fetching = false;
                        this.app_ready = true;
                    });
            },
            saveSettings() {
                this.saving = true;
                this.$post({
                    action: 'wpf_css_js_endpoints',
                    route: 'save_settings',
                    form_id: this.form_id,
                    custom_css: this.custom_css,
                    custom_js: this.custom_js
                })
                    .then(response => {
                        this.$notify.success(response.data.message);
                    })
                    .fail(error => {
                        this.$showAjaxError(error);
                    })
                    .always(() => {
                        this.saving = false;
                    });
            }
        },
        mounted() {
            if(this.has_pro) {
                this.fetchSettings();
            } else {
                this.app_ready = true;
            }
        }
    }
</script>
