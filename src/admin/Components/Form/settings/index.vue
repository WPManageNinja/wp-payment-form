<template>
    <div class="wppaymform_editor">
        <div class="payform_editor_wrapper">
            <el-container>
                <el-aside :width="sidebarWidth">
                    <el-menu background-color="#545c64"
                             text-color="#fff"
                             :router="true"
                             :collapse="isCollapse"
                             :default-active="current_route"
                             active-text-color="#ffd04b"
                    >
                        <el-menu-item
                            v-for="formMenu in form_menus"
                            :key="formMenu.route"
                            :route="{ name: formMenu.route }"
                            :index="formMenu.route">
                            <i :class="formMenu.icon"></i>
                            <span>{{  formMenu.title }}</span>
                        </el-menu-item>
                    </el-menu>
                </el-aside>
                <el-main class="payform_settings_wrapper">
                    <router-view :form_id="form_id"></router-view>
                </el-main>
            </el-container>
        </div>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'global_settings',
        data() {
            return {
                current_route: this.$route.name,
                form_id: this.$route.params.form_id,
                sidebarWidth: "200",
                isCollapse: false,
                form_menus: [
                    {
                        route: 'confirmation_settings',
                        title: 'Confirmation Settings',
                        icon: 'el-icon-success'
                    },
                    {
                        route: 'form_currency_settings',
                        title: 'Currency Settings',
                        icon: 'dashicons dashicons-translation'
                    },
                    {
                        route: 'design_options',
                        title: 'Design Settings',
                        icon: 'dashicons dashicons-art'
                    },
                    {
                        route: 'scheduling_settings',
                        title: 'Scheduling Settings',
                        icon: 'dashicons dashicons-calendar-alt'
                    },
                    {
                        route: 'custom_css_js',
                        title: 'Custom CSS/JS',
                        icon: 'dashicons dashicons-media-code'
                    }
                ]
            }
        },
        mounted() {
            if(window.outerWidth < 600) {
                this.sidebarWidth = "auto";
                this.isCollapse = true;
            }
        }
    }
</script>
