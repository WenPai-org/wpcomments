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
            wp_enqueue_script('wpcomments-character-limiter', WPCOMMENTS_URL . 'assets/js/comment-limiter.js', array('jquery'), WPCOMMENTS_VERSION, true);
            
            $options = $this->get_character_limiter_options();
            wp_localize_script('wpcomments-character-limiter', 'wpcommentsLimiter', array(
                'minChars' => $options['min_chars'],
                'maxChars' => $options['max_chars'],
                'minMessage' => $options['min_message'],
                'maxMessage' => $options['max_message'],
                'isAdmin' => current_user_can('moderate_comments') ? 'yes' : 'no',
                'enableAdminFeature' => $options['enable_admin_feature'],
            ));
            
            wp_enqueue_style('wpcomments-character-limiter', WPCOMMENTS_URL . 'assets/css/comment-limiter.css', array(), WPCOMMENTS_VERSION);
        }
    }
    
    public function validate_comment_length($post_id) {
        $options = $this->get_character_limiter_options();
        if (current_user_can('moderate_comments') && 'yes' !== $options['enable_admin_feature']) {
            return;
        }

        $comment_content = isset($_POST['comment'])
            ? trim(wp_kses_post(wp_unslash($_POST['comment'])))
            : '';
        $comment_length = mb_strlen($comment_content, 'UTF-8');
        
        if ($options['min_chars'] > 0 && $comment_length < $options['min_chars']) {
            /* translators: 1: minimum characters, 2: submitted characters. */
            wp_die(esc_html(sprintf(__('评论至少需要 %1$d 个字符，您输入了 %2$d 个字符', 'wpcomments'), $options['min_chars'], $comment_length)));
        }
        
        if ($options['max_chars'] > 0 && $comment_length > $options['max_chars']) {
            /* translators: 1: maximum characters, 2: submitted characters. */
            wp_die(esc_html(sprintf(__('评论不能超过 %1$d 个字符，您输入了 %2$d 个字符', 'wpcomments'), $options['max_chars'], $comment_length)));
        }
    }
    
    public function add_character_counter($field) {
        $options = $this->get_character_limiter_options();
        
        if ($options['show_counter']) {
            $counter_html = '<div id="wpcomments-counter-container" class="wpcomments-character-counter">';
            /* translators: %d: maximum permitted number of comment characters. */
            $counter_html .= '<span id="wpcomments-char-counter" class="wpcomments-counter-text">' . esc_html(sprintf(__('字符数: 0/%d', 'wpcomments'), $options['max_chars'])) . '</span>';
            $counter_html .= '<div class="wpcomments-progress-bar"><div id="wpcomments-progress-fill" class="wpcomments-progress-fill"></div></div>';
            $counter_html .= '</div>';
            
            $field .= $counter_html;
        }
        
        return $field;
    }
    
    private function get_character_limiter_options() {
        $defaults = array(
            'minimum_characters' => 20,
            'maximum_characters' => 1000,
            'minimum_message' => __('评论内容太短。', 'wpcomments'),
            'maximum_message' => __('评论内容超过最大长度。', 'wpcomments'),
            'show_counter' => true,
            'enable_admin_feature' => 'no',
        );
        
        $options = get_option('wpcomments_comment_limiter_options', $defaults);
        $options = wp_parse_args(is_array($options) ? $options : array(), $defaults);
        return array(
            'min_chars' => absint($options['minimum_characters']),
            'max_chars' => absint($options['maximum_characters']),
            'min_message' => sanitize_text_field($options['minimum_message']),
            'max_message' => sanitize_text_field($options['maximum_message']),
            'show_counter' => !empty($options['show_counter']),
            'enable_admin_feature' => 'yes' === $options['enable_admin_feature'] ? 'yes' : 'no',
        );
    }
}
