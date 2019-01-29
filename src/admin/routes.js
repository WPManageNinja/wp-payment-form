const DashBoardStats = require('./Components/Dashboard');
const GlobalView = require('./Components/Global/index');
const AllForms = require('./Components/Forms/AllForms');
const EditFormView = require('./Components/Form/index');
const EditSingleForm = require('./Components/Form/EditForm');
const FormPaymentSettings = require('./Components/Form/PaymentSettings');
const FormBuilder = require('./Components/Form/FormBuilder');
import Entries from './Components/Entries/Entries';

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
                path: '/stats',
                name: 'stats',
                component: DashBoardStats
            },

            {
                path: '/settings',
                name: 'settings',
                component: DashBoardStats
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
        children: [
            {
                path: 'payment_otions',
                name: 'payment_options',
                component: FormPaymentSettings
            },
            {
                path: 'custom_fields',
                name: 'custom_fields',
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
            },
            {
                path: 'entries',
                name: 'entries',
                component: Entries
            }
        ]
    }
];