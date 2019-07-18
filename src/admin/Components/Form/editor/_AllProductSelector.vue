<template>
    <div class="item_selector">
        <el-select size="mini" v-model="field_options[item_name]">
            <el-option v-for="element in productElements" :key="element.id" :value="element.id" :label="element.label"></el-option>
        </el-select>
    </div>
</template>
<script type="text/babel">
    import each from 'lodash/each';
    export default {
        name: 'allItemSelector',
        props: ['all_elements', 'field_options', 'item_name'],
        computed: {
            productElements() {
                let paymentFields = [];
                each(this.all_elements, (element) => {
                    if(element.is_payment_field) {
                        paymentFields.push({
                            id: element.id,
                            label: element.field_options.label || element.id
                        });
                    }
                });
                return paymentFields;
            }
        }
    }
</script>