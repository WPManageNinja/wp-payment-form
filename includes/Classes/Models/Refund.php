<?php

namespace WPPayForm\Classes\Models;


if (!defined('ABSPATH')) {
    exit;
}

/**
 *  Refund Model
 * @since 2.0.0
 */
class Refund
{
    public function create($item)
    {
        $item['transaction_type'] = 'refund';

        return wpPayFormDB()
            ->table('wpf_order_transactions')
            ->insert($item);
    }

    public function getRefunds($submissionId)
    {
        $refunds = wpPayFormDB()->table('wpf_order_transactions')
            ->where('submission_id', $submissionId)
            ->where('transaction_type', 'refund')
            ->get();

        return apply_filters('wppayform/entry_refunds', $refunds, $submissionId);
    }

    public function getRefundTotal($submissionId)
    {
        $refunds = wpPayFormDB()->table('wpf_order_transactions')
            ->select(['id', 'payment_total'])
            ->where('submission_id', $submissionId)
            ->where('transaction_type', 'refund')
            ->get();

        $refundTotal = 0;
        foreach ($refunds as $refund) {
            $refundTotal += $refund->payment_total;
        }

        return $refundTotal;
    }

    public function getRefund($refundId)
    {
        return wpPayFormDB()->table('wpf_order_transactions')
            ->where('id', $refundId)
            ->where('transaction_type', 'refund')
            ->first();
    }

    public function update($refundId, $data)
    {
        $data['updated_at'] = gmdate('Y-m-d H:i:s');
        return wpPayFormDB()
            ->table('wpf_order_transactions')
            ->where('id', $refundId)
            ->update($data);
    }

    public function getLatestRefund($submissionId)
    {
        return wpPayFormDB()->table('wpf_order_transactions')
            ->where('submission_id', $submissionId)
            ->where('transaction_type', 'refund')
            ->orderBy('id', 'DESC')
            ->first();
    }

    public function getRefundByChargeId($chargeId)
    {
        return wpPayFormDB()->table('wpf_order_transactions')
            ->where('charge_id', $chargeId)
            ->where('transaction_type', 'refund')
            ->first();
    }


}