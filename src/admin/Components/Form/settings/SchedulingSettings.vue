<template>
    <el-container>
        <el-main class="no_shadow">
            <div v-loading="fetching" class="edit_form_warpper">
                <div class="all_payforms_wrapper payform_section">
                    <div class="payform_section_header">
                        <h3 class="payform_section_title">
                            {{ $t('Form Scheduling & Restrictions') }}
                        </h3>
                        <div v-if="has_pro" class="payform_section_actions">
                            <el-button v-loading="saving" @click="saveSettings()" class="payform_action" size="small"
                                       type="primary">
                                {{ $t( 'Save Settings' ) }}
                            </el-button>
                        </div>
                    </div>
                    <div class="payform_section_body">

                        <div v-if="!has_pro" class="payform_pro_message">
                            <h3>Scheduling form is pro version feature.</h3>
                            <p>Please upgrade to pro to unlock this feature. WP Payform Pro comes with lots of feature which will increase the flexebility and conversion rate</p>
                            <a target="_blank" :href="pro_purchase_url">Upgrade to WP Payment Form Pro</a>
                        </div>

                        <form-restrictions :current_date_time="current_date_time" :data="settings" />
                    </div>
                </div>
            </div>
        </el-main>
    </el-container>
</template>

<script type="text/babel">
    import FormRestrictions from './_Restrictions';
    export default {
        name: 'form_schedule_restriction_settings',
        props: ['form_id'],
        components: {FormRestrictions},
        data() {
            return {
                settings: {
                    limitNumberOfEntries: {},
                    scheduleForm: {},
                    requireLogin: {}
                },
                fetching: false,
                saving: false,
                current_date_time: ''
            }
        },
        methods: {
            saveSettings() {
                this.saving = true;
                this.$post({
                    action: 'wpf_scheduling_endpoints',
                    route: 'update_settings',
                    form_id: this.form_id,
                    settings: this.settings
                })
                    .then(response => {
                        this.$message.success(response.data.message);
                    })
                    .fail((error) => {
                        this.$message.error(error.responseJSON.data.message);
                    })
                    .always(() => {
                        this.saving = false;
                    });
            },
            getSettings() {
                this.fetching = true;
                this.$get({
                    action: 'wpf_scheduling_endpoints',
                    route: 'get_settings',
                    form_id: this.form_id
                })
                    .then((response) => {
                        this.settings = response.data.scheduling_settings;
                        this.current_date_time = response.data.current_date_time
                    })
                    .fail(error => {
                        this.$message.error(error.responseJSON.data.message);
                    })
                    .always(() => {
                        this.fetching = false;
                    });
            }
        },
        mounted() {
            if(this.has_pro) {
                this.getSettings();
            }
            window.WPPayFormsBus.$emit('site_title', 'Scheduling Settings');
        }
    }
</script>