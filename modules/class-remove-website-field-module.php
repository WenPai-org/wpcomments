<?php

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

class Remove_Website_Field_Module implements WPComments_Module_Interface {
    
    public function id(): string {
        return 'remove_website_field';
    }
    
    public function is_enabled(array $settings): bool {
        return !empty($settings['remove_website_field']);
    }
    
    public function register(): void {
        $this->init();
    }
    
    public function get_name(): string {
        return __('移除网站字段', 'wpcomments');
    }
    
    public function get_description(): string {
        return __('从评论表单中移除网站链接字段', 'wpcomments');
    }
    
    private function init(): void {
        add_filter('comment_form_default_fields', array($this, 'remove_website_field'));
    }
    
    public function remove_website_field($fields) {
        unset($fields['url']);
        return $fields;
    }
}