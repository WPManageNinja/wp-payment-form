<?php

function wpfFomatPrice($price, $formId = false)
{
    return '$' . $price;
}

function wpfGetStripePubKey()
{
    if (defined('WP_PAY_FORM_STRIPE_PUB_KEY') && WP_PAY_FORM_STRIPE_PUB_KEY) {
        return WP_PAY_FORM_STRIPE_PUB_KEY;
    }
    return '';
}

function wpfGetStripeSecretKey()
{
    if (defined('WP_PAY_FORM_STRIPE_SECRET_KEY') && WP_PAY_FORM_STRIPE_SECRET_KEY) {
        return WP_PAY_FORM_STRIPE_SECRET_KEY;
    }
    return '';
}

function wpfIsStripeKeysDefined()
{
    return defined('WP_PAY_FORM_STRIPE_SECRET_KEY') && defined('WP_PAY_FORM_STRIPE_PUB_KEY');
}

if (!function_exists('ninja_table_admin_role')) {
    function ninja_table_admin_role()
    {
        if (current_user_can('administrator')) {
            return 'administrator';
        }
        $roles = apply_filters('ninja_table_admin_role', array('administrator'));
        if (is_string($roles)) {
            $roles = array($roles);
        }
        foreach ($roles as $role) {
            if (current_user_can($role)) {
                return $role;
            }
        }
        return false;
    }
}

function wpPayformDB() {
    if (! function_exists('wpFluent')) {
        include WPPAYFORM_DIR.'includes/libs/wp-fluent/wp-fluent.php';
    }
    return wpFluent();
}
