<template>
    <div>
        <el-form-item :label="item.label">
            <el-radio-group class="wpf_block_items" v-model="checkout_settings.style">
                <el-radio v-for="(checkoutStyle, checkoutStyleName) in checkputStyles" :key="checkoutStyleName" :label="checkoutStyleName">{{checkoutStyle}}</el-radio>
            </el-radio-group>
        </el-form-item>

        <template v-if="checkout_settings.style == 'stripe_checkout'">
            <el-form-item label="Require Billing info">
                <el-switch
                    v-model="checkout_settings.require_billing_info"
                    active-value="yes"
                    inactive-value="no">
                </el-switch>
            </el-form-item>
        </template>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'stripeCheckoutSettings',
        props: ['checkout_settings', 'item_name', 'item'],
        data() {
            return {
                checkputStyles: {
                    'stripe_checkout': 'Stripe Hosted Checkout (Recommended)',
                    'embeded_form': 'Embedded Checkout Field'
                }
            }
        }
    }
</script>