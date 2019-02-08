<template>
    <div>
        <el-popover
            ref="input-popover1"
            placement="bottom"
            width="170"
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
        <el-button class="editor-add-shortcode"
                   size="mini"
                   v-popover:input-popover1
                   type="success">
            Add Shortcodes
            <i class="el-icon-arrow-down el-icon--right"></i>
        </el-button>
    </div>
</template>

<script>
    export default {
        name: 'inputPopoverDropdown',
        props: {
            data: Array
        },
        methods: {
            insertShortcode(code) {
                this.$emit('command', code);
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