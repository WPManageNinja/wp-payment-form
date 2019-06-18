<?php
if (!$submission->subscriptions) {
    return '';
}
$currencySetting = \WPPayForm\Classes\GeneralSettings::getGlobalCurrencySettings($submission->form_id);
$currencySetting['currency_sign'] = \WPPayForm\Classes\GeneralSettings::getCurrencySymbol($submission->currency);
?>
<table class="table wpf_subscriptions_items_table wpf_table table_bordered">
    <thead>
        <th><?php _e('Subscription', 'wppayform'); ?></th>
        <th><?php _e('Initial Amount', 'wppayform'); ?></th>
        <th><?php _e('Times Billed', 'wppayform'); ?></th>
        <th><?php _e('Status', 'wppayform'); ?></th>
    </thead>
    <tbody>
    <?php $subTotal = 0; ?>
    <?php foreach ($submission->subscriptions as $subscription): ?>
        <tr>
            <td>
                <?php echo $subscription->item_name . ' ('.$subscription->plan_name.')'; ?>
                <p style="margin: 0; padding: 5px 0 0; font-size: 12px;">
                    <?php echo wpPayFormFormattedMoney($subscription->recurring_amount, $currencySetting); ?> / <?php echo $subscription->billing_interval; ?>
                </p>
            </td>
            <td>
                <?php echo wpPayFormFormattedMoney($subscription->initial_amount, $currencySetting); ?>
            </td>
            <td><?php echo $subscription->bill_count; ?> / <?php echo ($subscription->bill_times) ? $subscription->bill_times : __('Until cancelled', 'wppayform'); ?> </td>
            <td><?php echo $subscription->status; ?></td>
        </tr>
        <?php
    endforeach; ?>
    </tbody>
</table>

<?php if(!empty($load_table_css)): ?>
    <style type="text/css">
        .wpf_table {
            empty-cells: show;
            font-size: 14px;
            border: 1px solid #cbcbcb
        }
        .wpf_table td,.wpf_table th {
            border-left: 1px solid #cbcbcb;
            border-width: 0 0 0 1px;
            font-size: inherit;
            margin: 0;
            overflow: visible;
            padding: .5em 1em
        }
        .wpf_table td:first-child,.wpf_table th:first-child {
            border-left-width: 0
        }
        .wpf_table thead {
            background-color: #e3e8ee;
            color: #000;
            text-align: left;
            vertical-align: bottom
        }
        .wpf_table td {
            background-color: transparent
        }
        .wpf_table tfoot {
            border-top: 1px solid #cbcbcb;
        }
    </style>
<?php endif; ?>
