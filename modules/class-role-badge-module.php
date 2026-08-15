<?php

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

class Role_Badge_Module implements WPComments_Module_Interface {
    
    protected $user_role = '';
    
    public function id(): string {
        return 'role_badge';
    }
    
    public function is_enabled(array $settings): bool {
        return !empty($settings['role_badge']);
    }
    
    public function register(): void {
        $this->init();
    }

    public function enqueue_frontend(): void {
        wp_enqueue_style(
            'wpcomments-role-badge',
            WPCOMMENTS_URL . 'assets/comment-author-role-badge.css',
            array(),
            WPCOMMENTS_VERSION
        );
    }
    
    public function get_name(): string {
        return __('角色徽章', 'wpcomments');
    }
    
    public function get_description(): string {
        return __('在评论中显示用户角色徽章', 'wpcomments');
    }
    
    private function init(): void {
        add_action('init', array($this, 'load_admin_textdomain_in_frontend'));
        add_filter('get_comment_author', array($this, 'get_comment_author_role'), 10, 3);
        add_filter('get_comment_author_link', array($this, 'comment_author_role'));
        
        if ($this->is_block_theme()) {
            add_action('wp_head', array($this, 'add_fse_theme_styles'));
            add_filter('render_block', array($this, 'modify_comment_blocks'), 10, 2);
        }
    }
    
    public function load_admin_textdomain_in_frontend() {
        if (!is_admin()) {
            \load_textdomain('default', WP_LANG_DIR . '/admin-' . get_locale() . '.mo');
        }
    }
    
    public function get_comment_author_role($author, $comment_id, $comment) {
        global $wp_roles;

        $this->user_role = '';
        if ($wp_roles) {
            $reply_user_id = $comment->user_id;
            if ($reply_user_id) {
                $reply_user = new \WP_User($reply_user_id);
                if (isset($reply_user->roles[0])) {
                    $role_slug = sanitize_html_class($reply_user->roles[0]);
                    $role_data = isset($wp_roles->roles[$reply_user->roles[0]])
                        ? $wp_roles->roles[$reply_user->roles[0]]
                        : null;
                    if ($role_data) {
                        $user_role = translate_user_role($role_data['name']);
                        $this->user_role = '<span class="comment-author-role-badge comment-author-role-badge--' . esc_attr($role_slug) . '">' . esc_html($user_role) . '</span>';
                    }
                }
            } else {
                $this->user_role = '';
            }
        }
        return $author;
    }
    
    public function comment_author_role($author) {
        return $author . $this->user_role;
    }
    
    private function is_block_theme() {
        return function_exists('wp_is_block_theme') && wp_is_block_theme();
    }
    
    public function add_fse_theme_styles() {
        echo '<style>
            .wp-block-comment-author-name .comment-author-role-badge {
                display: inline-block;
                margin-left: 8px;
                font-size: 0.8em;
                padding: 2px 6px;
                border-radius: 3px;
                background: #0073aa;
                color: white;
            }
        </style>';
    }
    
    public function modify_comment_blocks($block_content, $block) {
        if ($block['blockName'] === 'core/comment-author-name') {
            return $block_content . $this->user_role;
        }
        return $block_content;
    }
}
