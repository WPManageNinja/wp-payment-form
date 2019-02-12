<template>
    <div>
        <div v-if="app_ready" v-for="(payMethod, methodKey) in item.available_methods" :key="methodKey" class="method_wrapper">
            <div class="method_header">
                <div class="method_header_title">
                    <el-switch :disabled="!payMethod.isActive" active-value="yes" inactive-value="no" v-model="method_settings.payment_settings[methodKey].enabled"></el-switch> {{payMethod.label}}
                </div>
                <div v-if="payMethod.message" v-html="payMethod.message" class="method_header_info"></div>
            </div>
            <div v-if="method_settings.payment_settings[methodKey].enabled == 'yes'" class="payment_method_body">
                <payment-method-settings :pay_method="payMethod" :settings="method_settings.payment_settings[methodKey]"></payment-method-settings>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
    import each from 'lodash/each';
    import PaymentMethodSettings from './_PaymentMethodSettings'
    export default {
        name: 'choose_payment_method',
        props: ['item', 'method_settings'],
        components: { PaymentMethodSettings },
        data() {
            return {
                app_ready: false
            }
        },
        mounted() {
            if(!this.method_settings.payment_settings) {
                this.$set(this.method_settings, 'payment_settings', {});
            }
            each(this.item.available_methods, (methodName, methodKey) => {
                if(!this.method_settings.payment_settings[methodKey]) {
                    this.$set(this.method_settings.payment_settings, methodKey, {
                        enabled: 'no'
                    });
                }
            });

            this.app_ready = true;
        }
    }
</script>