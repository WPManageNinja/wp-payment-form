<template>
    <div v-if="notifications.length" class="wpf_email_notifications">
        <el-table :data="notifications">
            <el-table-column  type="index" width="50" />
            <el-table-column
                width="120"
                label="Status">
                <template slot-scope="scope">
                    <el-switch
                        v-model="scope.row.status"
                        @change="emitClick('saveNotification', scope.$index)"
                        active-color="#13ce66"
                        active-value="active"
                        inactive-value="disabled"
                    >
                    </el-switch>
                </template>
            </el-table-column>
            <el-table-column
                property="title"
                width="220"
                label="Title">
            </el-table-column>
            <el-table-column
                property="email_subject"
                label="Subject">
            </el-table-column>
            <el-table-column
                width="180"
                label="Sending Action">
                <template slot-scope="scope">
                    {{ humanActionName(scope.row.sending_action) }}
                </template>
            </el-table-column>
            <el-table-column
                width="160"
                label="">
                <template slot-scope="scope">
                    <el-button @click="emitClick('editNotification', scope.$index)" type="success" size="mini" icon="el-icon-edit"></el-button>
                    <el-button @click="emitClick('deleteNotification', scope.$index)" type="danger" size="mini" icon="el-icon-delete"></el-button>
                </template>
            </el-table-column>

        </el-table>
    </div>
    <div v-else>
        <h3 class="text-center">No email notifications configured yet.</h3>
    </div>
</template>
<script type="text/babel">
    export default {
        name: 'notifications_table',
        props: ['notifications'],
        methods: {
            emitClick(eventName, dataIndex) {
                this.$emit(eventName, dataIndex);
            },
            humanActionName(action) {
                let names = {
                    'wppayform/after_form_submission_complete' : 'After Form Submission',
                    'wppayform/form_payment_success' : 'After Payment Success'
                }
                if(names[action]) {
                    return names[action];
                }
                return action;
            }
        }
    }
</script>
