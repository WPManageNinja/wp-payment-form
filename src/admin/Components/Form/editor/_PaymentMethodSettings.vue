<template>
    <div v-if="app_ready" class="wpf_method_settings">
        <div v-for="(item, itemName) in pay_method.editor_elements" class="editor_form_item">
            <template v-if="item.type == 'text'">
                <el-form-item :label="item.label">
                    <el-input :placeholder="item.label" size="mini" v-model="settings[itemName]"></el-input>
                </el-form-item>
            </template>
            <template v-else-if="item.type == 'number'">
                <el-form-item :label="item.label">
                    <el-input-number :placeholder="item.label" size="mini" v-model="settings[itemName]"></el-input-number>
                </el-form-item>
            </template>
            <template v-else-if="item.type == 'textarea'">
                <el-form-item :label="item.label">
                    <el-input type="textarea" :placeholder="item.label" size="mini"
                              v-model="settings[itemName]"></el-input>
                </el-form-item>
            </template>
            <template v-else-if="item.type == 'switch'">
                <el-form-item :label="item.label">
                    <el-switch
                        v-model="settings[itemName]"
                        active-value="yes"
                        inactive-value="no">
                    </el-switch>
                </el-form-item>
            </template>
            <template v-else-if="item.type == 'checkout_display_options'">
                <checkout-display-option :item="item" :item_name="itemName" :checkout_settings="settings[itemName]"></checkout-display-option>
            </template>
        </div>
    </div>
</template>
<script type="text/babel">
    import CheckoutDisplayOption from './_StripeCheckoutSettings';
    import each from 'lodash/each';

    export default {
        name: 'pay_method_settings',
        props: ['settings', 'pay_method'],
        components: {
            CheckoutDisplayOption
        },
        data() {
            return {
                app_ready: false
            }
        },
        mounted() {
            each(this.pay_method.editor_elements, (element, elementName) => {
                if(!this.settings[elementName]) {
                    if(this.settings[elementName] == null && element.default) {
                        this.$set(this.settings, elementName, element.default);
                    }

                    if(element.type == 'checkout_display_options' && this.settings[elementName] == null) {
                        this.$set(this.settings, elementName, {});
                    }
                }
            });
            this.app_ready = true;
        }
    }
</script>