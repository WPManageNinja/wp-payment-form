<template>
    <div class="edit_form_warpper">
        <div class="all_payforms_wrapper payform_section">
            <div class="payform_section_header">
                <h3 class="payform_section_title">
                    {{ $t('Custom Form Fields') }}
                </h3>
                <div class="payform_section_actions">
                    <el-button @click="saveSettings()" class="payform_action" size="small" type="primary">
                        {{ $t( 'Save Form Settings' ) }}
                    </el-button>
                </div>
            </div>
            <div class="payform_section_body">
                <div class="payform_element_control">
                    <label>
                        Add Form Field
                        <select v-model="adding_component">
                            <option value="">-- Select --</option>
                            <option v-for="(component,component_name) in components" :key="component_name"
                                    :value="component_name">{{ component.editor_title }}
                            </option>
                        </select>
                        <el-button @click="addComponent()" type="info" size="mini" v-if="adding_component">Add To Form
                        </el-button>
                    </label>
                </div>

                <div class="payform_builder_items">
                    <draggable
                        :options="{handle:'.handler'}"
                        :list="builder_elements"
                        :element="'div'"
                    >
                        <div v-for="element in builder_elements" class="payform_builder_item">
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
                    </draggable>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
    import each from 'lodash/each'
    import findIndex from 'lodash/findIndex'
    import ElementEditor from './_ElementEditor';
    import draggable from 'vuedraggable'

    export default {
        name: 'payment_settings',
        components: {ElementEditor, draggable},
        props: ['form_id'],
        data() {
            return {
                current_editing: '',
                builder_elements: [],
                fetching: false,
                components: {},
                adding_component: ''
            }
        },
        computed: {
            elementIds() {
                let elements = [];
                each(this.builder_elements, (element) => {
                    elements.push(element.id);
                });
                return elements;
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
                    })
                    .fail(error => {

                    })
                    .always(() => {
                        this.fetching = false;
                    })
            },
            addComponent() {
                let componentName = this.adding_component;
                let component = this.components[componentName];
                if (!componentName) {
                    this.$message({
                        message: 'Please Select valid Component',
                        type: 'error'
                    });
                    return;
                }

                // check if it's single only
                if(component.single_only && this.alreadyExistElement(component.type)) {
                    this.$message({
                        message: 'Element already exists on your form. You can not add more of this item',
                        type: 'error'
                    });
                    return;
                }

                let nonMutableElement = JSON.parse(JSON.stringify(component));
                // Find an unique name for this elament
                nonMutableElement.id = this.getComponentUID(componentName);
                if(!nonMutableElement.field_options) {
                    nonMutableElement.field_options = {};
                }
                this.builder_elements.push(nonMutableElement);
                this.current_editing = nonMutableElement.id;
                this.adding_component = '';
            },

            alreadyExistElement(type) {
                let status = false;
                each(this.builder_elements, (element) => {
                    if(element.type == type) {
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
                    action: 'save_form_settings',
                    form_id: this.form_id,
                    settings: this.builder_elements,
                    settings_key: '_wp_paymentform_builder_settings',
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
        }
    }
</script>