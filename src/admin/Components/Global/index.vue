<template>
    <div class="wppaymform_global">
        <div class="wppayform_main_nav">
            <span class="plugin-name">WPPayForm</span>
            <router-link v-for="menuItem in topMenus" :key="menuItem.route" active-class="ninja-tab-active" exact :class="['ninja-tab']" :to="{ name: menuItem.route }">
                {{ menuItem.title }}
            </router-link>
            <a
                v-if="!has_pro"
                style="float: right;"
                target="_blank"
                class="el-button payform_action el-button--danger el-button--small"
                :href="pro_purchase_url">
                    Upgrade To Pro
            </a>
        </div>
        <router-view></router-view>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'global_wrapper',
        data() {
            return {
                topMenus: []
            }
        },
        methods: {
            setTopmenu() {
                this.topMenus = this.applyFilters('wpf_top_level_menu', [
                    {
                        route: 'forms',
                        title: 'All Forms'
                    },
                    {
                        route: 'entries',
                        title: 'All Entries & Payments'
                    },
                    {
                        route: 'general_settings',
                        title: 'Settings'
                    },
                    {
                        route: 'support',
                        title: 'Support'
                    }
                ])
            }
        },
        mounted() {
            this.setTopmenu();
        }
    }
</script>