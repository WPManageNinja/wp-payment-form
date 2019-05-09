<?php
if(!$submission->order_items) {
    return '';
}
$currencySetting = \WPPayForm\Classes\GeneralSettings::getGlobalCurrencySettings($submission->form_id);
$currencySetting['currency_sign'] = \WPPayForm\Classes\GeneralSettings::getCurrencySymbol($submission->currency);
?>
<table class="table wpf_order_items_table wpf_table table_bordered">
    <thead>
    <th><?php _e('Item', 'wppayform'); ?></th>
    <th><?php _e('Quantity', 'wppayform'); ?></th>
    <th><?php _e('Price', 'wppayform'); ?></th>
    <th><?php _e('Line Total', 'wppayform'); ?></th>
    </thead>
    <tbody>
    <?php foreach ($submission->order_items as $order_item): ?>
        <tr>
            <td><?php echo $order_item->item_name; ?></td>
            <td><?php echo $order_item->quantity; ?></td>
            <td><?php echo wpPayFormFormattedMoney($order_item->item_price, $currencySetting); ?></td>
            <td><?php echo wpPayFormFormattedMoney($order_item->line_total, $currencySetting); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
    <tr class="wpf_total_row">
        <th style="text-align: right" colspan="3"><?php _e('Total', 'wppayform'); ?></th>
        <td><?php echo wpPayFormFormattedMoney($submission->payment_total, $currencySetting); ?></td>
    </tr>
    </tfoot>
</table>