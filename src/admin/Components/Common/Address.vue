<template>
    <div class="address_fields_wrapper">
        <p><strong>Address Fields:</strong></p>
        <el-table :data="data_fields[0]" border style="width: 100%">
            <el-table-column label="Field">
                <template slot-scope="scope">
                    {{fields[scope.row.id]}}
                </template>
            </el-table-column>
            <el-table-column label="Label">
                <template slot-scope="scope">
                    <el-input v-model="field_options[scope.row.id]['label']"/>
                </template>
            </el-table-column>
            <el-table-column label="Default Value">
                <template slot-scope="scope">
                    <el-input v-model="field_options[scope.row.id]['default_value']"/>
                </template>
            </el-table-column>
            <el-table-column label="Visibility">
                <template slot-scope="scope">
                    <el-switch
                        active-value="yes"
                        inactive-value="no"
                        v-model="field_options[scope.row.id]['visibility']"></el-switch>
                </template>
            </el-table-column>
            <el-table-column label="Required">
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
    export default {
        name: 'AddressFields',
        props: [ 'fields', 'field_options' ],
        data() {
            return {
                data_fields: []
            }
        },
        created() {
            this.data_fields.push(Object.values(this.field_options))
        }
    }
</script>