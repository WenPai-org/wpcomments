<?php

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

class WPComments_Network_Settings {
    
    private $option_group = 'wpcomments_network_settings';
    private $page_slug = 'wpcomments-network-settings';
    
    public function __construct() {
        add_action('network_admin_menu', array($this, 'add_network_admin_menu'));
        add_action('network_admin_edit_wpcomments_network_settings', array($this, 'save_network_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }
    
    public function add_network_admin_menu() {
        add_submenu_page(
            'settings.php',
            'WP Comments 网络设置',
            'WP Comments',
            'manage_network_options',
            $this->page_slug,
            array($this, 'render_network_settings_page')
        );
    }
    
    public function render_network_settings_page() {
        if (isset($_GET['updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>网络设置已保存</p></div>';
        }
        
        $disable_comments = get_site_option('wpcomments_network_disable_comments', false);
        $enable_comments = get_site_option('wpcomments_network_enable_comments', false);
        $enable_herpderp = get_site_option('wpcomments_network_enable_herpderp', false);
        $enable_role_badge = get_site_option('wpcomments_network_enable_role_badge', false);
        $enable_delete_pending = get_site_option('wpcomments_network_enable_delete_pending', false);
        $enable_sticky_moderate = get_site_option('wpcomments_network_enable_sticky_moderate', false);
        $allow_site_override = get_site_option('wpcomments_network_allow_site_override', true);
        
        ?>
        <div class="wrap">
            <h1>WP Comments 网络设置</h1>
            <div class="wpcomments-network-settings-container">
                <form method="post" action="edit.php?action=wpcomments_network_settings">
                    <?php wp_nonce_field('wpcomments_network_settings_nonce'); ?>
                    
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">网络级别控制</th>
                                <td>
                                    <fieldset>
                                        <label for="wpcomments_network_allow_site_override">
                                            <input type="hidden" name="wpcomments_network_allow_site_override" value="0">
                                            <input type="checkbox" id="wpcomments_network_allow_site_override" name="wpcomments_network_allow_site_override" value="1" <?php checked(1, $allow_site_override); ?>>
                                            允许各站点覆盖网络设置
                                        </label>
                                        <p class="description">如果禁用，各站点将无法修改 WP Comments 设置，只能使用网络级别的设置。</p>
                                    </fieldset>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">评论控制设置</th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text">评论控制设置</legend>
                                        
                                        <label for="wpcomments_network_disable_comments">
                                            <input type="hidden" name="wpcomments_network_disable_comments" value="0">
                                            <input type="checkbox" id="wpcomments_network_disable_comments" name="wpcomments_network_disable_comments" value="1" <?php checked(1, $disable_comments); ?>>
                                            网络级别禁用评论功能
                                        </label>
                                        <p class="description">启用后将在整个网络中完全禁用评论系统，包括前台显示和后台管理。</p><br>
                                    </fieldset>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">默认功能设置</th>
                                <td>
                                    <fieldset id="wpcomments_feature_settings">
                                        <legend class="screen-reader-text">默认功能设置</legend>
                                        
                                        <label for="wpcomments_network_enable_comments">
                                            <input type="hidden" name="wpcomments_network_enable_comments" value="0">
                                            <input type="checkbox" id="wpcomments_network_enable_comments" name="wpcomments_network_enable_comments" value="1" <?php checked(1, $enable_comments); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            默认启用评论增强功能
                                        </label><br>
                                        
                                        <label for="wpcomments_network_enable_herpderp">
                                            <input type="hidden" name="wpcomments_network_enable_herpderp" value="0">
                                            <input type="checkbox" id="wpcomments_network_enable_herpderp" name="wpcomments_network_enable_herpderp" value="1" <?php checked(1, $enable_herpderp); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            默认启用阿巴阿巴功能
                                        </label><br>
                                        
                                        <label for="wpcomments_network_enable_role_badge">
                                            <input type="hidden" name="wpcomments_network_enable_role_badge" value="0">
                                            <input type="checkbox" id="wpcomments_network_enable_role_badge" name="wpcomments_network_enable_role_badge" value="1" <?php checked(1, $enable_role_badge); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            默认启用角色徽章功能
                                        </label><br>
                                        
                                        <label for="wpcomments_network_enable_delete_pending">
                                            <input type="hidden" name="wpcomments_network_enable_delete_pending" value="0">
                                            <input type="checkbox" id="wpcomments_network_enable_delete_pending" name="wpcomments_network_enable_delete_pending" value="1" <?php checked(1, $enable_delete_pending); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            默认启用删除待审评论功能
                                        </label><br>
                                        
                                        <label for="wpcomments_network_enable_sticky_moderate">
                                            <input type="hidden" name="wpcomments_network_enable_sticky_moderate" value="0">
                                            <input type="checkbox" id="wpcomments_network_enable_sticky_moderate" name="wpcomments_network_enable_sticky_moderate" value="1" <?php checked(1, $enable_sticky_moderate); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            默认启用评论置顶审核功能
                                        </label>
                                        
                                        <p class="description"><?php echo $disable_comments ? '网络级别已禁用评论功能，以下选项不可设置。' : '这些设置将作为新站点的默认配置。如果启用了"允许各站点覆盖网络设置"，各站点可以独立修改这些设置。'; ?></p>
                                    </fieldset>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <?php submit_button('保存网络设置'); ?>
                </form>
                
                <div class="wpcomments-network-info-box">
                    <h3>网络设置说明</h3>
                    <p>在多站点网络环境中，您可以通过此页面统一管理所有站点的 WP Comments 插件设置。</p>
                    
                    <h4>设置优先级</h4>
                    <ul>
                        <li><strong>网络强制模式：</strong>当"允许各站点覆盖网络设置"被禁用时，所有站点将强制使用网络级别的设置</li>
                        <li><strong>站点自主模式：</strong>当"允许各站点覆盖网络设置"被启用时，各站点可以独立配置功能</li>
                        <li><strong>默认继承：</strong>新创建的站点将继承网络级别的默认设置</li>
                    </ul>
                    
                    <h4>功能说明</h4>
                    <ul>
                        <li><strong>评论增强：</strong>插件的核心功能开关</li>
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
    
    public function save_network_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'wpcomments_network_settings_nonce')) {
            wp_die('安全验证失败');
        }
        
        if (!current_user_can('manage_network_options')) {
            wp_die('权限不足');
        }
        
        $disable_comments = isset($_POST['wpcomments_network_disable_comments']) ? (bool) $_POST['wpcomments_network_disable_comments'] : false;
        $allow_site_override = isset($_POST['wpcomments_network_allow_site_override']) ? (bool) $_POST['wpcomments_network_allow_site_override'] : false;
        $enable_comments = isset($_POST['wpcomments_network_enable_comments']) ? (bool) $_POST['wpcomments_network_enable_comments'] : false;
        $enable_herpderp = isset($_POST['wpcomments_network_enable_herpderp']) ? (bool) $_POST['wpcomments_network_enable_herpderp'] : false;
        $enable_role_badge = isset($_POST['wpcomments_network_enable_role_badge']) ? (bool) $_POST['wpcomments_network_enable_role_badge'] : false;
        $enable_delete_pending = isset($_POST['wpcomments_network_enable_delete_pending']) ? (bool) $_POST['wpcomments_network_enable_delete_pending'] : false;
        $enable_sticky_moderate = isset($_POST['wpcomments_network_enable_sticky_moderate']) ? (bool) $_POST['wpcomments_network_enable_sticky_moderate'] : false;
        
        update_site_option('wpcomments_network_disable_comments', $disable_comments);
        update_site_option('wpcomments_network_allow_site_override', $allow_site_override);
        update_site_option('wpcomments_network_enable_comments', $enable_comments);
        update_site_option('wpcomments_network_enable_herpderp', $enable_herpderp);
        update_site_option('wpcomments_network_enable_role_badge', $enable_role_badge);
        update_site_option('wpcomments_network_enable_delete_pending', $enable_delete_pending);
        update_site_option('wpcomments_network_enable_sticky_moderate', $enable_sticky_moderate);
        
        wp_redirect(add_query_arg(array('page' => $this->page_slug, 'updated' => 'true'), network_admin_url('settings.php')));
        exit;
    }
    
    public function enqueue_admin_styles($hook) {
        if ('settings_page_' . $this->page_slug . '-network' !== $hook) {
            return;
        }
        
        wp_add_inline_style('wp-admin', '
            .wpcomments-network-settings-container {
                display: flex;
                gap: 20px;
                margin-top: 20px;
            }
            
            .wpcomments-network-settings-container form {
                flex: 2;
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            
            .wpcomments-network-info-box {
                flex: 1;
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                height: fit-content;
            }
            
            .wpcomments-network-info-box h3 {
                margin-top: 0;
                color: #23282d;
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
            }
            
            .wpcomments-network-info-box h4 {
                margin-top: 20px;
                margin-bottom: 10px;
                font-size: 14px;
                color: #0073aa;
                font-weight: 600;
            }
            
            .wpcomments-network-info-box ul {
                margin: 15px 0;
                padding-left: 20px;
            }
            
            .wpcomments-network-info-box li {
                margin-bottom: 10px;
                line-height: 1.6;
            }
            
            .wpcomments-network-info-box li strong {
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
            
            .form-table td fieldset label {
                display: block;
                margin-bottom: 12px;
                font-weight: 500;
            }
            
            .form-table td fieldset label input[type="checkbox"] {
                margin-right: 8px;
                transform: scale(1.1);
            }
            
            .form-table td p.description {
                margin-top: 12px;
                font-style: italic;
                color: #666;
                font-size: 13px;
                line-height: 1.5;
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
            
            .notice {
                margin: 15px 0;
            }
            
            @media (max-width: 782px) {
                .wpcomments-network-settings-container {
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
    
    public static function get_effective_setting($setting_name, $default = false) {
        if (!is_multisite()) {
            return get_option($setting_name, $default);
        }
        
        $allow_override = get_site_option('wpcomments_network_allow_site_override', true);
        
        if (!$allow_override) {
            $network_setting = str_replace('wpcomments_', 'wpcomments_network_', $setting_name);
            return get_site_option($network_setting, $default);
        }
        
        return get_option($setting_name, $default);
    }
}