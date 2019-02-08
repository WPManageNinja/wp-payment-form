<div class="wpf_submission_header">
    <?php if($submission->payment_total): ?>
    <p class="wpf_submission_message">
        <?php _e('Thanks for your payment. Your payment has been received.', 'wppayform'); ?>
    </p>
    <?php else: ?>
    <p class="wpf_submission_message">
        <?php _e('Thanks for your submission. Here are the details of your submission:', 'wppayform'); ?>
    </p>
    <?php endif; ?>
</div>