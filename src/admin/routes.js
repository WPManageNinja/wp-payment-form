const DashBoardStats = require('./Components/Dashboard');
const GlobalView = require('./Components/Global/index');
const AllForms = require('./Components/Forms/AllForms');
const EditFormView = require('./Components/Form/index');

const FormBuilder = require('./Components/Form/FormBuilder');
const FormPaymentSettings = require('./Components/Form/settings/ConfirmationSettings');
const FormCurrencySettings = require('./Components/Form/settings/CurrencySettings');

import Entries from './Components/Entries/Entries';
import Entry from './Components/Entries/Entry';
import SettingView from './Components/Settings/index'
import StripeSettings from './Components/Settings/StripeSettings'
import GeneralSettings from './Components/Settings/GeneralSettings'
import FormDesignSettings from './Components/Form/settings/FormDesignSettings'

const formEditorChildrenRoutes = window.WPPayForms.applyFilters('wpf_form_children_roues', [
    {
        path: 'form-builder',
        name: 'edit_form',
        component: FormBuilder
    },
    {
        path: 'confirmation_settings',
        name: 'confirmation_settings',
        component: FormPaymentSettings
    },
    {
        path: 'currency_settings',
        name: 'form_currency_settings',
        component: FormCurrencySettings
    },
    {
        path: 'design_options',
        name: 'design_options',
        component: FormDesignSettings
    }
]);

const globalSettingsViewChilderRoutes = window.WPPayForms.applyFilters('wpf_global_settings_childern_routes', [
    {
        name: 'stripe_settings',
        path: 'stripe-settings',
        component: StripeSettings
    },
    {
        name: 'general_settings',
        path: 'general-settings',
        component: GeneralSettings
    }
]);

export const routes = [
    {
        path: '/',
        component: GlobalView,
        props: true,
        children: [
            {
                path: '/',
                name: 'forms',
                component: AllForms
            },
            {
                path: 'entries',
                name: 'entries',
                component: Entries
            },
            {
                path: 'entries/:entry_id/view',
                name: 'entry',
                component: Entry
            },
            {
                path: '/settings/',
                component: SettingView,
                children: globalSettingsViewChilderRoutes
            },
            {
                path: '/support',
                name: 'support',
                component: DashBoardStats
            }
        ]
    },
    {
        path: '/edit-form/:form_id/settings/',
        component: EditFormView,
        props: true,
        children: formEditorChildrenRoutes
    }
];