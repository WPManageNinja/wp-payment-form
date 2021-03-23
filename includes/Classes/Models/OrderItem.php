<?php
namespace WPPayForm\Classes\Models;

use WPPayForm\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Order Items Model
 * @since 1.0.0
 */
class OrderItem
{

    protected $metaGroup = 'wpf_order_items';

    public function create($item)
    {
        $insertItem = ArrayHelper::only($item, array(
            'form_id',
            'submission_id',
            'type',
            'parent_holder',
            'billing_interval',
            'item_name',
            'quantity',
            'item_price',
            'line_total',
            'created_at',
            'updated_at'
        ));

        $insertId = wpPayFormDB()->table('wpf_order_items')->insert($insertItem);
        if ($metas = ArrayHelper::get($item, 'meta')) {
            foreach ($metas as $metaKey => $value) {
                $this->updateMeta($insertId, $metaKey, $value);
            }
        }
        return $insertId;
    }

    public function getOrderItems($submissionId)
    {
        $orderItems = wpPayFormDB()->table('wpf_order_items')
            ->where('submission_id', $submissionId)
            ->where('type', '!=', 'discount')
            ->get();
        foreach ($orderItems as $orderItem) {
            if ($orderItem->type == 'tax_line') {
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
            ->whereIn('type', ['single','signup_fee'])
            ->get();

        return apply_filters('wppayform/order_items', $orderItems, $submissionId);
    }

    public function updateMeta($optionId, $key, $value)
    {
        $value = maybe_serialize($value);
        $exists = wpFluent()->table('wpf_meta')
                    ->where('meta_group', $this->metaGroup)
                    ->where('meta_key', $key)
                    ->where('option_id', $optionId)
                    ->first();

        if ($exists) {
            wpFluent()->table('wpf_meta')
                ->where('id', $exists->id)
                    ->update([
                        'meta_group' => $this->metaGroup,
                        'option_id' => $optionId,
                        'meta_key' => $key,
                        'meta_value' => $value,
                        'updated_at' => current_time('mysql')
                    ]);
            return $exists->id;
        }

        return wpFluent()->table('wpf_meta')->insert([
            'meta_group' => $this->metaGroup,
            'option_id' => $optionId,
            'meta_key' => $key,
            'meta_value' => $value,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ]);
    }

    public function getMetas($optionId)
    {
        $metas = wpFluent()->table('wpf_meta')
            ->where('meta_group', $this->metaGroup)
            ->where('option_id', $optionId)
            ->get();
        $formatted = array();
        foreach ($metas as $meta) {
            $formatted[$meta->meta_key] = maybe_unserialize($meta->meta_value);
        }
        return (object) $formatted;
    }

    public function getDiscountItems($submissionId)
    {
        $discounts =  wpFluent()->table('wpf_order_items')
            ->where('submission_id', intval($submissionId))
            ->where('type', 'discount')
            ->get();
        return $discounts;
    }
}
