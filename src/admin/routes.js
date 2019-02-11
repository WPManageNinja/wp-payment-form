const DashBoardStats = require('./Components/Dashboard');
const GlobalView = require('./Components/Global/index');
const AllForms = require('./Components/Forms/AllForms');
const EditFormView = require('./Components/Form/index');
const EditSingleForm = require('./Components/Dashboard');

const FormSettingsIndex = require('./Components/Form/settings/index');
const FormBuilder = require('./Components/Form/FormBuilder');
const FormPaymentSettings = require('./Components/Form/settings/ConfirmationSettings');
const FormCurrencySettings = require('./Components/Form/settings/CurrencySettings');


import Entries from './Components/Entries/Entries';
import Entry from './Components/Entries/Entry';
import SettingView from './Components/Settings/index'
import StripeSettings from './Components/Settings/StripeSettings'
import GeneralSettings from './Components/Settings/GeneralSettings'
import FormDesignSettings from './Components/Form/settings/FormDesignSettings'

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
                children: [
                    {
                        name:'stripe_settings',
                        path: 'stripe-settings',
                        component: StripeSettings
                    },
                    {
                        name:'general_settings',
                        path: 'general-settings',
                        component: GeneralSettings
                    }
                ]
            },
            {
                path: '/support',
                name: 'support',
                component: DashBoardStats
            },
        ]
    },
    {
        path: '/edit-form/:form_id/',
        component: EditFormView,
        props: true,
        children: [
            {
                path: 'form-builder',
                name: 'edit_form',
                component: FormBuilder
            },
            {
                path: 'settings/',
                component: FormSettingsIndex,
                children: [
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
                        path: 'email_settings',
                        name: 'email_settings',
                        component: EditSingleForm
                    }
                ]
            }
        ]
    }
];
