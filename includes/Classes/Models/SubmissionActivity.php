<?php

namespace WPPayForm\Classes\Models;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manage Submission
 * @since 1.0.0
 */
class SubmissionActivity
{
    public static function getSubmissionActivity($submissionId)
    {
        $activities = wpPayFormDB()->table('wpf_submission_activities')
            ->where('submission_id', $submissionId)
            ->orderBy('id', 'DESC')
            ->get();
        foreach ($activities as $activitiy) {
            if($activitiy->created_by_user_id) {
                $activitiy->user_profile_url = get_edit_user_link($activitiy->created_by_user_id);
            }
        }
        
        return apply_filters('wppayform/entry_activities', $activities, $submissionId);
    }

    public static function createActivity($data)
    {
        $data['created_at'] = gmdate('Y-m-d H:i:s');
        $data['updated_at'] = gmdate('Y-m-d H:i:s');

        return wpPayFormDB()->table('wpf_submission_activities')
            ->insert($data);
    }
}