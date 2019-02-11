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
        return wpPayformDB()->table('wpf_order_items')->insert($item);
    }

    public function getOrderItems($submissionId)
    {
        $orderItems = wpPayformDB()->table('wpf_order_items')->where('submission_id', $submissionId)->first();
        return apply_filters('wppayform/order_items', $orderItems, $submissionId);
    }
}