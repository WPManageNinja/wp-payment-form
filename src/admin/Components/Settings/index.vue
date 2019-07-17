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
                    <router-view></router-view>
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
                form_menus: [],
                editFormModalShow: false,
                form: {},
                fetching: false,
                saving: false,
                sidebarWidth: '200px',
                isCollapse: false
            }
        },
        computed: {
            current_route() {
                return this.$route.name;
            }
        },
        methods: {
            setMenu() {

                let menu = [
                    {
                        route: 'general_settings',
                        title: 'General Settings',
                        icon: 'dashicons dashicons-translation'
                    },
                    {
                        route: 'stripe_settings',
                        title: 'Stripe Settings',
                        icon: 'el-icon-bank-card'
                    },
                    {
                        route: 'paypal_settings',
                        title: 'Paypal Settings',
                        icon: 'el-icon-money'
                    },
                    {
                        route: 'tools',
                        title: 'Tools',
                        icon: 'el-icon-s-cooperation'
                    },
                    {
                        route: 'recaptcha',
                        title: 'reCAPTCHA Settings',
                        icon: 'el-icon-help'
                    }
                ];

                if(this.has_pro) {
                    menu.push({
                        route: 'licensing',
                        title: 'Licensing',
                        icon: 'dashicons dashicons-category'
                    })
                }
                this.form_menus = this.applyFilters('global_settings_menu', menu);
            }
        },
        mounted() {
            this.setMenu();
            if(window.outerWidth < 500) {
                this.sidebarWidth = "auto";
                this.isCollapse = true;
            }
        }
    }
</script>
