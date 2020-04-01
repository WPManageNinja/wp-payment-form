<template>
    <div>
        <el-checkbox v-model="conditionals.status" v-if="! disabled">
            Enable conditional logic
        </el-checkbox>

        <el-checkbox v-else disabled @click.native="comingSoon = true">
            Enable conditional logic
        </el-checkbox>

        <div v-if="conditionals.status">
            Send this notification if

            <select v-model="conditionals.type">
                <option v-for="(label, value) in {all: 'All', any: 'Any'}" :key="value" :value="value">
                    {{ label }}
                </option>
            </select>

            of the following match:

            <el-row v-for="(logic, key) in items" :key="key"
                    style="margin-top: 15px;" :gutter="10"
            >
                <el-col :md="8">
                    <el-select v-model="items[key].field" style="width: 100%" @change="items[key].value = ''">
                        <el-option v-for="(field, key) in fields" :key="key"
                                   :label="field.admin_label" :value="key"
                        ></el-option>
                    </el-select>
                </el-col>

                <el-col :md="5">
                    <el-select v-model="items[key].operator">
                        <el-option value="=" label="equal"></el-option>
                        <el-option value="!=" label="not equal"></el-option>
                        <template v-if="fields[logic.field] && !Object.keys(fields[logic.field].options).length">
                            <el-option value=">" label="greater than"></el-option>
                            <el-option value="<" label="less than"></el-option>
                            <el-option value=">=" label="greater than or equal"></el-option>
                            <el-option value="<=" label="less than or equal"></el-option>
                            <el-option value="contains" label="contains"></el-option>
                            <el-option value="startsWith" label="starts with"></el-option>
                            <el-option value="endsWith" label="ends with"></el-option>
                        </template>
                    </el-select>
                </el-col>

                <el-col :md="8">
                    <el-select v-if="fields[logic.field] && Object.keys(fields[logic.field].options).length" v-model="items[key].value" style="width: 100%">
                        <el-option v-for="(label, value) in fields[logic.field].options" :key="value"
                                   :label="label" :value="value"
                        ></el-option>
                    </el-select>
                    <el-input v-else placeholder="Enter a value" v-model="items[key].value"></el-input>
                </el-col>

                <el-col :md="3" class="action-btns ">
                    <i class="el-icon-plus" @click="add(key)"></i>

                    <i class="el-icon-minus" @click="remove(key)"
                       v-if="items.length > 1"
                    ></i>
                </el-col>
            </el-row>
        </div>

        <coming-soon
            v-if="disabled"
            :visibility.sync="comingSoon" />

    </div>
</template>

<script>
    import ComingSoon from '../../modals/ItemDisabled';

    export default {
        name: 'FilterFields',
        components: {
            ComingSoon
        },
        props: {
            conditionals: {
                type: Object,
                required: true,
                default: {}
            },
            fields: {
                type: Object,
                required: true,
                default: {}
            },
            disabled: {
                type: Boolean,
                required: true,
                default: false,
            }
        },
        data() {
            return {
                defaultRules: {
                    field: null,
                    operator: '=',
                    value: null
                },
                comingSoon: false,
            }
        },
        computed: {
            items() {
                return this.conditionals.conditions;
            }
        },
        methods: {
            add(index) {
                this.items.splice(index + 1, 0, {...this.defaultRules});
            },
            remove(index) {
                this.items.splice(index, 1);
            }
        },
        mounted() {
            if(!this.conditionals.conditions.length) {
                this.conditionals.conditions.push({...this.defaultRules});
            }
        }
    }
</script>

<style>
    .action-btns i {
        cursor: pointer;
        padding: 2px;
    }

    .action-btns i:hover {
        color: #58B7FF
    }
</style>