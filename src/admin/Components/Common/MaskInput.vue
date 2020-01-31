<template>
    <div class="mask_input_wrapper">
        <el-form-item :label="item.label">
            <el-select class="item_full_width" size="small" v-model="field_options.mask">
                <el-option v-for="(option_name,option_key) in item.options" :key="option_key"
                           :label="option_name" :value="option_key"></el-option>
            </el-select>
            <p v-if="item.info" v-html="item.info"></p>
        </el-form-item>
        <template v-if="field_options.mask == 'custom'">
            <el-form-item label="Custom Mask Pattern">
                <el-input placeholder="Custom Mask Pattern" size="mini"
                          v-model="field_options.mask_custom"></el-input>
            </el-form-item>
            <el-form-item label="Do Reverse Mask">
                <el-switch
                    v-model="field_options.is_mask_reverse"
                    active-value="yes"
                    inactive-value="no">
                </el-switch>
            </el-form-item>
            <el-button @click="show_info = !show_info" size="mini" type="text">
                <span v-if="show_info">Hide info</span>
                <span v-else>Show info</span>
            </el-button>
            <div v-show="show_info" class="mask_info_wrapper">
                <h4 style="margin-bottom: 5px;">Usage</h4>
                <ul>
                    <li>Use a '0' to indicate a numerical character.</li>
                    <li>Use a '9' to indicate a numerical character but optional.</li>
                    <li>Use a '#' to indicate a numerical character but recursive.</li>
                    <li>Use a upper case 'A' to indicate Letters and number [a-zA-Z0-9].</li>
                    <li>Use 'S' to indicate Letters.</li>
                    <li>All other characters are literal values and will be displayed automatically.</li>
                </ul>
                <h4 style="margin-bottom: 5px;">Examples</h4>
                <p><strong>Date </strong> Mask:
                    <mark>00/00/0000</mark>
                    . Valid Input:
                    <mark>10/21/2011</mark>
                </p>
                <p><strong>Social Security Number </strong> Mask:
                    <mark>000-00-0000</mark>
                    . Valid Input:
                    <mark>987-65-4329</mark>
                </p>
                <p><strong>Course Code </strong> Mask:
                    <mark>SSS 999</mark>
                    . Valid Input:
                    <mark>BIO 101</mark>
                </p>
                <a href="https://igorescobar.github.io/jQuery-Mask-Plugin/docs.html" target="_blank">view full library
                    documentation</a>
            </div>
        </template>
    </div>
</template>

<script>
    export default {
        name: "mask_input",
        props: ['item', 'field_options'],
        data() {
            return {
                show_info: false
            }
        }
    }
</script>