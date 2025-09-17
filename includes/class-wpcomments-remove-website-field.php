<?php

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

class WPComments_Remove_Website_Field {
    
    public function __construct() {
        if (!get_option('wpcomments_enable_remove_website_field', false)) {
            return;
        }
        
        $this->init();
    }
    
    private function init() {
        add_filter('comment_form_default_fields', array($this, 'remove_website_field'));
    }
    
    public function remove_website_field($fields) {
        if (isset($fields['url'])) {
            unset($fields['url']);
        }
        
        return $fields;
    }
}