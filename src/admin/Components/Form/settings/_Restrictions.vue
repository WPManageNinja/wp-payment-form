<template>
    <el-form ref="form-bottom" label-width="220px" label-position="left">
        <!--Limit Number of Entries-->
        <el-form-item>
            <div slot="label">
                Maximum Number of Entries
                <el-tooltip class="item" placement="bottom-start" effect="light">
                    <div slot="content">
                        <h3>Maximum Number of Entries</h3>
                        <p>
                            Enter a number in the input box below to limit <br>
                            the number of entries allowed for this form. The <br>
                            form will become inactive when that number is reached.
                        </p>
                    </div>
                    <i class="el-icon-info el-text-info"></i>
                </el-tooltip>
            </div>
            <el-switch active-value="yes" inactive-value="no" v-model="form.limitNumberOfEntries.status"></el-switch>
        </el-form-item>

        <!--Additional fields when limit number of entries enabled-->
        <transition name="slide-down">
            <div v-if="form.limitNumberOfEntries.status == 'yes'" class="conditional-items">
                <el-form-item label="Maximum Entries">
                    <el-col :md="8">
                        <el-input-number :min="0"
                                         v-model="form.limitNumberOfEntries.number_of_entries"
                        ></el-input-number>
                    </el-col>

                    <el-col :md="8">
                        For <el-select v-model="form.limitNumberOfEntries.limit_type">
                            <el-option v-for="(label, period) in entryPeriodOptions" :key="period"
                                       :label="label" :value="period"
                            ></el-option>
                        </el-select>
                    </el-col>
                    <el-col :md="8">
                        <el-select multiple v-model="form.limitNumberOfEntries.limit_payment_statuses">
                            <el-option v-for="(label, label_value) in paymentStatuses" :key="label_value"
                                   :label="label" :value="label_value"
                        ></el-option>
                    </el-select>
                    </el-col>
                </el-form-item>
                <el-form-item class="label-lh-1-5" label="Message Shown on Reaching Max. Entries" key="limit-reached-msg">
                    <el-input v-model="form.limitNumberOfEntries.limit_exceeds_message" type="textarea"></el-input>
                </el-form-item>
            </div>
        </transition>

        <!--Schedule Form-->
        <el-form-item>
            <div slot="label">
                Form Scheduling
                <el-tooltip class="item" placement="bottom-start" effect="light">
                    <div slot="content">
                        <h3>Form Scheduling</h3>
                        <p>
                            Schedule a time period the form is active.
                        </p>
                    </div>

                    <i class="el-icon-info el-text-info"></i>
                </el-tooltip>
            </div>
            <el-switch  active-value="yes" inactive-value="no" v-model="form.scheduleForm.status"></el-switch>
        </el-form-item>

        <!--Additional fields when form sheduling enabled-->
        <transition name="slide-down">
            <div v-if="form.scheduleForm.status == 'yes'" class="conditional-items">
                <p>Scheduling will be performed based on your server time. Current server date and time: <code>{{ current_date_time }}</code></p>
                <el-row :gutter="30">
                    <el-col :md="12">
                        <el-form-item label="Submission Starts Date">
                            <el-date-picker
                                    class="el-fluid"
                                    style="width: 100%;"
                                    value-format="yyyy-MM-dd HH:mm:ss"
                                    v-model="form.scheduleForm.start_date"
                                    type="datetime"
                                    placeholder="Select date and time"
                                    :picker-options="datePickerOptions">
                            </el-date-picker>
                        </el-form-item>
                    </el-col>
                    <el-form labelWidth="160px">
                    <el-col :md="12">
                        <el-form-item label="Submission Ends Date">
                            <el-date-picker
                                class="el-fluid"
                                style="width: 100%;"
                                value-format="yyyy-MM-dd HH:mm:ss"
                                v-model="form.scheduleForm.end_date"
                                type="datetime"
                                placeholder="Select date and time"
                                :picker-options="datePickerOptions">
                            </el-date-picker>
                        </el-form-item>
                    </el-col>
                    </el-form>
                </el-row>

                <el-form-item label="Form Waiting Message">
                    <el-input v-model="form.scheduleForm.before_start_message" type="textarea"></el-input>
                </el-form-item>

                <el-form-item label="Form Expired Message">
                    <el-input v-model="form.scheduleForm.expire_message" type="textarea"></el-input>
                </el-form-item>
            </div>
        </transition>

        <!--Require user to be logged in-->
        <h4>Login Requirement Settings</h4>
        <el-form-item>
            <div slot="label">
                Require user to be logged in

                <el-tooltip class="item" placement="bottom-start" effect="light">
                    <div slot="content">
                        <h3>Require user to be logged in</h3>

                        <p>
                            Check this option to require a user to be <br>
                            logged in to view this form.
                        </p>
                    </div>

                    <i class="el-icon-info el-text-info"></i>
                </el-tooltip>
            </div>

            <el-switch  active-value="yes" inactive-value="no" v-model="form.requireLogin.status"></el-switch>
        </el-form-item>

        <!--Additional fields when user logged in is enabled-->
        <transition name="slide-down">
            <div v-if="form.requireLogin.status == 'yes'" class="conditional-items">
                <el-form-item>
                    <template slot="label">
                        Require Login Message
                        <el-tooltip class="item" placement="bottom-start" effect="light">
                            <div slot="content">
                                <h3>Require Login Message</h3>
                                <p>
                                    Enter a message to be displayed to users who <br>
                                    are not logged in (shortcodes and HTML are supported).
                                </p>
                            </div>
                            <i class="el-icon-info el-text-info"></i>
                        </el-tooltip>
                    </template>
                    <el-input type="textarea" v-model="form.requireLogin.message"></el-input>
                </el-form-item>
            </div>
        </transition>

        <template v-if="hasAnyRestriction">
            <h4>Form Display option when restriction met</h4>
            <el-form-item>
                <div slot="label">
                    Restricted Form Status
                    <el-tooltip class="item" placement="bottom-start" effect="light">
                        <div slot="content">
                            <h3>Restricted Form Status</h3>
                            <p>
                                You can show / hide the form inputs if any restriction mate
                            </p>
                        </div>
                        <i class="el-icon-info el-text-info"></i>
                    </el-tooltip>
                </div>
                <el-radio-group v-model="form.restriction_applied_type">
                    <el-radio label="hide_form">Hide Form</el-radio>
                    <el-radio label="hide_submit_button">Hide Submit Button</el-radio>
                    <el-radio label="validation_failed">Make validation failed after submit</el-radio>
                </el-radio-group>
            </el-form-item>
        </template>
    </el-form>
</template>

<script>
    export default {
        name: 'FormRestrictions',
        props: {
            data: {
                required: true
            },
            current_date_time: {
                required: false,
                type: String
            }
        },
        data() {
            return {
                entryPeriodOptions: {
                    total: 'Total Entries',
                    day: 'Per Day',
                    week: 'Per Week',
                    month: 'Per Month',
                    year: 'Per Year',
                },
                paymentStatuses: window.wpPayFormsAdmin.paymentStatuses,
                datePickerOptions: {
                    shortcuts: [
                        {
                            text: 'Today',
                            onClick(picker) {
                                picker.$emit('pick', new Date());
                            }
                        },
                        {
                            text: 'Tomorrow',
                            onClick(picker) {
                                const date = new Date();
                                date.setTime(date.getTime() + 3600 * 1000 * 24);
                                picker.$emit('pick', date);
                            }
                        },
                        {
                            text: 'Yesterday',
                            onClick(picker) {
                                const date = new Date();
                                date.setTime(date.getTime() - 3600 * 1000 * 24);
                                picker.$emit('pick', date);
                            }
                        },
                        {
                            text: 'A week after',
                            onClick(picker) {
                                const date = new Date();
                                date.setTime(date.getTime() + 3600 * 1000 * 24 * 7);
                                picker.$emit('pick', date);
                            }
                        }
                    ]
                }
            }
        },
        computed: {
            form() {
                return this.data;
            },
            hasAnyRestriction() {
                if(
                    this.data.limitNumberOfEntries.status == 'yes' ||
                    this.data.scheduleForm.status == 'yes' ||
                    this.data.requireLogin.status == 'yes'
                ) {
                    return true;
                }
                return false;
            }
        }
    }
</script>
