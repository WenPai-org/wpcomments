<?php

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

class Sticky_Comments_Module implements WPComments_Module_Interface {
    
    public function id(): string {
        return 'sticky_comments';
    }
    
    public function is_enabled(array $settings): bool {
        return !empty($settings['sticky_comments']);
    }
    
    public function register(): void {
        $this->init();
    }
    
    public function get_name(): string {
        return __('置顶评论', 'wpcomments');
    }
    
    public function get_description(): string {
        return __('允许管理员将重要评论置顶显示', 'wpcomments');
    }
    
    private function init(): void {
        add_filter('comment_row_actions', array($this, 'add_pin_unpin_actions'), 10, 2);
        add_action('admin_action_wpcomments_pin_comment', array($this, 'handle_pin_comment'));
        add_action('admin_action_wpcomments_unpin_comment', array($this, 'handle_unpin_comment'));
        add_filter('comments_array', array($this, 'sort_sticky_comments'), 10, 2);
        add_filter('comment_class', array($this, 'add_sticky_comment_class'), 10, 4);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_sticky_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_sticky_styles'));
    }
    
    public function add_pin_unpin_actions($actions, $comment) {
        if (current_user_can('moderate_comments')) {
            $is_sticky = $this->is_sticky_comment($comment->comment_ID);
            
            if ($is_sticky) {
                $actions['unpin'] = sprintf(
                    '<a href="%s">%s</a>',
                    wp_nonce_url(admin_url('admin.php?action=wpcomments_unpin_comment&comment_id=' . $comment->comment_ID), 'unpin_comment_' . $comment->comment_ID),
                    __('取消置顶', 'wpcomments')
                );
            } else {
                $actions['pin'] = sprintf(
                    '<a href="%s">%s</a>',
                    wp_nonce_url(admin_url('admin.php?action=wpcomments_pin_comment&comment_id=' . $comment->comment_ID), 'pin_comment_' . $comment->comment_ID),
                    __('置顶', 'wpcomments')
                );
            }
        }
        
        return $actions;
    }
    
    public function handle_pin_comment() {
        $comment_id = isset($_GET['comment_id']) ? absint($_GET['comment_id']) : 0;
        
        if (!$comment_id || !isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'pin_comment_' . $comment_id)) {
            wp_die(esc_html__('安全验证失败', 'wpcomments'));
        }
        
        if (!current_user_can('moderate_comments')) {
            wp_die(esc_html__('您没有权限执行此操作', 'wpcomments'));
        }
        
        update_comment_meta($comment_id, 'wpcomments_sticky', 1);
        
        wp_safe_redirect(wp_get_referer() ?: admin_url('edit-comments.php'));
        exit;
    }
    
    public function handle_unpin_comment() {
        $comment_id = isset($_GET['comment_id']) ? absint($_GET['comment_id']) : 0;
        
        if (!$comment_id || !isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'unpin_comment_' . $comment_id)) {
            wp_die(esc_html__('安全验证失败', 'wpcomments'));
        }
        
        if (!current_user_can('moderate_comments')) {
            wp_die(esc_html__('您没有权限执行此操作', 'wpcomments'));
        }
        
        delete_comment_meta($comment_id, 'wpcomments_sticky');
        
        wp_safe_redirect(wp_get_referer() ?: admin_url('edit-comments.php'));
        exit;
    }
    
    public function sort_sticky_comments($comments, $post_id) {
        $sticky_comments = array();
        $regular_comments = array();
        
        foreach ($comments as $comment) {
            if ($this->is_sticky_comment($comment->comment_ID)) {
                $sticky_comments[] = $comment;
            } else {
                $regular_comments[] = $comment;
            }
        }
        
        return array_merge($sticky_comments, $regular_comments);
    }
    
    public function add_sticky_comment_class($classes, $class, $comment_id, $post_id) {
        if ($this->is_sticky_comment($comment_id)) {
            $classes[] = 'wpcomments-sticky-comment';
        }
        return $classes;
    }
    
    public function enqueue_sticky_styles() {
        if (is_singular() && comments_open()) {
            $css = '
                .wpcomments-sticky-comment {
                    border-left: 4px solid #0073aa !important;
                    background-color: #f7f7f7 !important;
                    position: relative;
                }
                .wpcomments-sticky-comment:before {
                    content: "📌 ' . esc_js(__('置顶', 'wpcomments')) . '";
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    background: #0073aa;
                    color: white;
                    padding: 2px 8px;
                    border-radius: 3px;
                    font-size: 12px;
                    font-weight: bold;
                }
            ';
            wp_add_inline_style('wp-block-library', $css);
        }
    }
    
    public function enqueue_admin_sticky_styles() {
        $screen = get_current_screen();
        if ($screen && in_array($screen->id, array('edit-comments', 'comment'))) {
            $css = '
                .wpcomments-sticky-comment {
                    background-color: #fff3cd !important;
                    border-left: 4px solid #ffc107 !important;
                }
                .wpcomments-sticky-comment .row-title:before {
                    content: "📌 ";
                    color: #ffc107;
                }
            ';
            wp_add_inline_style('wp-admin', $css);
        }
    }
    
    private function is_sticky_comment($comment_id) {
        return get_comment_meta($comment_id, 'wpcomments_sticky', true) == 1;
    }
}
