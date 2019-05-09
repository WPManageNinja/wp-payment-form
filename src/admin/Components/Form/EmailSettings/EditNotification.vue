<template>
    <div class="notification_edit">
        <el-form v-if="editingNotificationIndex != null" ref="notificationForm" class="payform_hide_valdate_messages" :show-message="showErrorMessage" :rules="rules" label-position="top" :model="form" label-width="120px">
            <el-form-item prop="title" label="Notification Title">
                <el-input size="small" placeholder="Notification Title" v-model="form.title"></el-input>
            </el-form-item>
            <div class="payform_form_item_wrapper">
                <el-form-item prop="email_to" class="payform_item_half" label="Email To">
                    <el-input size="small" placeholder="Email To" v-model="form.email_to">
                        <popover
                            @command="(code) => { form.email_to += code }"
                            slot="suffix" :data="merge_tags"
                            btnType="text"
                            buttonText='<i class="el-icon-menu"></i>'>
                        </popover>
                    </el-input>
                </el-form-item>
                <el-form-item class="payform_item_half" label="Reply To">
                    <el-input size="small" placeholder="Reply To" v-model="form.reply_to">
                        <popover
                            @command="(code) => { form.reply_to += code }"
                            slot="suffix" :data="merge_tags"
                            btnType="text"
                            buttonText='<i class="el-icon-menu"></i>'>
                        </popover>
                    </el-input>
                </el-form-item>
            </div>
            <el-form-item prop="email_subject" label="Email Subject">
                <el-input size="small" placeholder="Email Subject" v-model="form.email_subject">
                    <popover
                        @command="(code) => { form.email_subject += code }"
                        slot="suffix" :data="merge_tags"
                        btnType="text"
                        buttonText='<i class="el-icon-menu"></i>'>
                    </popover>
                </el-input>
            </el-form-item>
            <el-form-item prop="email_body" label="Email Body">
                <wp-editor
                    :editor_id="'wp_email_editor_'+editingNotificationIndex"
                    v-model="form.email_body"
                    :editorShortcodes="merge_tags"
                />
            </el-form-item>

            <el-form-item prop="sending_action" label="When to send this email">
                <p>Please Select when the email will be sent</p>
                <el-radio-group v-model="form.sending_action">
                    <el-radio v-for="sending_hook in notification_actions" :key="sending_hook.hook_name"
                              :label="sending_hook.hook_name">
                        {{sending_hook.hook_title}}
                    </el-radio>
                </el-radio-group>
            </el-form-item>
            <el-collapse v-model="showAdvanced" accordion>
                <el-collapse-item title="Advanced Settings" name="advanced_settings">
                    <div class="payform_form_item_wrapper">
                        <el-form-item class="payform_item_half" label="From Name">
                            <el-input size="small" placeholder="From Name" v-model="form.from_name">
                                <popover
                                    @command="(code) => { form.from_name += code }"
                                    slot="suffix" :data="merge_tags"
                                    btnType="text"
                                    buttonText='<i class="el-icon-menu"></i>'>
                                </popover>
                            </el-input>
                        </el-form-item>
                        <el-form-item class="payform_item_half" label="From Email">
                            <el-input size="small" placeholder="From Email" v-model="form.from_email">
                                <popover
                                    @command="(code) => { form.from_email += code }"
                                    slot="suffix" :data="merge_tags"
                                    btnType="text"
                                    buttonText='<i class="el-icon-menu"></i>'>
                                </popover>
                            </el-input>
                        </el-form-item>
                    </div>
                    <div class="payform_form_item_wrapper">
                        <el-form-item class="payform_item_half" label="CC">
                            <el-input size="small" placeholder="CC" v-model="form.cc_to">
                                <popover
                                    @command="(code) => { form.cc_to += code }"
                                    slot="suffix" :data="merge_tags"
                                    btnType="text"
                                    buttonText='<i class="el-icon-menu"></i>'>
                                </popover>
                            </el-input>
                        </el-form-item>
                        <el-form-item class="payform_item_half" label="BCC">
                            <el-input size="small" placeholder="BCC" v-model="form.bcc_to">
                                <popover
                                    @command="(code) => { form.bcc_to += code }"
                                    slot="suffix" :data="merge_tags"
                                    btnType="text"
                                    buttonText='<i class="el-icon-menu"></i>'>
                                </popover>
                            </el-input>
                        </el-form-item>
                    </div>
                </el-collapse-item>
            </el-collapse>
            <el-form-item class="submit_btn_right">
                <el-button size="small" type="primary" @click="update('notificationForm')">Update</el-button>
            </el-form-item>

        </el-form>
    </div>
</template>

<script type="text/babel">
    import wpEditor from '../../Common/_wp_editor';
    import popover from '../../Common/input-popover-dropdown.vue'

    export default {
        name: 'editNotification',
        components: {
            wpEditor,
            popover
        },
        props: ['notification', 'editingNotificationIndex', 'merge_tags', 'notification_actions'],
        data() {
            return {
                form: {},
                showAdvanced: '',
                rules: {
                    title: [
                        {
                            required: true, message: 'Please Provide Notification Title',
                        }
                    ],
                    email_to: [
                        {
                            required: true, message: 'Please Provide Email To', trigger: 'change'
                        }
                    ],
                    email_subject: [
                        {
                            required: true, message: 'Please Provide Email Subject',
                        }
                    ],
                    email_body: [
                        {
                            required: true, message: 'Please Provide Email Body',
                        }
                    ],
                    from_email: [
                        {
                            required: true, message: 'Please Provide Email Body'
                        }
                    ],
                    sending_action: [
                        {
                            required: true, message: 'When to send field is required'
                        }
                    ]
                },
                showErrorMessage: false
            }
        },
        watch: {
            editingNotificationIndex() {
                if (this.editingNotificationIndex != null) {
                    this.showErrorMessage = false;
                    this.form = JSON.parse(JSON.stringify(this.notification));
                }
            }
        },
        methods: {
            update(formName) {
                // validate first please
                this.showErrorMessage = true;
                this.$refs[formName].validate((valid) => {
                    if (valid) {
                        this.$emit('update:notification', JSON.parse(JSON.stringify(this.form)));
                        this.$emit('saveNotifications');
                    } else {
                        this.$notify.error('Please provide all required fields');
                        return false;
                    }
                });
            },
            handleInsert() {
                console.log(arguments);
            },
            getFormErrors() {

            }
        },
        mounted() {
            this.showErrorMessage = false;
            this.form = JSON.parse(JSON.stringify(this.notification));
        }
    }
</script>
