<?php

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

class Validation_Module implements WPComments_Module_Interface {
    
    public function id(): string {
        return 'validation';
    }
    
    public function is_enabled(array $settings): bool {
        return !empty($settings['validation']);
    }
    
    public function register(): void {
        $this->init();
    }
    
    public function get_name(): string {
        return __('评论验证', 'wpcomments');
    }
    
    public function get_description(): string {
        return __('为评论表单添加前端和后端验证', 'wpcomments');
    }
    
    private function init(): void {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_validation_scripts'));
        add_action('pre_comment_on_post', array($this, 'validate_comment_submission'));
    }
    
    public function enqueue_validation_scripts() {
        if (is_singular() && comments_open()) {
            wp_enqueue_script('wpcomments-validation', WPCOMMENTS_URL . 'assets/js/validation.js', array('jquery'), WPCOMMENTS_VERSION, true);
            
            wp_localize_script('wpcomments-validation', 'wpcomments_validation', array(
                'required_name' => __('请输入您的姓名', 'wpcomments'),
                'required_email' => __('请输入您的邮箱地址', 'wpcomments'),
                'invalid_email' => __('请输入有效的邮箱地址', 'wpcomments'),
                'required_comment' => __('请输入评论内容', 'wpcomments'),
            ));
        }
    }
    
    public function validate_comment_submission($post_id) {
        $comment = isset($_POST['comment']) ? trim(wp_unslash($_POST['comment'])) : '';
        $author = isset($_POST['author']) ? trim(wp_unslash($_POST['author'])) : '';
        $email = isset($_POST['email']) ? trim(wp_unslash($_POST['email'])) : '';
        if ('' === $comment) {
            wp_die(__('评论内容不能为空', 'wpcomments'));
        }
        
        if ('' === $author && get_option('require_name_email')) {
            wp_die(__('请输入您的姓名', 'wpcomments'));
        }
        
        if ('' === $email && get_option('require_name_email')) {
            wp_die(__('请输入您的邮箱地址', 'wpcomments'));
        }
        
        if ('' !== $email && !is_email($email)) {
            wp_die(__('请输入有效的邮箱地址', 'wpcomments'));
        }
    }
}
