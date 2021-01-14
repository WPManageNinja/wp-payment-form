<?php

if (!$submission->transactions) {
    return '';
}

$ids = array();
foreach ($submission->transactions as $transaction) {
    if ($transaction->charge_id) {
        array_push($ids, $transaction->charge_id);
    }
};
?>
<span class="wpf_transaction_id"><?php echo implode(', ', $ids); ?></span>