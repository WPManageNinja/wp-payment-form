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
                    <div
                        v-loading="fetching"
                        style="min-height: 200px"
                        element-loading-background="rgb(255, 255, 255)"
                        element-loading-text="Loading Builder..."
                        id="payform_builder" class="payform_section_body">
                        <div v-if="validationErrors" class="validation_errors_block">
                            <el-alert v-for="(error,error_key) in validationErrors" :key="error_key" type="error">
                                {{error}}
                            </el-alert>
                        </div>

                        <div class="payform_builder_items">
                            <draggable
                                :options="{handle:'.handler', animation: 0, ghostClass: 'ghost', group:'components'}"
                                :list="builder_elements"
                                :element="'div'"
                            >
                                <template v-if="builder_elements.length">
                                    <div v-for="element in builder_elements" :key="element.id"
                                         class="payform_builder_item">
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
                                        <transition name="slide-down">
                                            <div v-if="current_editing == element.id"
                                                 class="payform_builder_item_settings">
                                                <element-editor @deleteItem="deleteItem(element)"
                                                                @updateItem="saveSettings"
                                                                :element="element" :all_elements="builder_elements"/>
                                            </div>
                                        </transition>
                                    </div>
                                </template>
                                <div v-else class="empty_builder_items">
                                    <img style="max-width: 100%; max-height: 300px;" :src="empty_form_image"/>
                                </div>
                            </draggable>
                            <div v-if="builder_elements.length"
                                 class="payform_submit_button_settings payform_builder_item">
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
                                <transition name="slide-down">
                                    <div v-if="current_editing == '_submit_button'"
                                         class="payform_builder_item_settings">
                                        <submit-button-settings @updateSettings="saveSettings"
                                                                :submit_button="submit_button_settings"/>
                                    </div>
                                </transition>
                            </div>
                        </div>

                        <el-alert
                            v-if="form_tips && showNotice"
                            class="payform_builder_notices"
                            @close="hideNotices"
                            type="warning">
                            <p v-html="form_tips"></p>
                        </el-alert>
                    </div>
                </div>
            </div>
        </el-main>
        <el-aside width="250px">
            <div v-for="(componentGroup,componentGroupIndex) in fieldGroups" class="wpf_field_group" :class="isActiveGroup(componentGroupIndex)">
                <div @click="toggleActiveGroup(componentGroupIndex)" class="field_group_header">
                    <i :class="componentGroup.icon"></i> {{componentGroup.title}}
                    <span v-if="isActiveGroup(componentGroupIndex)" class="field_group_icon_nav"><i class="el-icon-caret-top"></i></span>
                    <span v-else class="field_group_icon_nav"><i class="el-icon-caret-bottom"></i></span>
                </div>
                <div style="min-height: 50px" element-loading-spinner="el-icon-loading" v-loading="fetching" class="field_group_body">
                    <draggable v-model="fieldGroups[componentGroupIndex].elements"
                               class="dragArea"
                               :clone="cloneItem"
                               :options="{ draggable:'.item_active', group:{ name:'components',  pull:'clone', put:false }, sort: false }">
                        <el-button
                            v-for="(component,componentIndex) in componentGroup.elements"
                            :key="'item_'+componentIndex"
                            size="mini"
                            :data-item_name="component.type"
                            type="plain"
                            class="wpf_element_items"
                            :class="getItemClasses(component)"
                            @click="addComponent(component)"
                        >
                            {{ component.editor_title }}
                        </el-button>
                    </draggable>
                </div>
            </div>
        </el-aside>
    </el-container>
</template>

<script type="text/babel">
    import each from 'lodash/each'
    import findIndex from 'lodash/findIndex'
    import ElementEditor from './editor/_ElementEditor';
    import draggable from 'vuedraggable'
    import SubmitButtonSettings from './editor/_SubmitButtonSettings';

    export default {
        name: 'form_builder',
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
                empty_form_image: window.wpPayFormsAdmin.assets_url + 'images/form_instruction.png',
                showNotice: true,
                validationErrors: false,
                collapsedGroups: {
                    payment: false,
                    payment_method: false,
                    general: false
                }
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
            hasPaymentMethodField() {
                let hasField = false;
                each(this.builder_elements, (element) => {
                    if (element.group == 'payment_method_element') {
                        hasField = true;
                        return true;
                    }
                });
                return hasField;
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
                    return 'You have added payment method field, to accept payments add <b>Product Fields</b>';
                }
                if (hasPaymentField && !hasItemField) {
                    return 'You have added order item field, to accept payments add <b>Payment Method Field</b>';
                }
                if (this.builder_elements.length && !hasPaymentField && !hasItemField) {
                    return 'Add <b>Product Fields</b> and <b>Payment Method Field</b> to accept payment';
                }
                return false;
            },
            fieldGroups() {
                let componentGroups = {
                    payment: {
                        title: 'Product Fields',
                        elements: [],
                        icon: 'dashicons dashicons-cart'
                    },
                    payment_method: {
                        title: 'Payment Method Fields',
                        elements: [],
                        icon: 'dashicons dashicons-shield'
                    },
                    general: {
                        title: 'General Fields',
                        icon: 'dashicons dashicons-feedback',
                        elements: []
                    }
                }
                each(this.components, (component, componentName) => {
                    if (componentGroups[component.postion_group]) {
                        componentGroups[component.postion_group].elements.push(component);
                    }
                });
                return componentGroups;
            }
        },
        methods: {
            isActiveGroup(index) {
                if(this.collapsedGroups[index]) {
                    return 'wpf_collapsed';
                }
                return false;
            },
            toggleActiveGroup(index) {
                this.collapsedGroups[index] = !this.collapsedGroups[index];
            },
            cloneItem(component) {
                if(component.disabled) {
                    this.handlePro(component);
                    return;
                }
                return this.getClonedItem(component);
            },
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
            addComponent(component, newIndex) {
                let componentName = component.type;
                if (!componentName) {
                    this.$message({
                        message: 'Please Select valid Component',
                        type: 'error'
                    });
                    return;
                }

                if(component.disabled) {
                   return this.handlePro(component);
                }

                // check if it's single only
                if (component.single_only && this.alreadyExistElement(component.group)) {
                    this.$message({
                        message: 'Element already exists on your form. You can not add more of this type',
                        type: 'error'
                    });
                    return;
                }
                let nonMutableElement = this.getClonedItem(component);
                if (newIndex == undefined) {
                    this.builder_elements.push(nonMutableElement);
                } else {
                    this.builder_elements.splice(newIndex, 0, nonMutableElement);
                }
                this.current_editing = nonMutableElement.id;
                this.adding_component = '';
            },
            handlePro(component) {
                let message = 'This item is disabled';
                if(component.disabled_message) {
                    message = component.disabled_message;
                }
                this.$notify.error(message);
                return false;
            },
            getClonedItem(component) {
                let componentName = component.type;
                let nonMutableElement = JSON.parse(JSON.stringify(component));
                // Find an unique name for this elament
                nonMutableElement.id = this.getComponentUID(componentName);
                if (!nonMutableElement.field_options) {
                    nonMutableElement.field_options = {};
                }
                return nonMutableElement;
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
                this.validationErrors = false;
                if (!this.form_tips) {
                    this.deleteStoreData('hide_form_' + this.form_id);
                }

                this.$adminPost({
                    action: 'wppayform_forms_admin_ajax',
                    form_id: this.form_id,
                    builder_settings: JSON.stringify(this.builder_elements),
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
                        if (error.responseJSON.data.errors) {
                            this.validationErrors = error.responseJSON.data.errors;
                            jQuery([document.documentElement, document.body]).animate({
                                scrollTop: jQuery('#payform_builder').offset().top - 100
                            }, 200);
                        }
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
            },
            getItemClasses(component) {
                let isActive = '';
                if (!this.hasPaymentMethodField || component.group != 'payment_method_element') {
                    isActive = ' item_active';
                }
                return 'wpf_item_' + component.group + isActive;
            },
            hideNotices() {
                this.setStoreData('hide_form_' + this.form_id, 'yes');
            }
        },
        mounted() {
            this.getSettings();
            this.showNotice = this.getFromStore('hide_form_' + this.form_id) != 'yes';
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
        opacity: 0.2;
        background: gray;
        color: white;
        width: 100%;
        padding: 15px;
        display: block;
        overflow: hidden;
    }
</style>