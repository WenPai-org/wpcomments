<?php

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

class WPComments_Settings {
    
    private $option_group = 'wpcomments_settings';
    private $page_slug = 'wpcomments-settings';
    
    public function __construct() {
        if (is_multisite()) {
            $network_disable_comments = get_site_option('wpcomments_network_disable_comments', 0);
            if ($network_disable_comments) {
                return;
            }
        }
        
        $site_disable_comments = get_option('wpcomments_disable_comments', 0);
        if ($site_disable_comments) {
            return;
        }
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }
    
    public function add_admin_menu() {
        if (current_user_can('manage_options')) {
            add_options_page(
                'WP Comments 设置',
                'WP Comments',
                'manage_options',
                $this->page_slug,
                array($this, 'render_settings_page')
            );
        }
    }
    
    public function register_settings() {
        register_setting($this->option_group, 'wpcomments_enable_comments', array(
            'type' => 'boolean',
            'sanitize_callback' => 'boolval',
            'default' => false
        ));
        
        register_setting($this->option_group, 'wpcomments_enable_herpderp', array(
            'type' => 'boolean',
            'sanitize_callback' => 'boolval',
            'default' => false
        ));
        
        register_setting($this->option_group, 'wpcomments_enable_role_badge', array(
            'type' => 'boolean',
            'sanitize_callback' => 'boolval',
            'default' => false
        ));
        
        register_setting($this->option_group, 'wpcomments_enable_delete_pending', array(
            'type' => 'boolean',
            'sanitize_callback' => 'boolval',
            'default' => false
        ));
        
        register_setting($this->option_group, 'wpcomments_enable_sticky_moderate', array(
            'type' => 'boolean',
            'sanitize_callback' => 'boolval',
            'default' => false
        ));
        
        add_settings_section(
            'wpcomments_general_section',
            '基本设置',
            array($this, 'render_general_section'),
            $this->page_slug
        );
        
        add_settings_section(
            'wpcomments_features_section',
            '功能设置',
            array($this, 'render_features_section'),
            $this->page_slug
        );
        
        add_settings_field(
            'wpcomments_enable_comments',
            '启用评论增强',
            array($this, 'render_enable_comments_field'),
            $this->page_slug,
            'wpcomments_general_section'
        );
        
        add_settings_field(
            'wpcomments_enable_herpderp',
            '启用阿巴阿巴',
            array($this, 'render_enable_herpderp_field'),
            $this->page_slug,
            'wpcomments_features_section'
        );
        
        add_settings_field(
            'wpcomments_enable_role_badge',
            '启用角色徽章',
            array($this, 'render_enable_role_badge_field'),
            $this->page_slug,
            'wpcomments_features_section'
        );
        
        add_settings_field(
            'wpcomments_enable_delete_pending',
            '启用删除待审评论',
            array($this, 'render_enable_delete_pending_field'),
            $this->page_slug,
            'wpcomments_features_section'
        );
        
        add_settings_field(
            'wpcomments_enable_sticky_moderate',
            '启用评论置顶审核',
            array($this, 'render_enable_sticky_moderate_field'),
            $this->page_slug,
            'wpcomments_features_section'
        );
    }
    
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        if (isset($_GET['settings-updated'])) {
            add_settings_error('wpcomments_messages', 'wpcomments_message', '设置已保存', 'updated');
        }
        
        settings_errors('wpcomments_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="wpcomments-settings-container">
                <form action="options.php" method="post">
                    <?php
                    settings_fields($this->option_group);
                    do_settings_sections($this->page_slug);
                    submit_button('保存设置');
                    ?>
                </form>
                
                <div class="wpcomments-info-box">
                    <h3>关于 WP Comments</h3>
                    <p>WP Comments 是一个强大的 WordPress 评论增强插件，提供多种实用功能来改善您的评论体验。</p>
                    <ul>
                        <li><strong>评论增强：</strong>启用插件的核心功能</li>
                        <li><strong>阿巴阿巴：</strong>将评论内容替换为"阿巴阿巴"</li>
                        <li><strong>角色徽章：</strong>为不同用户角色显示徽章</li>
                        <li><strong>删除待审评论：</strong>快速删除待审核的评论</li>
                        <li><strong>评论置顶审核：</strong>将评论操作按钮置顶显示</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function render_general_section() {
        echo '<p>配置 WP Comments 插件的基本设置。</p>';
    }
    
    public function render_features_section() {
        echo '<p>启用或禁用特定的评论功能。</p>';
    }
    
    public function render_enable_comments_field() {
        $value = get_option('wpcomments_enable_comments', false);
        echo '<input type="hidden" name="wpcomments_enable_comments" value="0" />';
        echo '<input type="checkbox" id="wpcomments_enable_comments" name="wpcomments_enable_comments" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wpcomments_enable_comments">启用 WP Comments 插件的所有功能</label>';
        echo '<p class="description">这是插件的主开关，关闭后所有功能都将停用。</p>';
    }
    
    public function render_enable_herpderp_field() {
        $value = get_option('wpcomments_enable_herpderp', false);
        echo '<input type="hidden" name="wpcomments_enable_herpderp" value="0" />';
        echo '<input type="checkbox" id="wpcomments_enable_herpderp" name="wpcomments_enable_herpderp" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wpcomments_enable_herpderp">将评论内容替换为"阿巴阿巴"</label>';
        echo '<p class="description">启用后，所有评论内容都会显示为"阿巴阿巴"。</p>';
    }
    
    public function render_enable_role_badge_field() {
        $value = get_option('wpcomments_enable_role_badge', false);
        echo '<input type="hidden" name="wpcomments_enable_role_badge" value="0" />';
        echo '<input type="checkbox" id="wpcomments_enable_role_badge" name="wpcomments_enable_role_badge" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wpcomments_enable_role_badge">为用户显示角色徽章</label>';
        echo '<p class="description">在评论中显示用户的角色徽章（如管理员、编辑等）。</p>';
    }
    
    public function render_enable_delete_pending_field() {
        $value = get_option('wpcomments_enable_delete_pending', false);
        echo '<input type="hidden" name="wpcomments_enable_delete_pending" value="0" />';
        echo '<input type="checkbox" id="wpcomments_enable_delete_pending" name="wpcomments_enable_delete_pending" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wpcomments_enable_delete_pending">启用删除待审评论功能</label>';
        echo '<p class="description">在评论管理页面添加快速删除待审核评论的功能。</p>';
    }
    
    public function render_enable_sticky_moderate_field() {
        $value = get_option('wpcomments_enable_sticky_moderate', false);
        echo '<input type="hidden" name="wpcomments_enable_sticky_moderate" value="0" />';
        echo '<input type="checkbox" id="wpcomments_enable_sticky_moderate" name="wpcomments_enable_sticky_moderate" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wpcomments_enable_sticky_moderate">启用评论置顶审核功能</label>';
        echo '<p class="description">将评论操作按钮移至评论内容列的前部，便于快速操作。</p>';
    }
    
    public function enqueue_admin_styles($hook) {
        if ('settings_page_' . $this->page_slug !== $hook) {
            return;
        }
        
        wp_add_inline_style('wp-admin', '
            .wpcomments-settings-container {
                display: flex;
                gap: 20px;
                margin-top: 20px;
            }
            
            .wpcomments-settings-container form {
                flex: 2;
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            
            .wpcomments-info-box {
                flex: 1;
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                height: fit-content;
            }
            
            .wpcomments-info-box h3 {
                margin-top: 0;
                color: #23282d;
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
            }
            
            .wpcomments-info-box ul {
                margin: 15px 0;
                padding-left: 20px;
            }
            
            .wpcomments-info-box li {
                margin-bottom: 10px;
                line-height: 1.6;
            }
            
            .wpcomments-info-box li strong {
                color: #0073aa;
            }
            
            .form-table th {
                width: 200px;
                vertical-align: top;
                padding-top: 15px;
                font-weight: 600;
            }
            
            .form-table td {
                padding-top: 10px;
                padding-bottom: 15px;
            }
            
            .form-table td label {
                font-weight: 500;
                margin-left: 8px;
            }
            
            .form-table td p.description {
                margin-top: 8px;
                font-style: italic;
                color: #666;
                font-size: 13px;
            }
            
            .form-table input[type="checkbox"] {
                margin-right: 8px;
                transform: scale(1.1);
            }
            
            h2.title {
                border-bottom: 1px solid #ddd;
                padding-bottom: 8px;
                margin-bottom: 15px;
                color: #23282d;
            }
            
            .submit {
                padding-top: 10px;
                border-top: 1px solid #eee;
                margin-top: 20px;
            }
            
            .submit .button-primary {
                background: #0073aa;
                border-color: #0073aa;
                font-size: 14px;
                padding: 8px 16px;
                height: auto;
            }
            
            .submit .button-primary:hover {
                background: #005a87;
                border-color: #005a87;
            }
            
            @media (max-width: 782px) {
                .wpcomments-settings-container {
                    flex-direction: column;
                }
                
                .form-table th,
                .form-table td {
                    display: block;
                    width: 100%;
                    padding: 10px 0;
                }
                
                .form-table th {
                    border-bottom: none;
                }
            }
        ');
    }
}