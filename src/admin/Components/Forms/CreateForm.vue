<template>
    <el-dialog title="Create a Payment Form" :visible.sync="isVisible">
            <div class="pay_form_modal_body">
                <el-form ref="payForm" :model="payForm" label-width="120px">
                    <el-form-item label="Form Title">
                        <el-input placeholder="Form Title" v-model="payForm.postTitle"></el-input>
                    </el-form-item>
                </el-form>
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button @click="isVisible = false">Cancel</el-button>
                <el-button type="primary" @click="createForm">Continue</el-button>
            </span>
    </el-dialog>
</template>
<script type="text/babel">
    export default {
        name: 'CreateForm',
        props: ['modalVisible'],
        data() {
            return {
                payForm: {
                    postTitle: ''
                },
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
                // Validate Form
                if(!this.payForm.postTitle) {
                    this.$message({
                        message: 'Please Provide a title',
                        type: 'error'
                    });
                    return;
                }

                this.submitting = true;
                // Send Request now
                this.$adminPost({
                    route: 'create_form',
                    post_title: this.payForm.postTitle
                })
                    .then(response => {
                        console.log(response);
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

            }
        }
    }
</script>