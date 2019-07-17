<template>
    <div class="key_pair_table_wrapper">
        <table class="ninja_filter_table">
            <thead>
            <tr>
                <th></th>
                <th>Item Name</th>
                <th>Item Price</th>
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
                        <el-input size="mini" v-model="filter.label"
                                  type="text"></el-input>
                    </td>
                    <td>
                        <el-input-number size="small" v-model="filter.value" :min="0"></el-input-number>
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
    </div>
</template>
<script type="text/babel">
    import draggable from 'vuedraggable'

    export default {
        name: 'key_pair_options',
        components: { draggable },
        props: ['value'],
        data() {
            return {
                value_option: false,
                item: [{}]
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
            }
        },
        mounted() {
            if (this.value) {
                this.item = JSON.parse(JSON.stringify(this.value));
            }
        }
    }
</script>

<style lang="scss">
</style>
