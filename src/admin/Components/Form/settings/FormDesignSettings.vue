<template>
    <el-container>
        <el-main class="no_shadow">
            <div v-loading="fetching" class="edit_form_warpper">
                <div class="all_payforms_wrapper payform_section">
                    <div class="payform_section_header">
                        <h3 class="payform_section_title">
                            {{ $t('Form Design Settings') }}
                        </h3>
                        <div class="payform_section_actions">
                            <el-button v-loading="saving" @click="saveSettings()" class="payform_action" size="small"
                                       type="primary">
                                {{ $t( 'Save Design Settings' ) }}
                            </el-button>
                        </div>
                    </div>
                    <div class="payform_section_body">
                        <el-form ref="layout_settings" :label-position="labelPosition" :model="layout_settings" label-width="220px">
                            <div class="wpf_settings_section">
                                <div class="sub_section_header">
                                    <h3>Form Layout</h3>
                                </div>
                                <div class="sub_section_body">
                                    <!--Label placement-->
                                    <el-form-item>
                                        <template slot="label">
                                            Label Alignment
                                            <el-tooltip class="item" placement="bottom-start" effect="light">
                                                <div slot="content">
                                                    <h3>Form Label Placement</h3>

                                                    <p>
                                                        Select the default label placement. Labels can be <br>
                                                        top aligned above a field, left aligned to the <br>
                                                        left of a field, or right aligned to the right of a field.
                                                    </p>
                                                </div>

                                                <i class="el-icon-info el-text-info"></i>
                                            </el-tooltip>
                                        </template>

                                        <el-radio v-for="(labelOption, optionName) in labelPlacementOptions"
                                                  v-model="layout_settings.labelPlacement" :label="optionName"
                                                  :key="optionName" border>
                                            {{ labelOption }}
                                        </el-radio>
                                    </el-form-item>

                                    <!--Required asterisk mark position -->
                                    <el-form-item>
                                        <template slot="label">
                                            Asterisk Position

                                            <el-tooltip class="item" placement="bottom-start" effect="light">
                                                <div slot="content">
                                                    <h3>Required Asterisk Position</h3>

                                                    <p>
                                                        The asterisk marker position for the required elements
                                                    </p>
                                                </div>

                                                <i class="el-icon-info el-text-info"></i>
                                            </el-tooltip>
                                        </template>

                                        <el-radio v-for="(option, optionName) in asteriskPlacementMock"
                                                  v-model="layout_settings.asteriskPlacement" :label="optionName"
                                                  :key="optionName" border>{{ option }}
                                        </el-radio>
                                    </el-form-item>

                                    <!--Submit Button Position -->
                                    <el-form-item>
                                        <template slot="label">
                                            Submit Button Position

                                            <el-tooltip class="item" placement="bottom-start" effect="light">
                                                <div slot="content">
                                                    <h3>Submit Button Position</h3>
                                                    <p>
                                                        Choose where you want to show your submit button
                                                    </p>
                                                </div>
                                                <i class="el-icon-info el-text-info"></i>
                                            </el-tooltip>
                                        </template>
                                        <el-radio-group v-model="layout_settings.submit_button_position">
                                            <el-radio label="left" border>Left</el-radio>
                                            <el-radio label="center" border>Center</el-radio>
                                            <el-radio label="right" border>Right</el-radio>
                                        </el-radio-group>
                                    </el-form-item>

                                </div>
                            </div>

                            <div class="wpf_settings_section">
                                <div class="sub_section_header">
                                    <h3>Form Element Stylings</h3>
                                </div>
                                <div class="sub_section_body">
                                    <!--Label placement-->
                                    <el-form-item>
                                        <template slot="label">
                                            Form Labels Font Weight
                                            <el-tooltip class="item" placement="bottom-start" effect="light">
                                                <div slot="content">
                                                    <h3>Form Labels Font Weight</h3>
                                                    <p>
                                                        Select how you want to show the form labels. To make the form labels as bold check this option.
                                                    </p>
                                                </div>

                                                <i class="el-icon-info el-text-info"></i>
                                            </el-tooltip>
                                        </template>
                                        <el-checkbox true-label="yes" false-label="no" v-model="layout_settings.extra_styles.wpf_bold_labels">Make Labels as bold</el-checkbox>
                                    </el-form-item>

                                    <!--Input Styling Option-->
                                    <el-form-item>
                                        <template slot="label">
                                            Input Item Styles
                                            <el-tooltip class="item" placement="bottom-start" effect="light">
                                                <div slot="content">
                                                    <h3>Input Item Styles</h3>
                                                    <p>
                                                        Check this if you want default input stylings offered wp payment form
                                                    </p>
                                                </div>

                                                <i class="el-icon-info el-text-info"></i>
                                            </el-tooltip>
                                        </template>
                                        <el-checkbox true-label="yes" false-label="no" v-model="layout_settings.extra_styles.wpf_default_form_styles">Enable Default form styles</el-checkbox>
                                    </el-form-item>

                                </div>
                            </div>
                        </el-form>
                    </div>
                </div>
            </div>
        </el-main>
    </el-container>
</template>

<script type="text/babel">
    export default {
        name: 'form_design_settings',
        props: ['form_id'],
        data() {
            return {
                layout_settings: {
                    extra_styles: {}
                },
                labelPosition: 'right',
                labelPlacementOptions: {
                    'top': 'Top',
                    'left': 'Left',
                    'right': 'Right'
                },
                asteriskPlacementMock: {
                    'none': 'None',
                    'left': 'Left to Label',
                    'right': 'Right to Label'
                },
                fetching: false,
                saving: false
            }
        },
        methods: {
            saveSettings() {
                this.saving = true;
                this.$adminPost({
                    route: 'update_design_settings',
                    form_id: this.form_id,
                    layout_settings: this.layout_settings
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
                this.$adminGet({
                    route: 'get_design_settings',
                    form_id: this.form_id
                })
                    .then((response) => {
                        this.layout_settings = response.data.layout_settings;
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
            this.getSettings();
            window.WPPayFormsBus.$emit('site_title', 'Design Settings');
            if(window.outerWidth < 500) {
                this.labelPosition = "top";
            }
        }
    }
</script>