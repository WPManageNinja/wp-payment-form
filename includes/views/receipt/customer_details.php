<div class="wpf_customer_details">
    <h4><?php _e('Submitter Details', 'wppayform'); ?></h4>
    <table class="table wpf_table table_bordered">
        <tbody>
            <tr>
                <td>Name</td>
                <td><?php echo $submission->customer_name; ?></td>
            </tr>
            <tr>
                <td>Email</td>
                <td><?php echo $submission->customer_email; ?></td>
            </tr>
        </tbody>
    </table>
    <?php if($billingDetails = \WPPayForm\Classes\ArrayHelper::get($submission->parsedData, '__checkout_billing_address_details.value')): ?>
        <h5><?php _e('Billing address') ?></h5>
        <div class="wpf_address_details"><?php echo $billingDetails; ?></div>
    <?php endif; ?>

    <?php if($shippingDetails = \WPPayForm\Classes\ArrayHelper::get($submission->parsedData, '__checkout_shipping_address_details.value')): ?>
        <h5><?php _e('Shipping address') ?></h5>
        <div class="wpf_address_details"><?php echo $shippingDetails; ?></div>
    <?php endif; ?>
</div>