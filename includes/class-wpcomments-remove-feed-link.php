<?php

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

class WPComments_Remove_Feed_Link {
    
    public function __construct() {
        if (!get_option('wpcomments_enable_remove_feed_link', false)) {
            return;
        }
        
        $this->init();
    }
    
    private function init() {
        global $wp_version;
        
        if (version_compare($wp_version, '4.4', '>=')) {
            add_filter('feed_links_show_comments_feed', '__return_false');
        } else {
            remove_action('wp_head', 'feed_links', 2);
            add_action('wp_head', array($this, 'custom_feed_links'), 2);
        }
        
        remove_action('wp_head', 'feed_links_extra', 3);
        add_action('template_redirect', array($this, 'disable_comment_feed'));
        add_action('init', array($this, 'load_textdomain'));
    }
    
    public function custom_feed_links() {
        printf('<link rel="alternate" type="%s" title="%s" href="%s" />' . "\n",
            feed_content_type(),
            esc_attr(sprintf(__('%s Feed'), get_bloginfo('name'))),
            esc_url(get_feed_link())
        );
    }
    
    public function disable_comment_feed() {
        if (is_comment_feed()) {
            wp_die(__('No comment feeds available.'), '', array('response' => 404));
        }
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('wpcomments', false, dirname(plugin_basename(__FILE__)) . '/../languages/');
    }
}