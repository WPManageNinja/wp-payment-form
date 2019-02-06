<template>
    <div class="wppaymform_global">
        <div class="wppayform_main_nav">
            <span class="plugin-name">WP PayForm</span>
            <router-link v-for="menuItem in topMenus" :key="menuItem.route" active-class="ninja-tab-active" exact :class="['ninja-tab']" :to="{ name: menuItem.route }">
                {{ menuItem.title }}
            </router-link>
        </div>
        <router-view></router-view>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'global_wrapper',
        data() {
            return {
                topMenus: [
                    {
                        route: 'forms',
                        title: 'All Forms'
                    },
                    {
                        route: 'entries',
                        title: 'Entries & Payments'
                    },
                    {
                        route: 'stripe_settings',
                        title: 'Settings'
                    },
                    {
                        route: 'support',
                        title: 'Support'
                    }
                ]
            }
        }
    }
</script>