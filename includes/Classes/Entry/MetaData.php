<?php

namespace WPPayForm\Classes\Entry;


use WPPayForm\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Entry MetaDat
 * @since 1.0.0
 */
class MetaData
{
    protected $entry;
    protected $postId;
    protected $userId;
    protected $queryVars = null;
    protected $post;
    protected $user;

    public function __construct($entry)
    {
        $this->entry = $entry;
        $this->postId = $entry->getRawInput('__wpf_current_page_id');
        $this->userId = $entry->user_id;
    }

    public function getWPValues($key)
    {
        switch ($key) {
            case 'post_id':
                return $this->postId;
            case 'post_title':
                return get_the_title($this->postId);
            case 'post_url':
                return get_the_permalink($this->postId);
            case 'post_author':
                $post = $this->getPost();
                if (!$post) {
                    return '';
                }
                return get_the_author_meta('display_name', $post->post_author);
            case 'post_author_email':
                $post = $this->getPost();
                return get_the_author_meta('user_email', $post->post_author);
            case 'user_id':
                return $this->userId;
            case 'user_first_name':
                $user = $this->getUser();
                if (!$user) {
                    return '';
                }
                return $user->user_firstname;
            case 'user_last_name':
                $user = $this->getUser();
                if (!$user) {
                    return '';
                }
                return $user->user_lastname;
            case 'user_display_name':
                $user = $this->getUser();
                if (!$user) {
                    return '';
                }
                return $user->display_name;
            case 'user_email':
                $user = $this->getUser();
                if (!$user) {
                    return '';
                }
                return $user->user_email;
            case 'user_url':
                $user = $this->getUser();
                if (!$user) {
                    return '';
                }
                return $user->user_url;
            case 'site_title':
                return get_bloginfo('name');
            case 'site_url':
                return get_bloginfo('url');
            case 'admin_email':
                return get_bloginfo('admin_email');
            default:
                return '';
                break;
        }
    }

    public function getPostMeta($key)
    {
        $meta = get_post_meta($this->postId, $key, true);
        if(is_array($meta)) {
            return implode(', ', $meta);
        }
        return $meta;
    }

    public function getuserMeta($key)
    {
        $meta = get_user_meta($this->userId, $key, true);
        if(is_array($meta)) {
            return implode(', ', $meta);
        }
        return $meta;
    }

    public function getFromUrlQuery($key)
    {
        if($this->queryVars == null) {
            $submissionUrl = $this->entry->getRawInput('__wpf_current_url');
            $parts = parse_url($submissionUrl);
            if (isset($parts['query'])) {
                parse_str($parts['query'], $query);
                $this->queryVars = $query;
            }
        }

        if(isset($this->queryVars[$key])) {
            return esc_attr($this->queryVars[$key]);
        }
        return '';
    }

    public function getOtherData($key)
    {
        if($key == 'date') {
            $dateFormat = get_option('date_format');
            return gmdate($dateFormat, time());
        }

        if($key == 'time') {
            $dateFormat = get_option('time_format');
            return gmdate($dateFormat, time());
        }
        if($key == 'user_ip') {
            return $this->entry->ip_address;
        }

        return '';
    }

    protected function getPost()
    {
        if ($this->post) {
            return $this->post;
        }
        $this->post = get_post($this->postId);
        return $this->post;
    }

    protected function getUser()
    {
        if ($this->user) {
            return $this->user;
        }
        $this->user = get_user_by('ID', $this->userId);
        return $this->user;
    }
}