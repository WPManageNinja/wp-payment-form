const DashBoardStats = require('./Components/Dashboard');
const GlobalView = require('./Components/Global/index');
const AllForms = require('./Components/Forms/AllForms');

const EditFormView = require('./Components/Form/index');
const FormBuilder = require('./Components/Form/FormBuilder');
const FormSettingsIndex = require('./Components/Form/settings/index')
const FormPaymentSettings = require('./Components/Form/settings/ConfirmationSettings');
const FormCurrencySettings = require('./Components/Form/settings/CurrencySettings');
const FormSchedulingSetting = require('./Components/Form/settings/SchedulingSettings');

import Entries from './Components/Entries/Entries';
import Entry from './Components/Entries/Entry';
import SettingView from './Components/Settings/index'
import StripeSettings from './Components/Settings/StripeSettings'
import GeneralSettings from './Components/Settings/GeneralSettings'
import FormDesignSettings from './Components/Form/settings/FormDesignSettings'

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
const formEditorChildrenRoutes = window.WPPayForms.applyFilters('wpf_main_children_roues', [
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
    },
    {
        path: 'scheduling_settings',
        name: 'scheduling_settings',
        component: FormSchedulingSetting
    }
]);
const formEditRoutes = window.WPPayForms.applyFilters('wpf_edit_children_roues',[
    {
        path: 'form-builder',
        name: 'edit_form',
        component: FormBuilder
    },
    {
        path: 'settings',
        component: FormSettingsIndex,
        children: formEditorChildrenRoutes
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
        path: '/edit-form/:form_id/',
        component: EditFormView,
        props: true,
        children: formEditRoutes
    }
];