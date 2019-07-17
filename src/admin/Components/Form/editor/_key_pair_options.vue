<template>
    <div class="key_pair_table_wrapper">
        <div style="margin-bottom: 15px" class="keypair_header">
            <el-checkbox v-model="value_option">Provide Value Separately</el-checkbox>
            <el-button style="float: right" @click="initBulkEdit()" size="mini">Bulk Edit</el-button>
        </div>
        <table class="ninja_filter_table">
            <thead>
            <tr>
                <th></th>
                <th>Label</th>
                <th v-show="value_option">Value</th>
                <th></th>
            </tr>
            </thead>
            <draggable
                :options="{handle:'.handle'}"
                :list="item"
                :element="'tbody'"
            >
                <tr v-for="(filter, index) in item">
                    <td>
                        <span style="margin-top: 10px" class="dashicons dashicons-editor-justify handle"></span>
                    </td>
                    <td>
                        <el-input @blur="changeFilterLabel(filter)" size="mini" v-model="filter.label"
                                  type="text"></el-input>
                    </td>
                    <td v-show="value_option">
                        <el-input size="mini" v-model="filter.value" type="text"></el-input>
                    </td>
                    <td>
                        <el-button :disabled="item.length == 1" @click="deleteItem(index)" type="danger" size="mini">-
                        </el-button>
                        <el-button @click="add()" v-show="(index + 1) == item.length" type="success" size="mini">+
                        </el-button>
                    </td>
                </tr>
            </draggable>
        </table>

        <el-dialog
            :append-to-body="true"
            class="backdrop"
            title="Edit your options"
            :visible.sync="bulkEditVisible"
        >
            <div class="bulk_editor_wrapper">
                <h4>Please provide the value as LABEL:VALUE as each line.</h4>
                <el-input type="textarea" :rows="5" v-model="value_key_pair_text"></el-input>
                <p>You can simply give value only the system will convert the label as value</p>
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button size="mini" @click="bulkEditVisible = false">Cancel</el-button>
                <el-button size="mini" type="primary" @click="confirmBulkEdit()">Confirm</el-button>
            </span>
        </el-dialog>

    </div>
</template>
<script type="text/babel">
    import draggable from 'vuedraggable'
    import each from 'lodash/each';

    export default {
        name: 'key_pair_options',
        components: { draggable },
        props: ['value'],
        data() {
            return {
                value_option: false,
                item: [{}],
                bulkEditVisible: false,
                value_key_pair_text: ''
            }
        },
        watch: {
            item: {
                handler() {
                    this.$emit('update:value', JSON.parse(JSON.stringify(this.item)));
                },
                deep: true
            }
        },
        methods: {
            deleteItem(index) {
                this.item.splice(index, 1);
            },
            add() {
                this.item.push({
                    label: '',
                    value: ''
                });
            },
            changeFilterLabel(filter) {
                if (!this.value_option) {
                     filter.value = JSON.parse(JSON.stringify(filter.label));
                }
            },
            initBulkEdit() {
                let astext = '';
                each(this.item, (item, index) => {
                    astext += item.label;
                    if (astext && astext != item.value) {
                        astext += ':' + item.value;
                    }
                    astext += String.fromCharCode(13, 10);
                });
                this.value_key_pair_text = astext;
                this.bulkEditVisible = true
            },

            confirmBulkEdit() {
                let lines = this.value_key_pair_text.split('\n');
                let values = [];
                each(lines, (line) => {
                    let lineItem = line.split(':');
                    let label = lineItem[0];
                    let value = lineItem[1];
                    if (!value) {
                        value = label;
                    }
                    if (label && value) {
                        values.push({
                            label: label,
                            value: value
                        });
                    }
                });
                this.item = values;
                this.bulkEditVisible = false
            },
        },
        mounted() {
            if (this.value) {
                this.item = JSON.parse(JSON.stringify(this.value));
            }
        }
    }
</script>