<template>
    <div v-if="subscriptions && subscriptions.length" class="entry_info_box entry_subscription_items">
        <div class="entry_info_header">
            <div class="info_box_header">Subscriptions (Recurring Payments)</div>
        </div>
        <div class="entry_info_body">

            <div v-for="(subscription, subscriptionIndex) in subscriptions" class="payment_header subscripton_item">
                <div class="payment_head_top">
                    <div class="payment_header_left">
                        <p class="head_small_title">{{subscription.plan_name}} <span class="mini_title">({{subscription.item_name}})</span></p>

                        <div class="head_payment_amount">
                            <span class="pay_amount" v-html="getFormattedMoney(subscription.recurring_amount)"></span>
                            <span>/month</span>
                            <span :class="'wpf_paystatus_badge wpf_pay_status_'+subscription.status">
                                <i :class="getPaymentStatusIcon(subscription.status)"></i> {{subscription.status}}
                            </span>
                        </div>

                    </div>
                    <div class="payment_header_right">
                        <a v-show="getSubscriptionUrl(subscription)" rel="noopener" target="_blank" :href="getSubscriptionUrl(subscription)" class="el-button el-button--default el-button--mini">
                            View on {{payment_method}}
                        </a>
                        <p style="margin-top: 0px">
                            <span>Total Payment Recieved: </span><span class="table_payment_amount" v-html="subscriptionTotal(subscription.related_payments)"></span>
                        </p>
                        <p style="margin-top: 0px" v-html="subscriptionHumanText(subscription.original_plan)"></p>
                    </div>
                </div>
                <div class="payment_head_bottom wpf_entry_order_items">
                    <h3>Payments</h3>
                    <table class="wp-list-table widefat table table-bordered striped">
                        <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Date (GMT)</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <template v-for="payment in subscription.related_payments">
                            <tr v-for="lineItem in getLineItems(payment)">
                                <td>
                                    <span class="table_payment_amount" v-html="getFormattedMoney(lineItem.amount)"></span>
                                    <span class="payment_currency">{{payment.currency}}</span>
                                    <span class="wpf_paystatus_badge wpf_pay_status_active">{{payment.status}}</span>
                                </td>
                                <td>
                                    {{lineItem.description}}
                                </td>
                                <td>
                                    {{payment.created_at | dateFormat('MMM DD, YYYY h:mm:ss a') }}
                                </td>
                                <td>
                                    <a class="el-button el-button--primary el-button--mini" v-if="getChargeUrl(payment)" target="_blank" rel="noopener" :href="getChargeUrl(payment)">
                                        <i class="el-icon-view"></i>
                                    </a>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</template>

<script type="text/babel">
    import fromatPrice from '../../../../common/formatPrice';
    import each from 'lodash/each';

    export default {
        name: 'subscription_payments',
        props: ['subscriptions', 'currencySetting', 'payment_mode', 'payment_method'],
        methods: {
            subscriptionTotal(payments) {
                let total = 0;
                each(payments, (payment) => {
                    if(payment.status == 'paid') {
                        total += payment.payment_total;
                    }
                });
                return this.getFormattedMoney(total);
            },
            getFormattedMoney(amount) {
                if (!amount) {
                    return 'n/a';
                }
                return fromatPrice(amount, this.currencySetting);
            },
            getPaymentStatusIcon(status) {
                if (status == 'pending') {
                    return 'el-icon-time';
                } else if (status == 'active') {
                    return 'el-icon-check';
                } else if (status == 'failed') {
                    return 'el-icon-error';
                } else if (status == 'refunded') {
                    return 'el-icon-warning';
                }
                return '';
            },
            getLineItems(payment) {
                if(payment.payment_method == 'stripe') {
                    return payment.payment_note.lines.data;
                }
                return [];
            },
            getChargeUrl(payment) {
                if(payment.payment_method == 'stripe' && payment.charge_id) {
                    if(this.payment_mode == 'test') {
                        return 'https://dashboard.stripe.com/test/payments/' + payment.charge_id;
                    }
                    return 'https://dashboard.stripe.com/payments/' + payment.charge_id;
                }
                return '';
            },
            getSubscriptionUrl(subscription) {
                if(this.payment_method == 'stripe') {
                    if(this.payment_mode == 'test') {
                        return 'https://dashboard.stripe.com/test/subscriptions/' + subscription.vendor_subscriptipn_id;
                    }
                    return 'https://dashboard.stripe.com/payments/' + subscription.vendor_subscriptipn_id;
                }
            },
            subscriptionHumanText(plan) {
                if(parseInt(plan.bill_times)) {
                    return 'Customer will be billed '+plan.bill_times+' times in total';
                } else {
                    return 'Customer will be billed untill cancelled';
                }
            }
        }
    }
</script>