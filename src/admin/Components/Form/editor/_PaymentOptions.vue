<template>
    <div class="payment_options_settings">

        <el-form-item label="Payment Selection Option">
            <el-radio-group v-model="pricing_details.one_time_type">
                <el-radio v-for="(label, type) in item.one_time_field_options" :key="type" :label="type">{{ label }}
                </el-radio>
            </el-radio-group>
        </el-form-item>

        <template v-if="pricing_details.one_time_type == 'single'">
            <el-form-item label="One Time Payment Amount">
                <el-input-number
                    :precision="2"
                    size="small"
                    v-model="pricing_details.payment_amount"
                    :min="0"></el-input-number>
            </el-form-item>
            <el-form-item label="Show Item title and Price">
                <el-checkbox
                    true-label="yes"
                    false-label="no"
                    v-model="pricing_details.show_onetime_labels">
                    Show item title and price
                </el-checkbox>
            </el-form-item>
        </template>

        <div
            v-else-if="pricing_details.one_time_type == 'choose_single' || pricing_details.one_time_type == 'choose_multiple'"
            class="pricing_key_pair_options">
            <el-form-item label="Pricing Details">
                <key-pair-pricing :value.sync="pricing_details.multiple_pricing"></key-pair-pricing>
            </el-form-item>
            <template v-if="pricing_details.one_time_type == 'choose_single'">
                <el-form-item label="Display Type">
                    <el-radio-group v-model="pricing_details.prices_display_type">
                        <el-radio label="radio">Radio</el-radio>
                        <el-radio label="select">Select</el-radio>
                    </el-radio-group>
                </el-form-item>
            </template>
        </div>
    </div>
</template>
<script type="text/babel">
    import KeyPairPricing from './_key_pair_pricing_options';

    export default {
        name: 'payment_options_settings',
        props: ['item', 'pricing_details'],
        components: {
            KeyPairPricing
        },
        data() {
            return {}
        }
    }
</script>