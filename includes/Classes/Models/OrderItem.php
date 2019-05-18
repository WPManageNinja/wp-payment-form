<?php

namespace WPPayForm\Classes\Models;


if (!defined('ABSPATH')) {
    exit;
}

/**
 * Order Items Model
 * @since 1.0.0
 */
class OrderItem
{
    public function create($item)
    {
        return wpPayFormDB()->table('wpf_order_items')->insert($item);
    }

    public function getOrderItems($submissionId)
    {
        $orderItems = wpPayFormDB()->table('wpf_order_items')
            ->where('submission_id', $submissionId)
            ->get();
        foreach ($orderItems as $orderItem) {
            if($orderItem->type == 'tax_line') {
                $orderItem->quantity = $orderItem->line_total / $orderItem->item_price;
            }
        }
        return apply_filters('wppayform/order_items', $orderItems, $submissionId);
    }

    public function getTaxOrderItems($submissionId)
    {
        $orderItems = wpPayFormDB()->table('wpf_order_items')
            ->where('submission_id', $submissionId)
            ->where('type', 'tax_line')
            ->get();

        foreach ($orderItems as $orderItem) {
            $orderItem->quantity = $orderItem->line_total / $orderItem->item_price;
            $orderItem->taxRate = number_format(($orderItem->line_total / $orderItem->item_price) * 100, 2);
        }

        return apply_filters('wppayform/tax_items', $orderItems, $submissionId);
    }

    public function getSingleOrderItems($submissionId)
    {
        $orderItems = wpPayFormDB()->table('wpf_order_items')
            ->where('submission_id', $submissionId)
            ->where('type', 'single')
            ->get();
        return apply_filters('wppayform/order_items', $orderItems, $submissionId);
    }
}