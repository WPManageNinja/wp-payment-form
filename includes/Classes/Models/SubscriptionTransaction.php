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
        if(!isset($item['created_at'])) {
            $item['created_at'] = gmdate('Y-m-d H:i:s');
            $item['updated_at'] = gmdate('Y-m-d H:i:s');
        }
        return wpPayFormDB()
            ->table('wpf_order_transactions')
            ->insert($item);
    }

    public function maybeInsertCharge($item)
    {
        $exists = wpPayFormDB()
            ->table('wpf_order_transactions')
            ->where('transaction_type', 'subscription')
            ->where('submission_id', $item['submission_id'])
            ->where('subscription_id', $item['subscription_id'])
            ->where('charge_id', $item['charge_id'])
            ->where('payment_method', $item['payment_method'])
            ->first();

        if($exists) {
            $this->update($exists->id, $item);
            return $exists->id;
        }
        $id =  $this->create($item);
        // We want to update the total amount here
        $parentSubscription = wpFluent()->table('wpf_subscriptions')
                                ->where('id', $item['subscription_id'])
                                ->first();

        // Let's count the total subscription payment
        if($parentSubscription) {
            wpFluent()->table('wpf_subscriptions')
                ->where('id', $parentSubscription->id)
                ->update([
                    'bill_count' => $this->getPaymentCounts($parentSubscription->id),
                    'payment_total' => $this->getPaymentTotal($parentSubscription->id),
                    'updated_at' => gmdate('Y-m-d H:i:s')
                ]);
        }

        return $id;
    }

    public function getSubscriptionTransactions($subscriptionId)
    {
        $transactions = wpPayFormDB()->table('wpf_order_transactions')
                            ->where('subscription_id', $subscriptionId)
                            ->get();

        foreach ($transactions as $transaction) {
            $transaction->payment_note = maybe_unserialize($transaction->payment_note);
            $transaction->items = apply_filters('wppayform/subscription_items_'.$transaction->payment_method, [], $transaction);
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

    public function getPaymentCounts($subscriptionId, $paymentMethod = false)
    {
        $query = wpPayFormDB()
            ->select(['id'])
            ->table('wpf_order_transactions')
            ->where('transaction_type', 'subscription')
            ->where('subscription_id', $subscriptionId);
        if($paymentMethod) {
            $query = $query->where('payment_method', $paymentMethod);
        }

        $totalPayments = $query->get();
        return count($totalPayments);
    }

    public function getPaymentTotal($subscriptionId, $paymentMethod = false)
    {
        $query = wpPayFormDB()
            ->select(['id', 'payment_total'])
            ->table('wpf_order_transactions')
            ->where('transaction_type', 'subscription')
            ->where('subscription_id', $subscriptionId);
        if($paymentMethod) {
            $query = $query->where('payment_method', $paymentMethod);
        }
        $payments = $query->get();

        $paymentTotal = 0;

        foreach ($payments as $payment) {
            $paymentTotal += $payment->payment_total;
        }
        return $paymentTotal;
    }
}