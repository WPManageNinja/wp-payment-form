const DashBoardStats = require('./Components/Dashboard');
const GlobalView = require('./Components/Global/index');
const AllForms = require('./Components/Forms/AllForms');
const EditFormView = require('./Components/Form/index');
const EditSingleForm = require('./Components/Form/EditForm');
const FormPaymentSettings = require('./Components/Form/PaymentSettings');
const FormBuilder = require('./Components/Form/FormBuilder');
import Entries from './Components/Entries/Entries';
import Entry from './Components/Entries/Entry';
import SettingView from './Components/Settings/index'
import StripeSettings from './Components/Settings/StripeSettings'
import CurrencySettings from './Components/Settings/CurrencySettings'

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
                path: '/stats',
                name: 'stats',
                component: DashBoardStats
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
                        name:'currency_settings',
                        path: 'currency-settings',
                        component: CurrencySettings
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
                path: 'payment_otions',
                name: 'payment_options',
                component: FormPaymentSettings
            },
            {
                path: 'form-builder',
                name: 'edit_form',
                component: FormBuilder
            },
            {
                path: 'design_options',
                name: 'design_options',
                component: EditSingleForm
            },
            {
                path: 'email_settings',
                name: 'email_settings',
                component: EditSingleForm
            }
        ]
    }
];
