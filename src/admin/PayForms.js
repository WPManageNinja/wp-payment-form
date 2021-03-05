import Vue from './elements';
import Router from 'vue-router';
Vue.use(Router);

import { applyFilters, addFilter, addAction, doAction } from '@wordpress/hooks';

export default class WPPayForms {
    constructor() {
        this.applyFilters = applyFilters;
        this.addFilter = addFilter;
        this.addAction = addAction;
        this.doAction = doAction;
        this.Vue = Vue;
        this.Router = Router;
    }

    $get(options) {
        options['wpf_admin_nonce'] = window.wpPayFormsAdmin.wpf_admin_nonce;
        return window.jQuery.get(window.wpPayFormsAdmin.ajaxurl, options);
    }

    $adminGet(options) {
        options.action = 'wppayform_forms_admin_ajax';
        options['wpf_admin_nonce'] = window.wpPayFormsAdmin.wpf_admin_nonce;
        return window.jQuery.get(window.wpPayFormsAdmin.ajaxurl, options);
    }

    $post(options) {
        options['wpf_admin_nonce'] = window.wpPayFormsAdmin.wpf_admin_nonce;
        return window.jQuery.post(window.wpPayFormsAdmin.ajaxurl, options);
    }

    $adminPost(options) {
        options.action = 'wppayform_forms_admin_ajax';
        options['wpf_admin_nonce'] = window.wpPayFormsAdmin.wpf_admin_nonce;
        return window.jQuery.post(window.wpPayFormsAdmin.ajaxurl, options);
    }

    $getJSON(options) {
        options['wpf_admin_nonce'] = window.wpPayFormsAdmin.wpf_admin_nonce;
        return window.jQuery.getJSON(window.wpPayFormsAdmin.ajaxurl, options);
    }
}
