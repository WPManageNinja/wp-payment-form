<template>
    <div class="tabular_products_wrapper">
        <div class="tablular_headings">
            <h4 class="tabular_title" style="margin:0">{{item.label}}</h4>
            <div class="tabular_actions">
                <el-switch
                    v-model="field_options.enable_image"
                    active-value="yes"
                    inactive-value="no"
                    active-text="Enable Image"
                />
                <el-switch
                    :disabled="field_options.enable_image != 'yes'"
                    v-model="field_options.enable_lightbox"
                    active-value="yes"
                    inactive-value="no"
                    active-text="Enable Lightbox"
                />
            </div>
        </div>

        <div style="margin: 10px 0px 20px;" class="tabular_product_pair_table_wrapper">
            <table class="ninja_filter_table">
                <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th v-if="field_options.enable_image == 'yes'" style="width: 70px;">Photo</th>
                    <th>Item Name</th>
                    <th style="width: 90px;">Item Price</th>
                    <th style="width: 90px;">Default Quantity</th>
                    <th style="width: 90px;">Minimum Quantity</th>
                    <th style="width: 120px;"></th>
                </tr>
                </thead>
                <draggable
                    :options="{handle:'.handle'}"
                    :list="product_settings"
                    :element="'tbody'"
                >
                    <tr v-for="(item, index) in product_settings">
                        <td>
                            <span style="margin-top: 10px" class="dashicons dashicons-editor-justify handle"></span>
                        </td>
                        <td v-if="field_options.enable_image == 'yes'">
                            <photo-widget :product="item" />
                        </td>
                        <td>
                            <el-input placeholder="Item Name" size="mini" v-model="item.product_name"
                                      type="text"></el-input>
                        </td>
                        <td>
                            <el-input-number size="small" v-model="item.product_price" :min="0"></el-input-number>
                        </td>
                        <td>
                            <el-input-number size="small" v-model="item.default_quantity" :min="0"></el-input-number>
                        </td>
                        <td>
                            <el-input-number size="small" v-model="item.min_quantity" :min="0"></el-input-number>
                        </td>
                        <td>
                            <el-button :disabled="product_settings.length == 1" @click="deleteItem(index)" type="danger" size="mini">-
                            </el-button>
                            <el-button @click="add()" v-show="(index + 1) == product_settings.length" type="success" size="mini">+
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
        name: 'tabular_products',
        components: { draggable, PhotoWidget },
        props: ['item', 'product_settings', 'field_options'],
        methods: {
            deleteItem(index) {
                this.product_settings.splice(index, 1);
            },
            add() {
                this.product_settings.push({
                    product_name: '',
                    default_quantity: '',
                    min_quantity: '',
                    product_price: ''
                });
            }
        }
    }
</script>
