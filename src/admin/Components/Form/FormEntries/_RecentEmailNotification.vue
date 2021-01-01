<template>
    <div class="wpf_email_resend_inline">
        <el-button v-if="element_type == 'button'"
            icon="el-icon-message"
            @click="getNotifications"
            type="info"
            size="mini">{{btn_text}}
        </el-button>
        <el-dialog
            title="Choose Email Notification"
            top="42px"
            @close="resetData()"
            :append-to-body="true"
            :visible.sync="dialogVisible"
            width="60%">
            <template v-if="has_pro">
                <el-form label-width="120px" ref="form" :model="form" label-position="left">
                    <el-form-item label="Notification">
                        <el-select v-loading="fetching" size="small" placeholder="Select Notification"
                                   v-model="form.notification_id">
                            <el-option v-for="notification in notifications" :value="notification.id"
                                       :label="notification.name" :key="notification.id"></el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="Send To">
                        <el-radio-group size="small" v-model="form.send_to_type">
                            <el-radio-button label="default">Default Recipient</el-radio-button>
                            <el-radio-button label="custom">Custom Recipient</el-radio-button>
                        </el-radio-group>
                    </el-form-item>
                    <template v-if="form.send_to_type == 'custom'">
                        <el-form-item label="Recipient">
                            <el-input v-model="form.send_to_custom_email" size="small"
                                      placeholder="Please Type Recipient Email Address"/>
                        </el-form-item>
                    </template>
                </el-form>
                <span slot="footer" class="dialog-footer">
                    <el-button @click="dialogVisible = false" size="mini">Cancel</el-button>
                    <el-button v-loading="sending" :disabled="!isActive" size="mini" type="primary" @click="send()">Resend this notification</el-button>
                </span>
            </template>
            <div style="text-align: center" v-else>
                <h3>This feature is available on pro version of WPPayforms.</h3>
                <a target="_blank" rel="noopener" :href="upgrade_url" class="el-button el-button--danger">
                    Buy Pro Now
                </a>
            </div>
        </el-dialog>
    </div>
</template>
<script type="text/babel">
    export default {
        name: 'resentEmailNotification',
        props: {
            entry_id: {
                default() {
                    return '';
                }
            },
            form_id: {
                required: true
            },
            entry_ids: {
                default() {
                    return []
                }
            },
            element_type: {
                default() {
                    return 'button'
                }
            },
            btn_text: {
                default() {
                    return 'Resend Email Notification'
                }
            }
        },
        data() {
            return {
                has_pro: window.wpPayFormsAdmin.has_pro,
                dialogVisible: false,
                fetching: true,
                sending: false,
                form: {
                    notification_id: '',
                    send_to_type: 'default',
                    send_to_custom_email: ''
                },
                notifications: [],
                upgrade_url: 'https://wpmanageninja.com/downloads/wppayform-pro-wordpress-payments-form-builder/'
            }
        },
        computed: {
            isActive() {
                if (this.form.send_to_type == 'custom') {
                    return this.form.notification_id !== '' && this.form.send_to_custom_email;
                }
                return this.form.notification_id !== '';
            }
        },
        methods: {
            send() {
                this.sending = true;
                const query = {
                    action: 'wppayform_forms_admin_ajax',
                    route: 'resend_notifications',
                    form_id: parseInt(this.form_id),
                    submission_id: parseInt(this.entry_id),
                    info: this.form
                }
                this.$get(query)
                    .then(res => {
                        if(res.success) {
                            this.$message.success({
                                message: res.data,
                                offset: 40,
                                duration: 5000
                            })
                        } else {
                            this.$message.error({
                                message: res.data,
                                offset: 40,
                                duration: 5000
                            })
                        }
                        this.sending = false;
                    })
                    .fail((e)=>{
                        this.$message.error(e.responseJSON.data.err);
                        this.sending = false;
                    })
                    .always(() => {
                        this.sending = false;
                    });
            },
            resetData() {
                this.form = {
                    notification_id: '',
                    send_to_type: 'default',
                    send_to_custom_email: ''
                }
            },
            getNotifications() {
                this.fetching = true;
                const query = {
                    action: 'wppayform_forms_admin_ajax',
                    route: 'get_notifications_only',
                    form_id: parseInt(this.form_id)
                }
                this.$get(query)
                    .then(response => {
                        this.notifications = response.data.notifications;
                        this.fetching = false;
                    })
                    .always(() => {
                        this.fetching = false;
                    });
                this.dialogVisible = true;

            }
        }
    }
</script>

<style lang="scss">
    .wpf_email_resend_inline {
        display: inline-block;
    }
</style>
