<template>
    <div class="wppayform_main_nav">
            <span class="plugin-name">
                <router-link :to="{ name: 'forms' }">
                    <img class="wpf_plugin_brand_img" :src="icon">
                </router-link>
            </span>
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
</template>

<script type="text/babel">
    export default {
        name: 'global_navigation',
        data() {
            return {
                topMenus: [],
                icon: window.wpPayFormsAdmin.icon_url,
                goFull: window.localStorage.getItem('wpf_full_screen')
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
            },
            toggleFullScreen() {
                let status = 'yes';
                if (window.localStorage.getItem('wpf_full_screen') === 'yes') {
                    status = 'no';
                }
                this.goFull = status;
                window.localStorage.setItem('wpf_full_screen', status)
                jQuery('html').toggleClass('wpf_go_full');
            }
        },
        mounted() {
            this.setTopmenu();
            if (window.localStorage.getItem('wpf_full_screen') === 'yes') {
                jQuery('html').addClass('wpf_go_full');
            }
        }
    }
</script>
