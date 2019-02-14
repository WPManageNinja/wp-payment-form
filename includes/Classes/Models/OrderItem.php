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
        $orderItems = wpPayFormDB()->table('wpf_order_items')->where('submission_id', $submissionId)->get();
        return apply_filters('wppayform/order_items', $orderItems, $submissionId);
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