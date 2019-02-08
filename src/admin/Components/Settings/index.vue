<template>
    <div class="wppaymform_editor">

        <div class="payform_editor_wrapper">
            <el-container>
                <el-aside width="200px">
                    <el-menu background-color="#545c64"
                             text-color="#fff"
                             :router="true"
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
                <el-main>
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
                current_route: this.$route.name,
                form_menus: [
                    {
                        route: 'stripe_settings',
                        title: 'Stripe Settings',
                        icon: 'dashicons dashicons-cart'
                    },
                    {
                        route: 'currency_settings',
                        title: 'Currency Settings',
                        icon: 'dashicons dashicons-translation'
                    }
                ],
                editFormModalShow: false,
                form: {},
                fetching: false,
                saving: false,
            }
        }
    }
</script>
