<template>
    <div class="element_editor">
        <el-form ref="element_form" :model="element" label-width="220px">
            <div v-for="(item, itemName) in element.editor_elements" class="editor_form_item">
                <template v-if="item.type == 'text'">
                    <el-form-item :label="item.label">
                        <el-input :placeholder="item.label" size="mini" v-model="element.field_options[itemName]"></el-input>
                    </el-form-item>
                </template>
                <template v-if="item.type == 'number'">
                    <el-form-item :label="item.label">
                        <el-input-number :placeholder="item.label" size="mini" v-model="element.field_options[itemName]"></el-input-number>
                    </el-form-item>
                </template>
                <template v-else-if="item.type == 'textarea'">
                    <el-form-item :label="item.label">
                        <el-input type="textarea" :placeholder="item.label" size="mini"
                                  v-model="element.field_options[itemName]"></el-input>
                    </el-form-item>
                </template>
                <template v-else-if="item.type == 'switch'">
                    <el-form-item :label="item.label">
                        <el-switch
                            v-model="element.field_options[itemName]"
                            active-value="yes"
                            inactive-value="no">
                        </el-switch>
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
                        <div class="html_placeholder_instruction">
                            You can use the following dynamic placeholder on your HTML
                            <span>{payment_total}</span>
                        </div>
                    </el-form-item>
                </template>
                <template v-else-if="item.type == 'payment_options'">
                    <payment-options-settings :item="item" :pricing_details="element.field_options.pricing_details"/>
                </template>
                <template v-else-if="item.type == 'product_selector'">
                    <el-form-item :label="item.label">
                        <item-selector :all_elements="all_elements" :item_name="itemName" :field_options="element.field_options" />
                    </el-form-item>
                </template>
                <template v-else-if="item.type == 'select_option'">
                    <el-form-item :label="item.label">
                        <el-select class="item_full_width" size="small" v-model="element.field_options[itemName]">
                            <el-option v-for="(option_name,option_key) in item.options" :key="option_key" :label="option_name" :value="option_key"></el-option>
                        </el-select>
                    </el-form-item>
                </template>
                <template v-else-if="item.type == 'checkout_display_options'">
                    <checkout-display-option :item="item" :item_name="itemName" :checkout_settings="element.field_options[itemName]"></checkout-display-option>
                </template>
                <template v-else>

                </template>
            </div>
            <el-form-item label="Field ID">
                {{ element.id }}
            </el-form-item>
            <div class="action_right">
                <el-button @click="deleteItem()" size="mini">Delete</el-button>
                <el-button @click="updateItem()" type="success" size="mini">Update</el-button>
            </div>

        </el-form>
    </div>
</template>

<script type="text/babel">
    import KeyPairOptions from './_key_pair_options';
    import PaymentOptionsSettings from './_PaymentOptions'
    import ItemSelector from './_ProductSelector';
    import CheckoutDisplayOption from './_StripeCheckoutSettings';

    export default {
        name: 'elementEditor',
        components: {
            KeyPairOptions,
            PaymentOptionsSettings,
            ItemSelector,
            CheckoutDisplayOption
        },
        props: ['element', 'all_elements'],
        comments: {
            KeyPairOptions
        },
        data() {
            return {}
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