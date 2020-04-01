<template>
    <div  v-loading="loading">
        <el-row class="setting_header">
            <el-col :md="12"><h2>WebHooks Integration</h2></el-col>

            <!--Add Feed-->
            <el-col :md="12" class="action-buttons mb15 clearfix">
                <el-button v-if="!show_edit" @click="add" type="primary" class="pull-right"
                    size="small" icon="el-icon-plus">Add New</el-button>

                <el-button v-if="show_edit" @click="backToHome()" type="primary" class="pull-right"
                    size="small" icon="el-icon-arrow-left">Back</el-button>
            </el-col>
        </el-row>

        <!-- WebHook Feeds Table: 1 -->
        <el-table v-if="!show_edit" :data="tableData" stripe class="el-fluid">
            <template slot="empty">
                You don't have any feeds configured. Let's go
                <a href="#" @click.prevent="add">create one!</a>
            </template>

            <el-table-column width="100">
                <template slot-scope="scope">
                    <el-switch active-color="#13ce66" @change="handleActive(scope.row)" v-model="scope.row.formattedValue.enabled"></el-switch>
                </template>
            </el-table-column>

            <el-table-column
                prop="formattedValue.name"
                label="Name">
            </el-table-column>

            <el-table-column
                prop="formattedValue.request_url"
                label="WebHook URL">
            </el-table-column>

            <el-table-column width="160" label="Actions" class-name="action-buttons">
                <template slot-scope="scope">
                    <el-button
                        @click="edit(scope.$index)"
                        type="primary"
                        icon="el-icon-setting"
                        size="mini">
                    </el-button>
                    <remove @on-confirm="remove(scope.row.id)"></remove>
                </template>
            </el-table-column>
        </el-table>

        <!-- WebHook Feed Editor -->
        <editor
            v-if="show_edit"
            :fields="inputs"
            :form_id="form.id"
            :has_pro="has_pro"
            :edit_item="editing_item"
            :selected_id="selected_id"
            :ajax_actions="ajaxActions"
            :setSelectedId="setSelectedId"
            :selected_index="selectedIndex"
            :request_headers="request_headers"
            :editor_Shortcodes="editorShortcodes"
        ></editor>
    </div>
</template>

<script>
    // import remove from '../../confirmRemove.vue';
    // import inputPopover from '../../input-popover.vue';
    // import Editor from './Editor.vue';
    
    // export default {
    //     name: 'WebHook',
    //     props: ['form', 'inputs', 'has_pro', 'editorShortcodes'],
    //     components: {
    //         remove,
    //         Editor,
    //         inputPopover
    //     },
    //     data() {
    //         return {
    //             loading: true,
    //             configure_url: '',
    //             editing_item: null,
    //             selected_id: 0,
    //             selectedIndex: null,
    //             show_edit: false,
    //             integrations: [],
    //             webHook_lists: [],
    //             request_headers: [],
    //             webHookCustomFields: null,
    //             errors: new Errors,
    //             ajaxActions: {
    //                 saveFeed: 'fluentform-save-webhook'
    //             }
    //         }
    //     },
    //     methods: {
    //         setSelectedId(id) {
    //             this.selected_id = id;
    //         },
    //         backToHome() {
    //             this.getFeeds(true);
    //             this.selected_id = 0;
    //             this.selectedIndex = 0;
    //             this.show_edit = false;
    //         },
    //         add() {
    //             this.selectedIndex = this.integrations.length;
    //             this.selected_id = 0;
    //             this.editing_item = false;
    //             this.show_edit = true;
    //         },
    //         edit(index) {
    //             let integration = this.integrations[index];
    //             this.selectedIndex = 0;
    //             this.selected_id = integration.id;
    //             this.editing_item =  integration.formattedValue;
    //             this.show_edit = true;
    //         },
    //         discard() {
    //             this.selected = null;
    //             this.selectedIndex = null;
    //             this.errors.clear();
    //         },
    //         handleActive(row) {
    //             let data = {
    //                 form_id: this.form.id,
    //                 notification_id: row.id,
    //                 action: this.ajaxActions.saveFeed,
    //                 notification: JSON.stringify(row.formattedValue)
    //             };

    //             jQuery.post(ajaxurl, data)
    //                 .then(response => {
    //                     this.$notify.success({
    //                         offset: 30,
    //                         title: 'Success!',
    //                         message: response.data.message
    //                     });
    //                 })
    //                 .fail(error => {
    //                     this.$notify.error({
    //                         offset: 30,
    //                         title: 'Success!',
    //                         message: error.responseJSON.data.message
    //                     });
    //                 });
    //         },
    //         remove(id) {
    //             let data = { 
    //                 action: 'fluentform-delete-webhook',
    //                 id: id, 
    //                 form_id: this.form.id 
    //             };

    //             jQuery.post(ajaxurl, data )
    //             .then(response => {
    //                 this.integrations = response.data.integrations;
    //                 this.$notify.success({
    //                     offset: 30,
    //                     title: 'Success!',
    //                     message: response.data.message
    //                 });
    //             })
    //             .fail(e => console.log(e));
    //         },
    //         getFeeds(onlyFeeds = null) {
    //             let data = {
    //                 form_id: this.form.id,
    //                 action: 'fluentform-get-webhooks'
    //             };
                
    //             jQuery.get(ajaxurl, data)
    //             .then(response => {
    //                 this.integrations = response.data.integrations;
    //                 this.request_headers = response.data.request_headers;
    //                 this.request_headers.push({
    //                     'label': 'Add Custom Header',
    //                     'value': '__webhook_custom_header__'
    //                 });
    //             })
    //             .fail(e => console.log(e))
    //             .always(r => this.loading = false);
    //         }
    //     },
    //     computed: {
    //         tableData() {
    //           return this.integrations;
    //         }
    //     },
    //     beforeMount() {
    //         this.getFeeds();
    //     },
    //     beforeCreate() {
    //         // ffSettingsEvents.$emit('change-title', 'WebHook Settings');
    //     }
    // }
</script>
