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
            'WPComments Network Settings',
            'WPComments',
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
        $enable_herpderp = get_site_option('wpcomments_network_enable_herpderp', false);
        $enable_role_badge = get_site_option('wpcomments_network_enable_role_badge', false);
        $enable_delete_pending = get_site_option('wpcomments_network_enable_delete_pending', false);
        $enable_sticky_moderate = get_site_option('wpcomments_network_enable_sticky_moderate', false);

        $enable_remove_feed_link = get_site_option('wpcomments_network_enable_remove_feed_link', false);
        $enable_remove_website_field = get_site_option('wpcomments_network_enable_remove_website_field', false);
        $enable_frequently_replies = get_site_option('wpcomments_network_enable_frequently_replies', false);
        $enable_validation = get_site_option('wpcomments_network_enable_validation', false);
        $enable_moderation_info = get_site_option('wpcomments_network_enable_moderation_info', true);
        $enable_email_notification = get_site_option('wpcomments_network_enable_email_notification', true);
        $allow_site_override = get_site_option('wpcomments_network_allow_site_override', true);
        
        ?>
        <div class="wrap">
            <h1>WPComments Network Settings</h1>
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
                                        

                                        
                                        <label for="wpcomments_network_enable_herpderp">
                                            <input type="hidden" name="wpcomments_network_enable_herpderp" value="0">
                                            <input type="checkbox" id="wpcomments_network_enable_herpderp" name="wpcomments_network_enable_herpderp" value="1" <?php checked(1, $enable_herpderp); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            启用阿巴阿巴功能
                                        </label>
                                        <p class="description" style="margin-left: 25px; margin-top: 5px; color: #666; font-size: 13px;">启用后，将对评论内容进行智能过滤，自动将低质量或无意义的评论转换为"阿巴阿巴"等占位文本。</p><br>
                                        
                                        <label for="wpcomments_network_enable_role_badge">
                                            <input type="hidden" name="wpcomments_network_enable_role_badge" value="0">
                                            <input type="checkbox" id="wpcomments_network_enable_role_badge" name="wpcomments_network_enable_role_badge" value="1" <?php checked(1, $enable_role_badge); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            启用角色徽章功能
                                        </label>
                                        <p class="description" style="margin-left: 25px; margin-top: 5px; color: #666; font-size: 13px;">启用后，将在评论者姓名旁显示其用户角色徽章，如管理员、编辑者等，便于识别用户身份。</p><br>
                                        
                                        <label for="wpcomments_network_enable_delete_pending">
                                            <input type="hidden" name="wpcomments_network_enable_delete_pending" value="0">
                                            <input type="checkbox" id="wpcomments_network_enable_delete_pending" name="wpcomments_network_enable_delete_pending" value="1" <?php checked(1, $enable_delete_pending); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            启用删除待审评论功能
                                        </label>
                                        <p class="description" style="margin-left: 25px; margin-top: 5px; color: #666; font-size: 13px;">启用后，管理员可以批量删除所有待审核状态的评论，提高评论管理效率。</p><br>
                                        
                                        <label for="wpcomments_network_enable_sticky_moderate">
                                            <input type="hidden" name="wpcomments_network_enable_sticky_moderate" value="0">
                                            <input type="checkbox" id="wpcomments_network_enable_sticky_moderate" name="wpcomments_network_enable_sticky_moderate" value="1" <?php checked(1, $enable_sticky_moderate); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            启用评论置顶审核功能
                                        </label>
                                        <p class="description" style="margin-left: 25px; margin-top: 5px; color: #666; font-size: 13px;">启用后，管理员可以将重要评论置顶显示，并对置顶评论进行特殊审核管理。</p><br>
                                        

                                        
                                        <label for="wpcomments_network_enable_remove_feed_link">
                                            <input type="hidden" name="wpcomments_network_enable_remove_feed_link" value="0">
                                            <input type="checkbox" id="wpcomments_network_enable_remove_feed_link" name="wpcomments_network_enable_remove_feed_link" value="1" <?php checked(1, $enable_remove_feed_link); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            启用移除评论订阅链接功能
                                        </label>
                                        <p class="description" style="margin-left: 25px; margin-top: 5px; color: #666; font-size: 13px;">启用后，将自动移除评论中的RSS订阅链接，减少垃圾链接和提升页面整洁度。</p><br>
                                        
                                        <label for="wpcomments_network_enable_remove_website_field">
                                            <input type="hidden" name="wpcomments_network_enable_remove_website_field" value="0">
                                            <input type="checkbox" id="wpcomments_network_enable_remove_website_field" name="wpcomments_network_enable_remove_website_field" value="1" <?php checked(1, $enable_remove_website_field); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            启用移除网站链接字段功能
                                        </label>
                                        <p class="description" style="margin-left: 25px; margin-top: 5px; color: #666; font-size: 13px;">启用后，将在评论表单中隐藏网站URL字段，减少垃圾评论和恶意链接的提交。</p><br>
                                        
                                        <label for="wpcomments_network_enable_frequently_replies">
                                            <input type="hidden" name="wpcomments_network_enable_frequently_replies" value="0">
                                            <input type="checkbox" id="wpcomments_network_enable_frequently_replies" name="wpcomments_network_enable_frequently_replies" value="1" <?php checked(1, $enable_frequently_replies); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            启用常用回复功能
                                        </label>
                                        <p class="description" style="margin-left: 25px; margin-top: 5px; color: #666; font-size: 13px;">启用后，您可以创建和管理常用回复模板，在回复评论时快速插入预设内容。</p><br>
                                        
                                        <label for="wpcomments_network_enable_validation">
                                            <input type="hidden" name="wpcomments_network_enable_validation" value="0">
                                            <input type="checkbox" id="wpcomments_network_enable_validation" name="wpcomments_network_enable_validation" value="1" <?php checked(1, $enable_validation); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            启用评论表单验证功能
                                        </label>
                                        <p class="description" style="margin-left: 25px; margin-top: 5px; color: #666; font-size: 13px;">启用后，评论表单将进行客户端验证，确保必填字段已填写且格式正确。</p><br>
                                        
                                        <label for="wpcomments_network_enable_moderation_info">
                                            <input type="hidden" name="wpcomments_network_enable_moderation_info" value="0">
                                            <input type="checkbox" id="wpcomments_network_enable_moderation_info" name="wpcomments_network_enable_moderation_info" value="1" <?php checked(1, $enable_moderation_info); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            启用审核信息功能
                                        </label>
                                        <p class="description" style="margin-left: 25px; margin-top: 5px; color: #666; font-size: 13px;">启用后，将显示评论审核状态信息，帮助管理员了解评论的审核进度。</p><br>
                                        
                                        <label for="wpcomments_network_enable_email_notification">
                                            <input type="hidden" name="wpcomments_network_enable_email_notification" value="0">
                                            <input type="checkbox" id="wpcomments_network_enable_email_notification" name="wpcomments_network_enable_email_notification" value="1" <?php checked(1, $enable_email_notification); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            启用邮件通知功能
                                        </label>
                                        <p class="description" style="margin-left: 25px; margin-top: 5px; color: #666; font-size: 13px;">启用后，将向管理员和用户发送评论相关的邮件通知。</p>
                                        
                                        <p class="description"><?php echo $disable_comments ? '网络级别已禁用评论功能，以下选项不可设置。' : '这些设置将作为新站点的默认配置。如果启用了"允许各站点覆盖网络设置"，各站点可以独立修改这些设置。'; ?></p>
                                    </fieldset>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <?php submit_button('保存网络设置'); ?>
                </form>
                
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

        $enable_herpderp = isset($_POST['wpcomments_network_enable_herpderp']) ? (bool) $_POST['wpcomments_network_enable_herpderp'] : false;
        $enable_role_badge = isset($_POST['wpcomments_network_enable_role_badge']) ? (bool) $_POST['wpcomments_network_enable_role_badge'] : false;
        $enable_delete_pending = isset($_POST['wpcomments_network_enable_delete_pending']) ? (bool) $_POST['wpcomments_network_enable_delete_pending'] : false;
        $enable_sticky_moderate = isset($_POST['wpcomments_network_enable_sticky_moderate']) ? (bool) $_POST['wpcomments_network_enable_sticky_moderate'] : false;

        $enable_remove_feed_link = isset($_POST['wpcomments_network_enable_remove_feed_link']) ? (bool) $_POST['wpcomments_network_enable_remove_feed_link'] : false;
        $enable_remove_website_field = isset($_POST['wpcomments_network_enable_remove_website_field']) ? (bool) $_POST['wpcomments_network_enable_remove_website_field'] : false;
        $enable_frequently_replies = isset($_POST['wpcomments_network_enable_frequently_replies']) ? (bool) $_POST['wpcomments_network_enable_frequently_replies'] : false;
        $enable_validation = isset($_POST['wpcomments_network_enable_validation']) ? (bool) $_POST['wpcomments_network_enable_validation'] : false;
        $enable_moderation_info = isset($_POST['wpcomments_network_enable_moderation_info']) ? (bool) $_POST['wpcomments_network_enable_moderation_info'] : false;
        $enable_email_notification = isset($_POST['wpcomments_network_enable_email_notification']) ? (bool) $_POST['wpcomments_network_enable_email_notification'] : false;
        
        update_site_option('wpcomments_network_disable_comments', $disable_comments);
        update_site_option('wpcomments_network_allow_site_override', $allow_site_override);

        update_site_option('wpcomments_network_enable_herpderp', $enable_herpderp);
        update_site_option('wpcomments_network_enable_role_badge', $enable_role_badge);
        update_site_option('wpcomments_network_enable_delete_pending', $enable_delete_pending);
        update_site_option('wpcomments_network_enable_sticky_moderate', $enable_sticky_moderate);

        update_site_option('wpcomments_network_enable_remove_feed_link', $enable_remove_feed_link);
        update_site_option('wpcomments_network_enable_remove_website_field', $enable_remove_website_field);
        update_site_option('wpcomments_network_enable_frequently_replies', $enable_frequently_replies);
        update_site_option('wpcomments_network_enable_validation', $enable_validation);
        update_site_option('wpcomments_network_enable_moderation_info', $enable_moderation_info);
        update_site_option('wpcomments_network_enable_email_notification', $enable_email_notification);
        
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