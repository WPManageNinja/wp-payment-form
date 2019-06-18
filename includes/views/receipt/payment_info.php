<div class="wpf_payment_info">
    <div class="wpf_payment_info_item wpf_payment_info_item_order_id">
        <?php if($submission->order_items): ?>
        <div class="wpf_item_heading"><?php _e('Order ID:');?></div>
        <?php else: ?>
        <div class="wpf_item_heading"><?php _e('Submission ID:');?></div>
        <?php endif; ?>
        <div class="wpf_item_value">#<?php echo $submission->id; ?></div>
    </div>
    <div class="wpf_payment_info_item wpf_payment_info_item_date">
        <div class="wpf_item_heading"><?php _e('Date:');?></div>
        <div class="wpf_item_value"><?php echo date(get_option( 'date_format' ), strtotime($submission->created_at)); ?></div>
    </div>
    <?php if($submission->payment_total): ?>
    <?php
        $currencySetting = \WPPayForm\Classes\GeneralSettings::getGlobalCurrencySettings($submission->form_id);
        $currencySetting['currency_sign'] = \WPPayForm\Classes\GeneralSettings::getCurrencySymbol($submission->currency);
    ?>
    <div class="wpf_payment_info_item wpf_payment_info_item_total">
        <div class="wpf_item_heading"><?php _e('Total:');?></div>
        <div class="wpf_item_value"><?php echo wpPayFormFormattedMoney($submission->payment_total, $currencySetting); ?></div>
    </div>
    <?php endif; ?>
    <?php if($submission->payment_method): ?>
        <div class="wpf_payment_info_item wpf_payment_info_item_payment_method">
            <div class="wpf_item_heading"><?php _e('Payment Method:');?></div>
            <div class="wpf_item_value"><?php echo ucfirst($submission->payment_method); ?></div>
        </div>
    <?php endif; ?>
    <?php if($submission->payment_status && $submission->order_items): ?>
        <div class="wpf_payment_info_item wpf_payment_info_item_payment_status">
            <div class="wpf_item_heading"><?php _e('Payment Status:');?></div>
            <div class="wpf_item_value"><?php echo ucfirst($submission->payment_status); ?></div>
        </div>
    <?php endif; ?>
</div>