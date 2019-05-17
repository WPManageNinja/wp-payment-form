<template>
    <el-dialog top="40px" width="70%" :append-to-body="true"
               title="Start with a Pre-Defined Form or Create a Blank Form" :visible.sync="isVisible">
        <div class="pay_form_modal_body">

            <div class="demo_form_cards">
                <div @click="createFromTemplate(form_name)" v-for="(demo_form, form_name) in demo_forms"
                     class="demo_form_card">
                    <div class="form_card_body">
                        <img :src="demo_form.preview_image" title="Click to create this form"/>
                        <div class="demo_form_desc">
                            <div class="desc_info">
                                <i class="el-icon-circle-plus"></i>
                                <p v-html="demo_form.description"></p>
                            </div>
                        </div>
                    </div>
                    <div class="form_card_footer">
                        {{demo_form.label}} <span v-if="demo_form.is_pro && !has_pro">(PRO)</span>
                    </div>
                </div>
            </div>
        </div>
        <span slot="footer" class="dialog-footer">
                <el-button @click="isVisible = false">Cancel</el-button>
                <el-button type="primary" @click="createFromTemplate('blank_form')">Create a Blank Form</el-button>
            </span>
    </el-dialog>
</template>
<script type="text/babel">
    export default {
        name: 'CreateForm',
        props: ['modalVisible', 'demo_forms'],
        data() {
            return {
                form_title: 'Blank Form',
                template: 'blank_form',
                isVisible: this.modalVisible
            }
        },
        watch: {
            isVisible() {
                this.$emit('update:modalVisible', JSON.parse(this.isVisible))
            }
        },
        methods: {
            createForm() {
                this.submitting = true;
                // Send Request now
                this.$adminPost({
                    route: 'create_form',
                    post_title: this.form_title,
                    template: this.template
                })
                    .then(response => {
                        this.$message.success(response.data.message);
                        this.$router.push({name: 'edit_form', params: {form_id: response.data.form_id}})
                    })
                    .fail(error => {
                        this.$message({
                            message: error.responseJSON.data.message,
                            type: 'error'
                        });
                    })
                    .always(() => {
                        this.submitting = false;
                    });

            },
            createFromTemplate(templateName) {
                let form = this.demo_forms[templateName];
                if (form) {
                    if(form.is_pro && !this.has_pro) {
                        this.$notify.error('You need pro version to create this form');
                        return;
                    }
                    if (form.label) {
                        this.form_title = form.label;
                    }
                    this.template = templateName;
                }
                this.createForm();
            }
        }
    }
</script>
