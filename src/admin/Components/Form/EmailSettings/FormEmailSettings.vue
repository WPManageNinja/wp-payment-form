<template>
    <el-container>
        <el-main>
            <div class="edit_form_warpper">
                <div class="all_payforms_wrapper payform_section">
                    <div class="payform_section_header">
                        <h3 class="payform_section_title">
                            {{title}}
                        </h3>
                        <div v-show="has_pro" class="payform_section_actions">
                            <el-button v-if="editingNotificationIndex == null" @click="addNotidication()"
                                       class="payform_action" size="small" type="primary">
                                {{ $t( 'Add New Notification' ) }}
                            </el-button>
                            <el-button @click="editingNotificationIndex = null" type="text" size="small" v-else>
                                Back
                            </el-button>
                        </div>
                    </div>
                    <template v-if="has_pro">
                        <div v-loading="loading" id="email_notifications" class="payform_section_body">
                            <notification-table v-if="editingNotificationIndex === null"
                                                @editNotification="editNotification"
                                                @deleteNotification="deleteNotification"
                                                @saveNotification="saveNotifications"
                                                :notifications="notifications"/>
                            <el-container class="email_notification_editing" v-else>
                                <el-aside width="200px">
                                    <el-menu background-color="#545c64"
                                             text-color="#fff"
                                             :default-active="'index_'+editingNotificationIndex"
                                             active-text-color="#ffd04b"
                                    >
                                        <el-menu-item
                                            v-for="(notification,notificationIndex) in notifications"
                                            :key="notificationIndex"
                                            @click="editNotification(notificationIndex)"
                                            :index="'index_'+notificationIndex">
                                            <span>{{  notification.title }}</span>
                                        </el-menu-item>
                                    </el-menu>
                                </el-aside>
                                <el-main>
                                    <edit-notification
                                        :notification_actions="notification_actions"
                                        @saveNotifications="saveNotifications"
                                        :editingNotificationIndex="editingNotificationIndex"
                                        :notification.sync="notifications[editingNotificationIndex]"
                                        :merge_tags="merge_tags"
                                    />
                                </el-main>
                            </el-container>
                        </div>
                    </template>
                    <template v-else>
                        <div class="payform_section_body payform_upgrade_wrapper">
                            <div class="payform_upgrade_section">
                                <h1><i class="el-icon-lock"></i></h1>
                                <h3>Send automatic email when your customers submit the form. You can send email to your customers as well as to yourself.</h3>
                                <a target="_blank" :href="pro_purchase_url" class="el-button el-button--primary">Upgrade To Pro version</a>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </el-main>
    </el-container>
</template>
<script type="text/babel">
    import NotificationTable from './Notifications';
    import EditNotification from './EditNotification'

    export default {
        name: 'FormEmailSettings',
        components: {
            NotificationTable,
            EditNotification
        },
        data() {
            return {
                form_id: this.$route.params.form_id,
                notifications: [],
                loading: false,
                merge_tags: [],
                notification_actions: [],
                editingNotificationIndex: null
            }
        },
        computed: {
            title() {
                let index = this.editingNotificationIndex;
                if (index != null && this.notifications.length) {
                    let serial = index + 1;
                    return 'Editing: ' + this.notifications[this.editingNotificationIndex].title + ' (#' + serial + ')';
                }
                return 'Email Notifications';
            }
        },
        methods: {
            getEmailNotifications() {
                this.loading = true;
                this.$adminGet({
                    route: 'get_email_notifications',
                    form_id: this.form_id
                })
                    .then(response => {
                        this.notifications = response.data.notifications;
                        this.merge_tags = Object.values(response.data.merge_tags);
                        this.notification_actions = response.data.notification_actions;
                    })
                    .fail(error => {

                    })
                    .always(() => {
                        this.loading = false;
                    });
            },
            addNotidication() {
                this.notifications.push({
                    title: 'Email Notification',
                    email_to: '',
                    reply_to: '',
                    email_subject: 'PayForm Submission',
                    email_body: '',
                    from_name: '',
                    from_email: '',
                    format: 'html',
                    email_template: 'default',
                    cc_to: '',
                    bcc_to: '',
                    conditions: '',
                    sending_action: '',
                    status: 'active'
                });
                this.editingNotificationIndex = this.notifications.length - 1;
            },
            editNotification(notificationIndex) {
                this.editingNotificationIndex = null;
                this.$nextTick(() => {
                    this.editingNotificationIndex = notificationIndex;
                });
            },
            deleteNotification(notificationIndex) {
                this.notifications.splice(notificationIndex, 1);
                this.saveNotifications();
            },
            saveNotifications() {
                this.saving = true;
                this.$adminPost({
                    route: 'save_email_notifications',
                    form_id: this.form_id,
                    notifications: this.notifications
                })
                    .then(response => {
                        this.$notify.success(response.data.message);
                        // this.notifications = response.data.notifications;
                    })
                    .fail(error => {

                    })
                    .always(() => {
                        this.saving = false;
                    });
            }
        },
        mounted() {
            if (this.has_pro) {
                this.getEmailNotifications();
            }
        }
    }
</script>
