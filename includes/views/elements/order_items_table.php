<?php
if (!$submission->order_items) {
    return '';
}

$currencySetting = \WPPayForm\Classes\GeneralSettings::getGlobalCurrencySettings($submission->form_id);
$currencySetting['currency_sign'] = \WPPayForm\Classes\GeneralSettings::getCurrencySymbol($submission->currency);
?>

<?php if ($submission->payment_total && $submission->payment_total > 0) {?>
    <table class="table wpf_order_items_table wpf_table table_bordered">
        <thead>
        <th><?php _e('Item', 'wppayform'); ?></th>
        <th><?php _e('Quantity', 'wppayform'); ?></th>
        <th><?php _e('Price', 'wppayform'); ?></th>
        <th><?php _e('Line Total', 'wppayform'); ?></th>
        </thead>
        <tbody>
        <?php $subTotal = 0; ?>
        <?php foreach ($submission->order_items as $order_item) {
            if ($order_item->line_total) :?>
            <tr>
                <td><?php echo $order_item->item_name; ?></td>
                <td><?php echo $order_item->quantity; ?></td>
                <td><?php echo wpPayFormFormattedMoney($order_item->item_price, $currencySetting); ?></td>
                <td><?php echo wpPayFormFormattedMoney($order_item->line_total, $currencySetting); ?></td>
            </tr>
                <?php
                $subTotal += $order_item->line_total;
            endif;
        };
        ?>
        </tbody>
        <tfoot>
        <?php if ($submission->tax_items) : ?>
            <tr class="wpf_sub_total_row">
                <th style="text-align: right" colspan="3"><?php _e('Sub Total', 'wppayform'); ?></th>
                <td><?php echo wpPayFormFormattedMoney($subTotal, $currencySetting); ?></td>
            </tr>
            <?php foreach ($submission->tax_items as $tax_item) : ?>
                <tr class="wpf_sub_total_row">
                    <td style="text-align: right" colspan="3"><?php echo $tax_item->item_name ?></td>
                    <td><?php echo wpPayFormFormattedMoney($tax_item->line_total, $currencySetting); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        <tr class="wpf_total_row">
            <th style="text-align: right" colspan="3"><?php _e('Sub-Total', 'wppayform'); ?></th>
            <td><?php echo wpPayFormFormattedMoney($submission->payment_total, $currencySetting); ?></td>
        </tr>
        <?php $discountTotal = 0;
        if (isset($submission->discounts['applied'])) : ?>
            <?php
            foreach ($submission->discounts['applied'] as $discount) :
                $discountTotal += intval($discount->line_total);
                ?>
                <tr class="wpf_discount_row">
                    <th style="text-align: right" colspan="3"><?php _e('Discounts ('. $discount->item_name . ' )', 'wppayform'); ?></th>
                    <td><?php echo '-' . wpPayFormFormattedMoney($discount->line_total, $currencySetting); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
            <tr class="wpf_total_payment_row">
                <th style="text-align: right" colspan="3"><?php _e('Total', 'wppayform'); ?></th>
                <td><?php echo  wpPayFormFormattedMoney(intval($submission->payment_total - $discountTotal), $currencySetting); ?></td>
            </tr>
        </tfoot>
    </table>
<?php } else { ?>
    <div style="color: red; background: #f7fafc; padding: 10px; font-size:13px; margin-bottom: 12px;">
        Please reload this receipt page after a few minutes,
        Payment items will be updated when paid.
    </div>
<?php } ?>