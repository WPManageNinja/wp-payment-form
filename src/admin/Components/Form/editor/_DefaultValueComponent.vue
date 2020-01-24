<template>
    <el-input :placeholder="item.label" size="mini" v-model="element.field_options[itemName]">
        <popover
            v-if="has_pro"
            @command="(code) => { element.field_options[itemName] += code }"
            slot="suffix" :data="merge_tags"
            btnType="text"
            buttonText='<i class="el-icon-menu"></i>'>
        </popover>
        <el-button @click="showPro()" type="text" size="mini" icon="el-icon-menu" slot="suffix" v-else></el-button>
    </el-input>
</template>
<script type="text/babel">
    import popover from '../../Common/input-popover-dropdown.vue';
    export default {
        name: 'default_value_item',
        components: {
            popover
        },
        props: ['item', 'element', 'itemName'],
        data() {
            return {
                merge_tags: Object.values(window.wpPayFormsAdmin.value_placeholders)
            }
        },
        methods: {
            showPro() {
                this.$emit('showProMessage', 1);
            }
        },
        mounted() {
            if (!this.element.field_options[this.itemName]) {
                this.$set(this.element.field_options, this.itemName, '');
            }
        }
    }
</script>
