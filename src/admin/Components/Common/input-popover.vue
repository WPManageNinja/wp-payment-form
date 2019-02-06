<template>
<div>
    <el-popover
        ref="input-popover"
        placement="bottom"
        width="200"
        popper-class="el-dropdown-list-wrapper"
        trigger="click">
            <ul class="el-dropdown-menu el-dropdown-list">
                <li v-for="item in data">
                    <span v-if="data.length > 1" class="group-title">{{ item.title }}</span>
                    <ul>
                        <li v-for="title, code in item.shortcodes"
                            @click="insertShortcode(code)"
                            class="el-dropdown-menu__item">
                            {{ title }}
                        </li>
                    </ul>
                </li>
            </ul>
    </el-popover>

    <el-input size="small" v-if="fieldType != 'textarea'" v-model="model" :type="fieldType">
        <el-button slot="append" icon="el-icon-more" v-popover:input-popover></el-button>
    </el-input>

    <div v-if="fieldType == 'textarea'" class="input-textarea-value">
        <i class="icon el-icon-tickets" v-popover:input-popover></i>
        <el-input type="textarea" v-model="model"></el-input>
    </div>
</div>
</template>

<script>
export default {
    name: 'inputPopover',
    props: {
        value : String,
        fieldType: String,
        data: Array,
        attrName: {
            type: String,
            default: 'attribute_name'
        }
    },
    data() {
        return {
            model: this.value,
        }
    },
    watch: {
        model() {
            this.$emit('input', this.model);
        }
    },
    methods: {
        insertShortcode(codeString) {
            if (this.model == undefined) {
                this.model = '';
            }
            this.model += codeString.replace(/param_name/, this.attrName);
        }
    }
}
</script>

<style lang="scss">
    .el-dropdown-list-wrapper {
        padding: 0;

        .group-title {
            display: block;
            padding: 5px 10px;
            background-color: gray;
            color: #fff;
        }
    }

    .input-textarea-value {
        position: relative;

        .icon {
            position: absolute;
            right: 0;
            top: -18px;
            cursor: pointer;
        }
    }
</style>