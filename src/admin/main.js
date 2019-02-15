window.WPPayFormsBus = new window.WPPayForms.Vue();

window.WPPayForms.Vue.mixin({
    methods: {
        $t(str) {
            let transString = wpPayFormsAdmin.i18n[str];
            if(transString) {
                return transString;
            }
            return str;
        },
        setStoreData(key, value) {
            if(window.localStorage) {
                localStorage.setItem("wppayforms_"+key, value);
            }
        },
        deleteStoreData(key) {
            if(window.localStorage) {
                localStorage.removeItem("wppayforms_"+key);
            }
        },
        getFromStore(key, defaultValue) {
            if(window.localStorage) {
                let itemValue = localStorage.getItem('wppayforms_'+key);
                if(itemValue) {
                    return itemValue;
                }
            }
            return defaultValue;
        },
        applyFilters: window.WPPayForms.applyFilters,
        addFilter: window.WPPayForms.addFilter,
        addAction: window.WPPayForms.addFilter,
        doAction: window.WPPayForms.doAction,
        $get: window.WPPayForms.$get,
        $adminGet: window.WPPayForms.$adminGet,
        $adminPost: window.WPPayForms.$adminPost,
        $post: window.WPPayForms.$post
    },
    data(){
        return {
            has_pro: window.wpPayFormsAdmin.has_pro,
            pro_purchase_url: 'https://wpmanageninja.com/wppayform-pro-wordpress-payments-form-builder/?utm_source=upgrade&utm_medium=url&utm_campaign=wppayform_upgrade'
        }
    },
    filters: {
        ucFirst(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }
    }
});

import {routes} from './routes'

const router = new window.WPPayForms.Router({
    routes: window.WPPayForms.applyFilters('wpf_global_vue_routes', routes),
    linkActiveClass: 'active'
});

import App from './App';

new window.WPPayForms.Vue({
    el: '#wppayformsapp',
    render: h => h(App),
    router: router,
    mounted() {
        window.WPPayFormsBus.$on('site_title', (title) => {
            jQuery(document).attr("title", title + ' :: WPPayFrom');
        });
    }
});
