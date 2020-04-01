<template>
    <el-form label-width="205px" label-position="left" v-loading="loading" element-loading-text="Loading Settings...">
        <el-row class="setting_header">
            <el-col :md="12">
                <h2>Slack Integration</h2>
            </el-col>
            <el-col :md="12" class="action-buttons clearfix mb15">
                <el-button class="pull-right" size="medium" type="success" icon="el-icon-success" @click="save" :loading="saving"
                >{{ saving ? 'Saving': 'Save'}} Settings
                </el-button>
            </el-col>
        </el-row>
        <el-form-item label="Integrate Slack">
            <el-switch active-color="#13ce66" v-model="slack.enabled"></el-switch>
        </el-form-item>
        <el-form-item v-if="slack.enabled" style="margin-left: 17px;" label="Slack Title">
            <el-input placeholder="optional" v-model="slack.textTitle"></el-input>
        </el-form-item>

        <transition name="slide-down">
            <el-form-item v-if="slack.enabled" class="conditional-items">
                <div slot="label">
                    Webhook URL

                    <el-tooltip class="item" placement="bottom-start" effect="light">
                        <div slot="content">
                            <h3>Webhook URL</h3>

                            <p>
                                The <a href="https://api.slack.com/incoming-webhooks" target="_blank">slack webhook
                                URL</a> where Fluent Forms will send JSON payload.
                            </p>
                        </div>

                        <i class="el-icon-info el-text-info"></i>
                    </el-tooltip>
                </div>

                <el-input placeholder="https://hooks.slack.com/services/..." v-model="slack.webhook"></el-input>
            </el-form-item>
        </transition>

        <el-form-item>
            <el-button class="pull-right" size="medium" type="success" icon="el-icon-success" @click="save" :loading="saving">
                {{ saving ? 'Saving': 'Save'}} Settings
            </el-button>
        </el-form-item>
    </el-form>
</template>

<script>
    export default {
        name: "Slack",
        props: ['form_id'],
        data() {
            return {
                loading: false,
                saving: false,
                slack: {
                    enabled: false,
                    webhook: null,
                    textTitle:''
                },
                errors: new Errors
            }
        },
        methods: {
            fetch() {
                this.loading = true;

                let data = {
                    form_id: this.form_id,
                    meta_key: 'slack',
                };

                this.$ajax.get('getFormSettings', data)
                    .then(response => {
                        if (response.data.result[0]) {
                            this.slack = response.data.result[0].value;
                            this.slack.id = response.data.result[0].id;
                        }
                    })
                    .fail(e => {
                    })
                    .always(() => {
                        this.loading = false;
                    })
            },
            save() {
                this.saving = true;

                let data = {
                    form_id: this.form_id,
                    meta_key: 'slack',
                    value: JSON.stringify(this.slack),
                    id: this.slack.id
                };

                this.$ajax.post('saveFormSettings', data)
                    .then(response => {
                        this.slack.id = response.data.id;

                        this.$notify.success({
                            title: 'Success',
                            message: response.data.message,
                            offset: 30
                        });
                    })
                    .fail(error => {
                        this.errors.record(error.responseJSON.data.errors);
                    })
                    .always(() => {
                        this.saving = false;
                    });
            }
        },
        mounted() {
            this.fetch();
        },
        beforeCreate() {
            // ffSettingsEvents.$emit('change-title', 'Slack Settings');
        }
    }
</script>