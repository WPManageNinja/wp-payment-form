<?php

namespace WPPayForm\Classes\Models;


if (!defined('ABSPATH')) {
    exit;
}

/**
 *  SubscriptionTransaction Model
 * @since 1.0.0
 */
class SubscriptionTransaction
{
    public function create($item)
    {
        $item['transaction_type'] = 'subscription';
        return wpPayFormDB()
            ->table('wpf_order_transactions')
            ->insert($item);
    }

    public function maybeInsertCharge($item)
    {
        $exists = wpPayFormDB()
            ->table('wpf_order_transactions')
            ->where('transaction_type', 'subscription')
            ->where('submission_id', $item->submission_id)
            ->where('charge_id', $item->charge_id)
            ->where('payment_method', $item->payment_method)
            ->first();
        if($exists) {
            $this->update($exists->id, $item);
            return $exists->id;
        }
        $id =  $this->create($item);
        // We want to update the total amount here
        $parentSubscription = wpFluent()->table('wpf_subscriptions')
                                ->where('id', $item['submission_id'])
                                ->first();

        if($parentSubscription) {
            wpFluent()->table('wpf_subscriptions')
                ->where('id', $parentSubscription->id)
                ->update([
                    'payment_total' => intval($parentSubscription->payment_total) +  $item['payment_total'],
                    'updated_at' => gmdate('Y-m-d H:i:s')
                ]);
        }

        return $id;
    }

    public function getSubscriptionTransactions($subscriptionId)
    {
        $transactions = wpPayFormDB()->table('wpf_order_transactions')
                            ->where('submission_id', $subscriptionId)
                            ->where('transaction_type', 'subscription')
                            ->get();

        foreach ($transactions as $transaction) {
            $transaction->payment_note = maybe_unserialize($transaction->payment_note);
        }

        return apply_filters('wppayform/subscription_transactions', $transactions, $subscriptionId);
    }

    public function getTransaction($transactionId)
    {
        return wpPayFormDB()->table('wpf_order_transactions')
            ->where('id', $transactionId)
            ->where('transaction_type', 'subscription')
            ->first();
    }

    public function update($transactionId, $data)
    {
        $data['updated_at'] = gmdate('Y-m-d H:i:s');
        $data['transaction_type'] = 'subscription';
        return wpPayFormDB()->table('wpf_order_transactions')->where('id', $transactionId)->update($data);
    }
}