<template>
    <div class="payment_options_settings">
        <el-form-item label="Payment Selection Option">
            <el-radio-group v-model="pricing_details.one_time_type">
                <el-radio v-for="(label, type) in item.one_time_field_options" :key="type" :label="type">{{ label }}
                </el-radio>
            </el-radio-group>
        </el-form-item>
        <template v-if="pricing_details.one_time_type == 'single'">
            <div class="payment_actions" style="margin-bottom:30px; margin-left:30px;">
                <div class="imageUpload" v-if="enableImage == 'yes'">
                    <div v-for="(item,index) in pricing_details.image_url" :key="index">
                        <photo-widget :product="item" />
                    </div>
                </div>
         </div>
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
                <key-pair-pricing :value.sync="pricing_details.multiple_pricing" :enableImage='enableImage'></key-pair-pricing>
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
    import PhotoWidget from './_photo_widget';


    export default {
        name: 'payment_options_settings',
        props: ['item', 'pricing_details','enableImage'],
        components: {
            KeyPairPricing,
            PhotoWidget
        },
        data() {
            return {
                value1 : true
            }
        }
    }
</script>

<style scoped>
.imageUpload{
    padding: 10px;
    width: 100px;
    height: 80px;
    margin-top: 20px;
    margin-bottom: 20px;
    border-radius: 5px;
    border: 2px solid;
    border-color: #469EFF;
    margin-left:40px;
    cursor: pointer;
}

</style>