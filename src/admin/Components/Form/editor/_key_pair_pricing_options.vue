<template>
    <div>
        <div class="key_pair_table_wrapper">
        <table class="ninja_filter_table">
            <thead>
           <tr>
                    <th style="width: 40px;"></th>
                    <th v-if="enableImage == 'yes'" style="width: 70px;">Photo</th>
                    <th>Item Name</th>
                    <th style="width: 90px;">Item Price</th>
                    <th style="width: 120px;"></th>
                </tr>
            </thead>
            <draggable
                :options="{handle:'.handle'}"
                :list="item"
                :element="'tbody'"
            >
                <tr v-for="(filter, index) in item" :key="index">
                    <td>
                        <span style="margin-top: 10px" class="dashicons dashicons-editor-justify handle"></span>
                    </td>
                    <td v-if="enableImage == 'yes'">
                            <photo-widget :product="filter" />
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
    </div>
</template>
<script type="text/babel">
    import draggable from 'vuedraggable'
    import PhotoWidget from './_photo_widget';


    export default {
        name: 'key_pair_options',
        components: { draggable, PhotoWidget },
        props: ['value','enableImage'],
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
            if (!this.value[0].photo && this.enableImage =='yes'){
                return
            }

            if (this.value) {
                this.item = JSON.parse(JSON.stringify(this.value));
            }

        }
    }
</script>

<style lang="scss">
.enableImageContainer{
    float: left;
}
</style>
