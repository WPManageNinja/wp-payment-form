<?php

namespace WPPayForm\Classes\Models;

/**
 * Base Model Class
 * @since 1.0.0
 */
class Model
{

    public $metaGroup = '';

    public function getMeta($submissionId, $metaKey, $default = '')
    {
        $exist = wpPayFormDB()->table('wpf_meta')
            ->where('meta_group', $this->metaGroup)
            ->where('option_id', $submissionId)
            ->where('meta_key', $metaKey)
            ->first();
        if($exist) {
            $value =  maybe_unserialize($exist->meta_value);
            if($value) {
                return $value;
            }
        }

        return $default;
    }

    public function updateMeta($submissionId, $metaKey, $metaValue)
    {
        $exist = wpPayFormDB()->table('wpf_meta')
            ->where('meta_group', $this->metaGroup)
            ->where('option_id', $submissionId)
            ->where('meta_key', $metaKey)
            ->first();

        if($exist) {
             wpPayFormDB()->table('wpf_meta')
                ->where('id', $exist->id)
                ->update([
                    'meta_value' => maybe_serialize($metaValue),
                    'updated_at' => gmdate('Y-m-d H:i:s')
                ]);
        } else {
            wpPayFormDB()->table('wpf_meta')
                ->insert([
                    'meta_key' => $metaKey,
                    'option_id' => $submissionId,
                    'meta_group' => $this->metaGroup,
                    'meta_value' => maybe_serialize($metaValue),
                    'updated_at' => gmdate('Y-m-d H:i:s'),
                    'created_at' => gmdate('Y-m-d H:i:s'),
                ]);
        }
    }


}