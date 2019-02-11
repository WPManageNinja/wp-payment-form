<?php

namespace WPPayForm\Classes\Models;


if (!defined('ABSPATH')) {
    exit;
}

/**
 *  Transaction Model
 * @since 1.0.0
 */
class Transaction
{
    public function create($item)
    {
        return wpPayformDB()->table('wpf_order_transactions')->insert($item);
    }

    public function getTransactions($submissionId)
    {
        $transactions = wpPayformDB()->table('wpf_order_transactions')
                            ->where('submission_id', $submissionId)
                            ->get();

        return apply_filters('wppayform/entry_transactions', $transactions, $submissionId);
    }

    public function getTransaction($transactionId)
    {
        return wpPayformDB()->table('wpf_order_transactions')->where('id', $transactionId)->first();
    }

    public function update($transactionId, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return wpPayformDB()->table('wpf_order_transactions')->where('id', $transactionId)->update($data);
    }
}