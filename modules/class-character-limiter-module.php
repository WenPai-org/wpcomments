<?php

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

class Character_Limiter_Module implements WPComments_Module_Interface {
    
    public function id(): string {
        return 'character_limiter';
    }
    
    public function is_enabled(array $settings): bool {
        return !empty($settings['character_limiter']);
    }
    
    public function register(): void {
        $this->init();
    }
    
    public function get_name(): string {
        return __('字符限制', 'wpcomments');
    }
    
    public function get_description(): string {
        return __('限制评论的最小和最大字符数', 'wpcomments');
    }
    
    private function init(): void {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_character_limiter_scripts'));
        add_action('pre_comment_on_post', array($this, 'validate_comment_length'));
        add_filter('comment_form_field_comment', array($this, 'add_character_counter'));
    }
    
    public function enqueue_character_limiter_scripts() {
        if (is_singular() && comments_open()) {
            wp_enqueue_script('wpcomments-character-limiter', WPCOMMENTS_URL . 'assets/js/character-limiter.js', array('jquery'), WPCOMMENTS_VERSION, true);
            
            $options = $this->get_character_limiter_options();
            wp_localize_script('wpcomments-character-limiter', 'wpcomments_character_limiter', array(
                'min_chars' => $options['min_chars'],
                'max_chars' => $options['max_chars'],
                'counter_text' => __('字符数: %current%/%max%', 'wpcomments'),
                'min_error' => sprintf(__('评论至少需要 %d 个字符', 'wpcomments'), $options['min_chars']),
                'max_error' => sprintf(__('评论不能超过 %d 个字符', 'wpcomments'), $options['max_chars']),
            ));
            
            wp_enqueue_style('wpcomments-character-limiter', WPCOMMENTS_URL . 'assets/css/character-limiter.css', array(), WPCOMMENTS_VERSION);
        }
    }
    
    public function validate_comment_length($post_id) {
        if (current_user_can('moderate_comments')) {
            return;
        }
        
        $options = $this->get_character_limiter_options();
        $comment_content = trim($_POST['comment']);
        $comment_length = mb_strlen($comment_content, 'UTF-8');
        
        if ($options['min_chars'] > 0 && $comment_length < $options['min_chars']) {
            wp_die(sprintf(__('评论至少需要 %d 个字符，您输入了 %d 个字符', 'wpcomments'), $options['min_chars'], $comment_length));
        }
        
        if ($options['max_chars'] > 0 && $comment_length > $options['max_chars']) {
            wp_die(sprintf(__('评论不能超过 %d 个字符，您输入了 %d 个字符', 'wpcomments'), $options['max_chars'], $comment_length));
        }
    }
    
    public function add_character_counter($field) {
        $options = $this->get_character_limiter_options();
        
        if ($options['show_counter']) {
            $counter_html = '<div class="wpcomments-character-counter">';
            $counter_html .= '<span class="wpcomments-counter-text">' . sprintf(__('字符数: %s/%s', 'wpcomments'), '<span class="wpcomments-current-count">0</span>', '<span class="wpcomments-max-count">' . $options['max_chars'] . '</span>') . '</span>';
            $counter_html .= '<div class="wpcomments-progress-bar"><div class="wpcomments-progress-fill"></div></div>';
            $counter_html .= '</div>';
            
            $field .= $counter_html;
        }
        
        return $field;
    }
    
    private function get_character_limiter_options() {
        $defaults = array(
            'min_chars' => 10,
            'max_chars' => 500,
            'show_counter' => true,
            'admin_exempt' => true
        );
        
        $options = get_option('wpcomments_character_limiter_options', $defaults);
        return wp_parse_args($options, $defaults);
    }
}