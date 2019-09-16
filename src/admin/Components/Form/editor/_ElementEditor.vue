<template>
    <div class="element_editor">

        <div class="wpf_editor_tabs">
            <div class="wpf_tabs_header">
                <div @click="showing_tab = 'general'" :class="(showing_tab == 'general') ? 'wpf_tab_active' : ''" class="wpf_tab">General</div>
                <div @click="showing_tab = 'advanced'" :class="(showing_tab == 'advanced') ? 'wpf_tab_active' : ''" class="wpf_tab">Advanced</div>
            </div>
            <div class="wpf_tab_body">
                <el-form ref="element_form" :class="'wpf_element_editor_form wpf_showing_'+showing_tab" :model="element" label-width="220px">
                    <div v-for="(item, itemName) in element.editor_elements" :class="[item.wrapper_class, 'wpf_item_group_'+item.group]"
                         class="editor_form_item">
                        <template v-if="item.type == 'text'">
                            <el-form-item :label="item.label">
                                <template v-if="itemName == 'default_value'">
                                    <el-input :placeholder="item.label" size="mini" v-model="element.field_options[itemName]">
                                        <popover
                                            v-if="has_pro"
                                            @command="(code) => { element.field_options[itemName] += code }"
                                            slot="suffix" :data="merge_tags"
                                            btnType="text"
                                            buttonText='<i class="el-icon-menu"></i>'>
                                        </popover>
                                        <el-button @click="showDevaultValuePro = true" type="text" size="mini" icon="el-icon-menu" slot="suffix" v-else></el-button>
                                    </el-input>
                                </template>
                                <template v-else>
                                    <el-input :placeholder="item.label" size="mini"
                                              v-model="element.field_options[itemName]"></el-input>
                                </template>
                            </el-form-item>
                        </template>
                        <template v-else-if="item.type == 'number'">
                            <el-form-item :label="item.label">
                                <el-input-number :placeholder="item.label" size="mini"
                                                 v-model="element.field_options[itemName]"></el-input-number>
                            </el-form-item>
                        </template>
                        <template v-else-if="item.type == 'textarea'">
                            <el-form-item :label="item.label">
                                <el-input type="textarea" :placeholder="item.label" size="mini"
                                          v-model="element.field_options[itemName]"></el-input>
                            </el-form-item>
                        </template>
                        <template v-else-if="item.type == 'checkbox'">
                            <el-form-item :label="item.label">
                                <el-checkbox-group v-model="element.field_options[itemName]">
                                    <el-checkbox v-for="(option,optionName) in item.options" :label="optionName"
                                                 :key="optionName">{{option}}
                                    </el-checkbox>
                                </el-checkbox-group>
                            </el-form-item>
                        </template>
                        <template v-else-if="item.type == 'switch'">
                            <el-form-item :label="item.label">
                                <el-switch
                                    v-model="element.field_options[itemName]"
                                    active-value="yes"
                                    inactive-value="no">
                                </el-switch>
                                <p v-if="item.info" v-html="item.info"></p>
                            </el-form-item>
                        </template>
                        <template v-else-if="item.type == 'key_pair'">
                            <el-form-item :label="item.label">
                                <key-pair-options :value.sync="element.field_options[itemName]"/>
                            </el-form-item>
                        </template>
                        <template v-else-if="item.type == 'html'">
                            <el-form-item :label="item.label">
                                <el-input
                                    type="textarea"
                                    rows="5"
                                    :placeholder="item.label"
                                    v-model="element.field_options[itemName]"></el-input>
                                <div v-if="item.info" class="html_placeholder_instruction" v-html="item.info"></div>
                            </el-form-item>
                        </template>
                        <template v-else-if="item.type == 'payment_options'">
                            <payment-options-settings :item="item" :pricing_details="element.field_options.pricing_details"/>
                        </template>
                        <template v-else-if="item.type == 'product_selector'">
                            <el-form-item :label="item.label">
                                <item-selector :all_elements="all_elements" :item_name="itemName"
                                               :field_options="element.field_options"/>
                                <p v-if="item.info" v-html="item.info"></p>
                            </el-form-item>
                        </template>
                        <template v-else-if="item.type == 'onetime_products_selector'">
                            <el-form-item :label="item.label">
                                <one-time-products-selector :all_elements="all_elements" :item_name="itemName"
                                               :field_options="element.field_options"/>
                                <p v-if="item.info" v-html="item.info"></p>
                            </el-form-item>
                        </template>
                        <template v-else-if="item.type == 'all_product_selector'">
                            <el-form-item :label="item.label">
                                <all-item-selector :all_elements="all_elements" :item_name="itemName"
                                                   :field_options="element.field_options"/>
                                <p v-if="item.info" v-html="item.info"></p>
                            </el-form-item>
                        </template>
                        <template v-else-if="item.type == 'select_option'">
                            <el-form-item :label="item.label">
                                <el-select :allow-create="item.creatable == 'yes'" filterable default-first-option
                                           class="item_full_width" size="small" v-model="element.field_options[itemName]">
                                    <el-option v-for="(option_name,option_key) in item.options" :key="option_key"
                                               :label="option_name" :value="option_key"></el-option>
                                </el-select>
                                <p v-if="item.info" v-html="item.info"></p>
                            </el-form-item>
                        </template>
                        <template v-else-if="item.type == 'checkout_display_options'">
                            <checkout-display-option :item="item" :item_name="itemName"
                                                     :checkout_settings="element.field_options[itemName]"></checkout-display-option>
                        </template>
                        <template v-else-if="item.type == 'choose_payment_method'">
                            <payment-method-choice :item="item" :method_settings="element.field_options[itemName]"/>
                        </template>
                        <template v-else-if="item.type == 'info_html'">
                            <div v-html="item.info"></div>
                        </template>
                        <template v-else-if="item.type == 'tabular_products'">
                            <tabular-products :item="item"
                                              :field_options="element.field_options"
                                              :product_settings="element.field_options[itemName]">
                            </tabular-products>
                        </template>
                        <template v-else-if="item.type == 'recurring_payment_options'">
                            <recurring-item
                                :item="item"
                                :product_settings="element.field_options[itemName]" />
                        </template>
                        <template v-else-if="item.type == 'confirm_email_switch'">
                            <el-form-item :label="item.label">
                                <el-switch
                                    v-model="element.field_options[itemName]"
                                    active-value="yes"
                                    inactive-value="no">
                                </el-switch>
                                <p v-if="item.info" v-html="item.info"></p>
                            </el-form-item>
                            <el-form-item v-if="element.field_options[itemName] == 'yes'" label="Confirm Email Label">
                                <el-input placeholder="Confirm Email Label" size="mini"
                                          v-model="element.field_options.confirm_email_label"></el-input>
                            </el-form-item>
                        </template>
                    </div>
                    <el-form-item class="wpf_item_group_advanced" label="Field ID">
                        {{ element.id }}
                    </el-form-item>
                    <div class="action_right">
                        <el-button @click="deleteItem()" size="mini">Delete</el-button>
                        <el-button @click="updateItem()" type="success" size="mini">Update</el-button>
                    </div>
                </el-form>
            </div>
        </div>

        <el-dialog
            title="Default Value is a Pro Feature"
            :visible.sync="showDevaultValuePro"
            :append-to-body="true"
            width="60%">
            <div v-if="showDevaultValuePro" class="modal_body wpf_default_value_modal">
                <img :src="assets_url+'images/default_value_screen.png'" />
                <h3>Add Default Value from dynamic variables from WordPress / URL Parameter</h3>
                <a class="el-button el-button--success" target="_blank" rel="noopener" :href="pro_purchase_url">Upgrade To Pro</a>
            </div>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
    import KeyPairOptions from './_key_pair_options';
    import PaymentOptionsSettings from './_PaymentOptions'
    import ItemSelector from './_ProductSelector';
    import OneTimeProductsSelector from './_OnetimeProductsSelector';
    import AllItemSelector from './_AllProductSelector';
    import CheckoutDisplayOption from './_StripeCheckoutSettings';
    import PaymentMethodChoice from './_payment_method_settings';
    import TabularProducts from './_tabular_products'
    import popover from '../../Common/input-popover-dropdown.vue';
    import RecurringItem from './_RecurringItem';

    export default {
        name: 'elementEditor',
        components: {
            KeyPairOptions,
            PaymentOptionsSettings,
            ItemSelector,
            CheckoutDisplayOption,
            PaymentMethodChoice,
            TabularProducts,
            popover,
            RecurringItem,
            AllItemSelector,
            OneTimeProductsSelector
        },
        props: ['element', 'all_elements'],
        comments: {
            KeyPairOptions
        },
        data() {
            return {
                merge_tags: Object.values(window.wpPayFormsAdmin.value_placeholders),
                showDevaultValuePro: false,
                assets_url: window.wpPayFormsAdmin.assets_url,
                showing_tab: 'general'
            }
        },
        methods: {
            deleteItem() {
                this.$emit('deleteItem', this.element);
            },
            updateItem() {
                this.$emit('updateItem', this.element);
            }
        },
        mounted() {
            if (!this.element.field_options) {
                this.$set(this.element, 'field_options', {});
            }
        }
    }
</script>