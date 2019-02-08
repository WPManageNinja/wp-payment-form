<template>
    <div class="submit_btn_settings">
        <el-form ref="submit_button_element_form" :model="submit_button" label-width="220px">
            <el-form-item label="Button Text">
                <el-input placeholder="eg: Pay {payment_total}" size="mini" v-model="submit_button.button_text"></el-input>
                <p>Use Shortcode <code>{payment_total}</code> to show payment total amount</p>
            </el-form-item>
            <el-form-item label="Processing State Button Text">
                <el-input placeholder="eg: Processing, please wait" size="mini" v-model="submit_button.processing_text"></el-input>
            </el-form-item>
            <el-form-item label="Button Style">
                <el-select class="item_full_width" size="small" v-model="submit_button.button_style">
                    <el-option label="Default Style" value="wpf_default_btn"></el-option>
                    <el-option label="Full Width Block Button" value="wpf_full_block_btn"></el-option>
                    <el-option label="No Style (Theme Default)" value=""></el-option>
                </el-select>
            </el-form-item>
            <el-form-item label="Button CSS Class (Optional)">
                <el-input placeholder="css class for this button" size="mini" v-model="submit_button.css_class"></el-input>
            </el-form-item>
            <div class="action_right">
                <el-button @click="updateItem()" type="success" size="mini">Update</el-button>
            </div>
        </el-form>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'submit_button_settings',
        props: ['submit_button'],
        methods: {
            updateItem() {
                this.$emit('updateSettings', this.submit_button);
            }
        }
    }
</script>