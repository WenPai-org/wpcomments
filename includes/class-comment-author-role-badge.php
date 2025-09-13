<?php

namespace WpComments;

defined('ABSPATH') or die('No script kiddies please!');

class Comment_Author_Role_Badge {

    protected $user_role = '';
    protected $is_fse_theme = false;

    public function __construct() {
        $this->is_fse_theme = $this->is_block_theme();
        
        add_action('init', array($this, 'load_admin_textdomain_in_frontend'));
        add_action('wp_enqueue_scripts', array($this, 'add_stylesheets'));
        add_filter('get_comment_author', array($this, 'get_comment_author_role'), 10, 3);
        add_filter('get_comment_author_link', array($this, 'comment_author_role'));
        
        if ($this->is_fse_theme) {
            add_action('wp_head', array($this, 'add_fse_theme_styles'));
            add_filter('render_block', array($this, 'modify_comment_blocks'), 10, 2);
        }
    }

    public function load_admin_textdomain_in_frontend() {
        if (!is_admin()) {
            load_textdomain('default', WP_LANG_DIR . '/admin-' . get_locale() . '.mo');
        }
    }

    public function get_comment_author_role($author, $comment_id, $comment) {
        global $wp_roles;
        
        if ($wp_roles) {
            $reply_user_id = $comment->user_id;
            if ($reply_user_id && $reply_user = new \WP_User($reply_user_id)) {
                if (isset($reply_user->roles[0])) {
                    $user_role = translate_user_role($wp_roles->roles[$reply_user->roles[0]]['name']);
                    $this->user_role = '<div class="comment-author-role-badge comment-author-role-badge--' . $reply_user->roles[0] . '">' . $user_role . '</div>';
                }
            } else {
                $this->user_role = '';
            }
        }
        return $author;
    }

    public function comment_author_role($author) {
        return $author .= $this->user_role;
    }

    public function add_stylesheets() {
        wp_enqueue_style(
            'wpcomments-author-role-badge',
            WPCOMMENTS_URL . 'assets/comment-author-role-badge.css',
            array(),
            WPCOMMENTS_VERSION
        );
    }

    protected function is_block_theme() {
        if (function_exists('wp_is_block_theme')) {
            return wp_is_block_theme();
        }
        
        if (function_exists('get_template_directory')) {
            $theme_dir = get_template_directory();
            return file_exists($theme_dir . '/templates/index.html') || 
                   file_exists($theme_dir . '/block-templates/index.html');
        }
        
        return false;
    }

    public function add_fse_theme_styles() {
        echo '<style>
            .wp-block-comment-author-name .comment-author-role-badge {
                display: inline-block;
                margin-left: 0.5em;
            }
            
            .wp-block-comment-content .comment-author-role-badge {
                display: inline-block;
                margin-left: 0.5em;
            }
            
            .wp-block-comments .comment-author-role-badge {
                font-size: 0.75rem;
                vertical-align: middle;
            }
        </style>';
    }

    public function modify_comment_blocks($block_content, $block) {
        if (!isset($block['blockName']) || empty($this->user_role)) {
            return $block_content;
        }

        switch ($block['blockName']) {
            case 'core/comment-author-name':
                if (strpos($block_content, 'comment-author-role-badge') === false) {
                    $block_content = str_replace(
                        '</a>',
                        '</a>' . $this->user_role,
                        $block_content
                    );
                    
                    $block_content = str_replace(
                        '</cite>',
                        $this->user_role . '</cite>',
                        $block_content
                    );
                    
                    if (strpos($block_content, '</a>') === false && strpos($block_content, '</cite>') === false) {
                        $block_content = preg_replace(
                            '/(<[^>]*class="[^"]*wp-block-comment-author-name[^"]*"[^>]*>.*?)(<\/[^>]+>)$/s',
                            '$1' . $this->user_role . '$2',
                            $block_content
                        );
                    }
                }
                break;
                
            case 'core/comment-template':
                if (strpos($block_content, 'comment-author-role-badge') === false) {
                    $block_content = preg_replace(
                        '/(<[^>]*class="[^"]*wp-block-comment-author-name[^"]*"[^>]*>.*?)(<\/[^>]+>)/s',
                        '$1' . $this->user_role . '$2',
                        $block_content
                    );
                }
                break;
        }

        return $block_content;
    }
}