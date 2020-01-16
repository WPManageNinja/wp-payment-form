<template>
  <div class="address_fields_wrapper">
    <p><strong>Address Fields:</strong></p>
    <el-table
      :data="data_fields[0]"
      border
      style="width: 100%"   
    >
      <el-table-column
        label="Field"
        prop="label"
        width="180"
      >
      </el-table-column>
      <el-table-column
        label="Label"
        width="180"
      >   
        <template slot-scope="scope">
          <input v-model="scope.row.label" />
        </template> 
      </el-table-column>
      <el-table-column
        label="Visibility"
        width="180"
      >
        <template slot-scope="scope">
          <el-switch v-model="scope.row.visibility"></el-switch>
        </template>
      </el-table-column>
      <el-table-column
        label="Required"
        width="264"
      >
        <template slot-scope="scope">
          <el-switch v-model="scope.row.required"></el-switch>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script>
export default {
  name: 'AddressFields',
  props: [
    'fields',
    'field_options'
  ],
  data() {
    return {
      // data_fields: ['Field', 'Label', 'Visibility', 'Required'],
      data_fields: []
    }
  },
  created() {
    this.data_fields.push(Object.values(this.field_options))
    console.log(this.data_fields[0])
  }
}
</script>