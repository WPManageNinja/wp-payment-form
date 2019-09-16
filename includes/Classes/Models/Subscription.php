<?php

namespace WPPayForm\Classes\Models;


if (!defined('ABSPATH')) {
    exit;
}

/**
 * Subscriptions Model
 * @since 1.2.0
 */
class Subscription
{
    protected $dbName = 'wpf_subscriptions';

    public function create($item)
    {
        return wpPayFormDB()->table($this->dbName)
            ->insert($item);
    }

    public function getSubscriptions($submissionId)
    {
        $subscriptions = wpPayFormDB()->table($this->dbName)
            ->where('submission_id', $submissionId)
            ->get();
        foreach ($subscriptions as $subscription) {
            $subscription->original_plan = maybe_unserialize($subscription->original_plan);
            $subscription->vendor_response = maybe_unserialize($subscription->vendor_response);
        }
        return $subscriptions;
    }

    public function getSubscription($id)
    {
        $subscription = wpPayFormDB()->table($this->dbName)
            ->where('id', $id)
            ->first();
        if ($subscription) {
            $subscription->original_plan = maybe_unserialize($subscription->original_plan);
        }

        return $subscription;
    }

    public function update($id, $data)
    {
        $data['updated_at'] = gmdate('Y-m-d H:i:s');
        return wpPayFormDB()->table($this->dbName)
            ->where('id', $id)
            ->update($data);
    }

    public function updateBySubmissionId($submissionId, $data)
    {
        return wpPayFormDB()->table($this->dbName)
                    ->where('submission_id', $submissionId)
                    ->update($data);

    }

    public function updateMeta($optionId, $key, $value)
    {
        $value = maybe_serialize($value);
        $exists = wpFluent()->table('wpf_meta')
            ->where('meta_group', $this->dbName)
            ->where('meta_key', $key)
            ->where('option_id', $optionId)
            ->first();

        if ($exists) {
            wpFluent()->table('wpf_meta')
                ->where('id', $exists->id)
                ->update([
                    'meta_group' => $this->dbName,
                    'option_id'  => $optionId,
                    'meta_key'   => $key,
                    'meta_value' => $value,
                    'updated_at' => gmdate('Y-m-d H:i:s')
                ]);
            return $exists->id;
        }

        return wpFluent()->table('wpf_meta')->insert([
            'meta_group' => $this->dbName,
            'option_id'  => $optionId,
            'meta_key'   => $key,
            'meta_value' => $value,
            'created_at' => gmdate('Y-m-d H:i:s'),
            'updated_at' => gmdate('Y-m-d H:i:s')
        ]);

    }

    public function getMetas($optionId)
    {
        $metas = wpFluent()->table('wpf_meta')
            ->where('meta_group', $this->dbName)
            ->where('option_id', $optionId)
            ->get();
        $formatted = array();
        foreach ($metas as $meta) {
            $formatted[$meta->meta_key] = maybe_unserialize($meta->meta_value);
        }
        return (object)$formatted;
    }

    public function getIntentedSubscriptions($submissionId)
    {
        $subscriptions = wpPayFormDB()->table($this->dbName)
            ->where('submission_id', $submissionId)
            ->where('status', 'intented')
            ->get();
        foreach ($subscriptions as $subscription) {
            $subscription->original_plan = maybe_unserialize($subscription->original_plan);
            $subscription->vendor_response = maybe_unserialize($subscription->vendor_response);
        }
        return $subscriptions;
    }
}