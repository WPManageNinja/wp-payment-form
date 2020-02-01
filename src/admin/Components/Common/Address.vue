<template>
    <div class="address_fields_wrapper">
        <p><strong>Address Fields:</strong></p>
        <el-table :data="data_fields[0]" border style="width: 100%">
            <el-table-column width="130" label="Field">
                <template slot-scope="scope">
                    {{fields[scope.row.id]}}
                </template>
            </el-table-column>
            <el-table-column label="Label">
                <template slot-scope="scope">
                    <el-input auto-complete="off" v-model="field_options[scope.row.id]['label']"/>
                </template>
            </el-table-column>
            <el-table-column label="Placeholder">
                <template slot-scope="scope">
                    <el-input auto-complete="off" v-model="field_options[scope.row.id]['placeholder']"/>
                </template>
            </el-table-column>
            <el-table-column label="Default Value">
                <template slot-scope="scope">
                    <div class="wp_vue_editor_wrapper">
                        <el-input auto-complete="off" v-model="field_options[scope.row.id]['default_value']">
                            <popover
                                @command="(code) => { field_options[scope.row.id]['default_value'] += code }"
                                slot="suffix" :data="editorShortcodes"
                                btnType="text"
                                buttonText='<i class="el-icon-menu"></i>'>
                            </popover>
                        </el-input>
                    </div>
                </template>
            </el-table-column>
            <el-table-column width="85" label="Visibility">
                <template slot-scope="scope">
                    <el-switch
                        active-value="yes"
                        inactive-value="no"
                        v-model="field_options[scope.row.id]['visibility']"></el-switch>
                </template>
            </el-table-column>
            <el-table-column width="85" label="Required">
                <template slot-scope="scope">
                    <el-switch
                        active-value="yes"
                        inactive-value="no"
                        v-model="field_options[scope.row.id]['required']"></el-switch>
                </template>
            </el-table-column>
        </el-table>
    </div>
</template>

<script>
    import popover from './input-popover-dropdown.vue'
    export default {
        name: 'AddressFields',
        components: {
            popover
        },
        props: [ 'fields', 'field_options' ],
        data() {
            return {
                data_fields: [],
                plain_content: '',
                countries: {},
                cursorPos: 0,
                editorShortcodes: []
            }
        },
        
        created() {
            this.data_fields.push(Object.values(this.field_options))
            this.countries = window.wpPayFormsAdmin.countries
            this.editorShortcodes =  Object.values(window.wpPayFormsAdmin.value_placeholders)
        },

        methods: {
            handleCommand(command) {
                if (this.hasWpEditor) {
                    tinymce.activeEditor.insertContent(command);
                } else {
                    var part1 = this.plain_content.slice(0, this.cursorPos);
                    var part2 = this.plain_content.slice(this.cursorPos, this.plain_content.length);
                    this.plain_content = part1 + command + part2;
                    this.cursorPos += command.length;
                }
            },
        }
    }
</script>