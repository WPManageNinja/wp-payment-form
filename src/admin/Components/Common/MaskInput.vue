<template>
    <div class="mask_input_wrapper">
        <div class="mask_input_field">
            <p>Mask Input:</p>
            <el-select v-model="field_options.mask_input.value">
                <el-option
                    v-for="item in mask_input_options"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value">
                </el-option>
            </el-select>
        </div>
        <div class="mask_input_field_req">
            <p>Required</p>
            <el-switch
                active-value="yes"
                inactive-value="no"
                v-model="field_options.mask_input.required"></el-switch>
        </div>
    </div>
</template>

<script>
export default {
    name: "mask_input",
    props: [ 'fields', 'field_options' ],
    data() {
        return {
            mask_input_options: []
        }
    },
    created() {
        this.mask_input_options = this.field_options.mask_input.options
    }
}
</script>