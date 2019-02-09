<template>
    <el-container>
        <el-main>
            <div class="edit_form_warpper">
                <div class="all_payforms_wrapper payform_section">
                    <div class="payform_section_header">
                        <h3 class="payform_section_title">
                            {{ $t('Custom Form Fields') }}
                        </h3>
                        <div class="payform_section_actions">
                            <el-button @click="saveSettings()" class="payform_action" size="small" type="primary">
                                {{ $t( 'Update Fields' ) }}
                            </el-button>
                        </div>
                    </div>
                    <div class="payform_section_body">
                        <div v-if="builder_elements.length" class="payform_builder_items">
                            <draggable
                                :options="{handle:'.handler', animation: 0, ghostClass: 'ghost'}"
                                :list="builder_elements"
                                :element="'div'"
                            >
                                <transition-group type="transition">
                                    <div v-for="element in builder_elements" :key="element.id" class="payform_builder_item">
                                        <div class="payform_builder_header">
                                            <div class="payform_head_left">
                                                <div class="handler payform_inline_item">
                                                    <span class="dashicons dashicons-menu"></span>
                                                </div>
                                                <div class="element_title payform_inline_item">
                                                    {{ (element.field_options && element.field_options.label) ?
                                                    element.field_options.label : element.editor_title }}
                                                </div>
                                            </div>
                                            <div @click="toggleEditing(element.id)" class="payform_head_right">
                                                <div class="element_type payform_inline_item">
                                                    {{ element.editor_title }}
                                                </div>
                                                <div class="element_control payform_inline_item">
                                                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-if="current_editing == element.id" class="payform_builder_item_settings">
                                            <element-editor @deleteItem="deleteItem(element)" @updateItem="saveSettings"
                                                            :element="element" :all_elements="builder_elements"/>
                                        </div>
                                    </div>
                                </transition-group>
                            </draggable>

                            <div v-if="builder_elements.length" class="payform_submit_button_settings payform_builder_item">
                                <div class="payform_builder_header">
                                    <div class="payform_head_left">
                                        <div class="payform_inline_item">
                                            <span class="el-icon-setting"></span>
                                        </div>
                                        <div class="element_title payform_inline_item">
                                            Submit Button Settings
                                        </div>
                                    </div>
                                    <div @click="toggleEditing('_submit_button')" class="payform_head_right">
                                        <div class="element_type payform_inline_item">
                                            button
                                        </div>
                                        <div class="element_control payform_inline_item">
                                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="current_editing == '_submit_button'" class="payform_builder_item_settings">
                                    <submit-button-settings @updateSettings="saveSettings"
                                                            :submit_button="submit_button_settings"/>
                                </div>
                            </div>
                        </div>
                        <div v-else class="empty_builder_items">
                            <img style="max-width: 100%; max-height: 300px;" :src="empty_form_image" />
                        </div>
                        <div v-if="form_tips" class="payform_builder_notices">
                            <p>
                                <i class="el-icon-info"></i>
                                <span class="payform_builder_error" v-html="form_tips"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </el-main>
        <el-aside width="250px">
            <div v-for="componentGroup in fieldGroups" class="wpf_field_group">
                <div class="field_group_header">
                    <i :class="componentGroup.icon"></i> {{componentGroup.title}}
                </div>
                <div class="field_group_body">
                    <el-button
                        v-for="(component,component_name) in componentGroup.elements"
                        :key="component_name"
                        size="mini"
                        type="plain"
                        class="wpf_element_items"
                        :class="'wpf_item_'+component.group"
                        @click="addComponent(component_name)"
                    >
                        {{ component.editor_title }}
                    </el-button>
                </div>
            </div>
        </el-aside>
    </el-container>
</template>

<script type="text/babel">
    import each from 'lodash/each'
    import findIndex from 'lodash/findIndex'
    import ElementEditor from './_ElementEditor';
    import draggable from 'vuedraggable'
    import SubmitButtonSettings from './_SubmitButtonSettings';
    export default {
        name: 'payment_settings',
        components: {
            ElementEditor,
            draggable,
            SubmitButtonSettings
        },
        props: ['form_id'],
        data() {
            return {
                current_editing: '',
                builder_elements: [],
                fetching: false,
                components: {},
                adding_component: '',
                submit_button_settings: {},
                empty_form_image: window.wpPayFormsAdmin.assets_url+'images/form_instruction.png'
            }
        },
        computed: {
            elementIds() {
                let elements = [];
                each(this.builder_elements, (element) => {
                    elements.push(element.id);
                });
                return elements;
            },
            form_tips() {
                let hasPaymentField = false;
                let hasItemField = false;

                each(this.builder_elements, (element) => {
                    if (element.group == 'payment') {
                        hasPaymentField = true;
                    }
                    if (element.group == 'payment_method_element') {
                        hasItemField = true;
                    }
                });

                if (!hasPaymentField && hasItemField) {
                    return 'You have added payment field, Now please add <b>Product Fields</b> field to accept payments';
                }
                if (hasPaymentField && !hasItemField) {
                    return 'You have added order item field, Now please add <b>Payment Method Field</b> field to accept payments';
                }
                if (this.builder_elements.length && !hasPaymentField && !hasItemField) {
                    return 'Add <b>Product Fields</b> and <b>Payment Method Field</b> field to accept payment';
                }
            },
            fieldGroups() {
                let componentGroups = {
                    payment: {
                        title: 'Product Fields',
                        elements: {},
                        icon: 'dashicons dashicons-cart'
                    },
                    payment_method: {
                        title: 'Payment Method Fields',
                        elements: {},
                        icon: 'dashicons dashicons-shield'
                    },
                    general: {
                        title: 'General Fields',
                        icon: 'dashicons dashicons-feedback',
                        elements: {}
                    }
                }
                each(this.components, (component, componentName) => {
                    if(componentGroups[component.postion_group]) {
                        componentGroups[component.postion_group].elements[componentName] = component;
                    }
                });
                return componentGroups;
            }
        },
        methods: {
            getSettings() {
                this.fetching = true;
                this.$adminGet({
                    route: 'get_custom_form_settings',
                    form_id: this.form_id
                })
                    .then(response => {
                        this.builder_elements = response.data.builder_settings;
                        this.components = response.data.components;
                        this.submit_button_settings = response.data.form_button_settings;
                    })
                    .fail(error => {

                    })
                    .always(() => {
                        this.fetching = false;
                    })
            },
            addComponent(componentName) {
                let component = this.components[componentName];
                if (!componentName) {
                    this.$message({
                        message: 'Please Select valid Component',
                        type: 'error'
                    });
                    return;
                }

                // check if it's single only
                if (component.single_only && this.alreadyExistElement(component.group)) {
                    this.$message( {
                        message: 'Element already exists on your form. You can not add more of this type',
                        type: 'error'
                    } );
                    return;
                }

                let nonMutableElement = JSON.parse(JSON.stringify(component));
                // Find an unique name for this elament
                nonMutableElement.id = this.getComponentUID(componentName);
                if (!nonMutableElement.field_options) {
                    nonMutableElement.field_options = {};
                }
                this.builder_elements.push(nonMutableElement);
                this.current_editing = nonMutableElement.id;
                this.adding_component = '';
            },

            alreadyExistElement(type) {
                let status = false;
                each(this.builder_elements, (element) => {
                    if (element.group == type) {
                        status = true;
                    }
                });
                return status;
            },
            getComponentUID(componentName) {
                let counter = 1;
                let originalName = JSON.parse(JSON.stringify(componentName));
                while (this.elementIds.indexOf(componentName) != -1) {
                    componentName = originalName + '_' + counter;
                    counter = counter + 1;
                }
                return componentName;
            },
            saveSettings() {
                this.saving = true;
                this.$adminPost({
                    action: 'wp_payment_forms_admin_ajax',
                    form_id: this.form_id,
                    builder_settings: this.builder_elements,
                    submit_button_settings: this.submit_button_settings,
                    route: 'save_form_builder_settings'
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
            },
            toggleEditing(elementId) {
                if (this.current_editing == elementId) {
                    this.current_editing = '';
                } else {
                    this.current_editing = elementId;
                }
            },
            deleteItem(element) {
                this.builder_elements.splice(findIndex(this.builder_elements, element), 1);
            }
        },
        mounted() {
            this.getSettings();
            window.WPPayFormsBus.$emit('site_title', 'Form Builder');
        }
    }
</script>

<style lang="scss">
    .flip-list-move {
        transition: transform 0.5s;
    }
    .no-move {
        transition: transform 0s;
    }
    .ghost {
        opacity: 0.1;
        background: gray;
    }
</style>