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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
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
        if (!current_user_can('manage_network_options')) {
            wp_die(__('您没有足够权限访问此页面。', 'wpcomments'));
        }
        // 成功提示统一交由前端 JS 的 #wpcomments-status 控制，避免重复提示
        
        // Get all network options
        $disable_comments = get_site_option('wpcomments_disable_comments', false);
        $enable_role_badge = get_site_option('wpcomments_enable_role_badge', false);
        $enable_delete_pending = get_site_option('wpcomments_enable_delete_pending', false);
        $enable_remove_website_field = get_site_option('wpcomments_enable_remove_website_field', false);
        $enable_frequently_replies = get_site_option('wpcomments_enable_frequently_replies', false);
        $enable_validation = get_site_option('wpcomments_enable_validation', false);
        $enable_moderation_info = get_site_option('wpcomments_enable_moderation_info', false);
        $enable_email_notification = get_site_option('wpcomments_enable_email_notification', false);
        $enable_private_comments = get_site_option('wpcomments_enable_private_comments', false);
        $enable_comment_limiter = get_site_option('wpcomments_enable_comment_limiter', false);
        $enable_report_comments = get_site_option('wpcomments_enable_report_comments', false);
        $enable_sticky_comments = get_site_option('wpcomments_enable_sticky_comments', false);
        $enable_gongan_beian = get_site_option('wpcomments_enable_gongan_beian', false);
        $allow_site_override = get_site_option('wpcomments_allow_site_override', true);
        $network_enabled = get_site_option('wpcomments_network_enabled', 1);
        $network_enforce = get_site_option('wpcomments_network_enforce', 0);
        
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'network';
        ?>
        <div class="wrap wpcomments-settings">
            <h1><?php esc_html_e('WPComments 网络设置', 'wpcomments'); ?>
                <span style="font-size: 13px; padding-left: 10px;"><?php printf(esc_html__('版本: %s', 'wpcomments'), esc_html(WPCOMMENTS_VERSION)); ?></span>
                <a href="https://github.com/WenPai-org/wpcomments" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="margin-left: 10px;"><?php esc_html_e('文档', 'wpcomments'); ?></a>
                <a href="https://github.com/WenPai-org/wpcomments/issues" target="_blank" rel="noopener noreferrer" class="button button-secondary"><?php esc_html_e('支持', 'wpcomments'); ?></a>
            </h1>

            <div id="wpcomments-status" class="notice" style="display:none; margin-top: 10px; padding: 8px 12px;"></div>
            
            <div class="wpcomments-card">
                <div class="wpcomments-tabs-wrapper">
                    <div class="wpcomments-sync-tabs">
                        <button type="button" class="wpcomments-tab <?php echo $active_tab === 'network' ? 'active' : ''; ?>" data-tab="network">
                            <?php _e('网络管理', 'wpcomments'); ?>
                        </button>
                        <button type="button" class="wpcomments-tab <?php echo $active_tab === 'basic' ? 'active' : ''; ?>" data-tab="basic">
                            <?php _e('基础设置', 'wpcomments'); ?>
                        </button>
                        <button type="button" class="wpcomments-tab <?php echo $active_tab === 'features' ? 'active' : ''; ?>" data-tab="features">
                            <?php _e('功能增强', 'wpcomments'); ?>
                        </button>
                        <button type="button" class="wpcomments-tab <?php echo $active_tab === 'management' ? 'active' : ''; ?>" data-tab="management">
                            <?php _e('管理工具', 'wpcomments'); ?>
                        </button>
                        <button type="button" class="wpcomments-tab <?php echo $active_tab === 'notifications' ? 'active' : ''; ?>" data-tab="notifications">
                            <?php _e('通知设置', 'wpcomments'); ?>
                        </button>
                        <button type="button" class="wpcomments-tab <?php echo $active_tab === 'tools' ? 'active' : ''; ?>" data-tab="tools">
                            <?php _e('实用工具', 'wpcomments'); ?>
                        </button>
                    </div>
                </div>

                <!-- Network Management Section -->
                <div class="wpcomments-section" id="wpcomments-section-network" style="<?php echo $active_tab !== 'network' ? 'display: none;' : ''; ?>">
                    <h2><?php _e('网络管理设置', 'wpcomments'); ?></h2>
                    <p class="wpcomments-section-desc"><?php _e('配置多站点网络的WPComments管理方式。', 'wpcomments'); ?></p>

                    <form method="post" action="<?php echo esc_url(network_admin_url('edit.php?action=wpcomments_network_settings')); ?>" id="wpcomments-network-form">
                        <?php wp_nonce_field('wpcomments_network_settings_nonce'); ?>
                        <input type="hidden" name="tab" value="network">

                        <table class="form-table">
                            <tr>
                                <th><?php _e('启用网络级管理', 'wpcomments'); ?></th>
                                <td>
                                    <label class="wpcomments-switch">
                                        <input type="checkbox" name="wpcomments_network_enabled" value="1" <?php checked($network_enabled); ?>>
                                        <span class="wpcomments-slider"></span>
                                        <span class="wpcomments-switch-label"><?php _e('在所有站点使用网络设置', 'wpcomments'); ?></span>
                                    </label>
                                    <p class="description"><?php _e('启用后，所有子站点将使用选定的网络设置，子站点的对应设置将被覆盖', 'wpcomments'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('强制使用网络设置', 'wpcomments'); ?></th>
                                <td>
                                    <label class="wpcomments-switch">
                                        <input type="checkbox" name="wpcomments_network_enforce" value="1" <?php checked($network_enforce); ?>>
                                        <span class="wpcomments-slider"></span>
                                        <span class="wpcomments-switch-label"><?php _e('完全禁用子站点设置页面', 'wpcomments'); ?></span>
                                    </label>
                                    <p class="description"><?php _e('启用后，子站点将无法访问WPComments设置页面，完全由网络管理', 'wpcomments'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('网络控制选项', 'wpcomments'); ?></th>
                                <td>
                                    <?php
                                    // Get current controlled options
                                    $controlled_options = get_site_option('wpcomments_network_controlled_options', array(
                                        'wpcomments_disable_comments',
                                        'wpcomments_enable_validation',
                                        'wpcomments_enable_role_badge'
                                    ));
                                    if (!is_array($controlled_options)) {
                                        $controlled_options = explode(',', $controlled_options);
                                    }

                                    // Define all available options with user-friendly labels
                                    $all_options = [
                                        'wpcomments_disable_comments' => __('禁用评论功能', 'wpcomments'),
                                        'wpcomments_enable_validation' => __('评论表单验证', 'wpcomments'),
                                        'wpcomments_enable_remove_website_field' => __('移除网站链接字段', 'wpcomments'),
                                        'wpcomments_enable_role_badge' => __('用户角色徽章', 'wpcomments'),
                                        'wpcomments_enable_delete_pending' => __('删除待审核评论', 'wpcomments'),
                                        'wpcomments_enable_frequently_replies' => __('常用回复功能', 'wpcomments'),
                                        'wpcomments_enable_moderation_info' => __('评论审核信息', 'wpcomments'),
                                        'wpcomments_enable_email_notification' => __('邮件通知功能', 'wpcomments'),
                                        'wpcomments_enable_private_comments' => __('私密评论功能', 'wpcomments'),
                                        'wpcomments_enable_comment_limiter' => __('评论字符限制', 'wpcomments'),
                                        'wpcomments_enable_report_comments' => __('评论举报系统', 'wpcomments'),
                                        'wpcomments_report_logged_in_only' => __('仅登录用户可举报（受控）', 'wpcomments'),
                                        'wpcomments_report_min_interval' => __('举报最小间隔（分钟）（受控）', 'wpcomments'),
                                        'wpcomments_report_retention_days' => __('举报保留天数（受控）', 'wpcomments'),
                                        'wpcomments_report_privacy_notice' => __('举报隐私提示（受控）', 'wpcomments'),
                                        'wpcomments_enable_sticky_comments' => __('置顶评论功能', 'wpcomments'),
                                        'wpcomments_enable_gongan_beian' => __('公安备案合规辅助', 'wpcomments'),
                                    ];
                                    // Group options by category
                                    $option_groups = [
                                        'basic' => [
                                            'title' => __('基础设置', 'wpcomments'),
                                            'options' => [
                                                'wpcomments_disable_comments',
                                                'wpcomments_enable_validation',
                                                'wpcomments_enable_remove_website_field',
                                                'wpcomments_enable_private_comments',
                                                'wpcomments_enable_comment_limiter'
                                            ]
                                        ],
                                        'features' => [
                                            'title' => __('功能增强', 'wpcomments'),
                                            'options' => [
                                                'wpcomments_enable_role_badge',
                                                'wpcomments_enable_report_comments',
                                                'wpcomments_report_logged_in_only',
                                                'wpcomments_report_min_interval',
                                                'wpcomments_report_retention_days',
                                                'wpcomments_report_privacy_notice'
                                            ]
                                        ],
                                        'management' => [
                                            'title' => __('管理工具', 'wpcomments'),
                                            'options' => [
                                                'wpcomments_enable_delete_pending',
                                                'wpcomments_enable_frequently_replies',
                                                'wpcomments_enable_moderation_info',
                                                'wpcomments_enable_sticky_comments',
                                                'wpcomments_enable_gongan_beian'
                                            ]
                                        ],
                                        'notifications' => [
                                            'title' => __('通知设置', 'wpcomments'),
                                            'options' => [
                                                'wpcomments_enable_email_notification'
                                            ]
                                        ]
                                    ];

                                    // Output options by group
                                    echo '<div class="network-control-options">';
                                    foreach ($option_groups as $group => $group_data) {
                                        echo '<div class="option-group">';
                                        echo '<h4>' . esc_html($group_data['title']) . '</h4>';

                                        foreach ($group_data['options'] as $option) {
                                            echo '<label class="wpcomments-checkbox">';
                                            echo '<input type="checkbox" name="wpcomments_network_controlled_options[]" value="' . esc_attr($option) . '" ' .
                                                 (in_array($option, $controlled_options) ? 'checked' : '') . '>';
                                            echo '<span class="wpcomments-checkbox-label">' . esc_html($all_options[$option]) . '</span>';
                                            echo '</label><br>';
                                        }

                                        echo '</div>';
                                    }
                                    echo '</div>';
                                    ?>
                                    <p class="description"><?php _e('选中的选项将在所有站点使用网络设置，未选中的选项可由站点管理员自定义。', 'wpcomments'); ?></p>
                                    <div class="wpcomments-action-buttons" style="margin-top:10px;">
                                        <button type="button" id="select-all-options" class="button button-secondary"><?php _e('全选', 'wpcomments'); ?></button>
                                        <button type="button" id="deselect-all-options" class="button button-secondary"><?php _e('取消全选', 'wpcomments'); ?></button>
                                        <button type="button" id="reset-default-options" class="button button-secondary"><?php _e('恢复默认', 'wpcomments'); ?></button>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <div class="wpcomments-submit-wrapper">
                            <button type="submit" class="button button-primary"><?php _e('保存设置', 'wpcomments'); ?></button>
                        </div>
                    </form>
                </div>

                <!-- Basic Settings Section -->
                <div class="wpcomments-section" id="wpcomments-section-basic" style="<?php echo $active_tab !== 'basic' ? 'display: none;' : ''; ?>">
                    <h2><?php _e('基础设置', 'wpcomments'); ?></h2>
                    <p class="wpcomments-section-desc"><?php _e('配置评论系统的核心功能，应用于所有网络站点。', 'wpcomments'); ?></p>

                    <form method="post" action="<?php echo esc_url(network_admin_url('edit.php?action=wpcomments_network_settings')); ?>" id="wpcomments-basic-form">
                        <?php wp_nonce_field('wpcomments_network_settings_nonce'); ?>
                        <input type="hidden" name="tab" value="basic">
                    
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">网络级别控制</th>
                                <td>
                                    <fieldset>
                                        <label for="wpcomments_network_allow_site_override">
                                            <input type="hidden" name="wpcomments_allow_site_override" value="0">
                            <input type="checkbox" id="wpcomments_allow_site_override" name="wpcomments_allow_site_override" value="1" <?php checked(1, $allow_site_override); ?>>
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
                                            <input type="hidden" name="wpcomments_disable_comments" value="0">
                            <input type="checkbox" id="wpcomments_disable_comments" name="wpcomments_disable_comments" value="1" <?php checked(1, $disable_comments); ?>>
                                            网络级别禁用评论功能
                                        </label>
                                        <p class="description">启用后将在整个网络中完全禁用评论系统，包括前台显示和后台管理。</p><br>
                                    </fieldset>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">表单验证设置</th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text">表单验证设置</legend>
                                        
                                        <label for="wpcomments_network_enable_validation">
                                            <input type="hidden" name="wpcomments_enable_validation" value="0">
                            <input type="checkbox" id="wpcomments_enable_validation" name="wpcomments_enable_validation" value="1" <?php checked(1, $enable_validation); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            启用评论表单验证功能
                                        </label>
                                        <p class="description">启用后，评论表单将进行客户端验证，确保必填字段已填写且格式正确。</p><br>
                                        
                                        <label for="wpcomments_network_enable_remove_website_field">
                                            <input type="hidden" name="wpcomments_enable_remove_website_field" value="0">
                            <input type="checkbox" id="wpcomments_enable_remove_website_field" name="wpcomments_enable_remove_website_field" value="1" <?php checked(1, $enable_remove_website_field); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            移除网站链接字段
                                        </label>
                                        <p class="description">启用后，将在评论表单中隐藏网站URL字段，减少垃圾评论和恶意链接的提交。</p><br>
                                        
                                        <label for="wpcomments_network_enable_private_comments">
                                            <input type="hidden" name="wpcomments_enable_private_comments" value="0">
                            <input type="checkbox" id="wpcomments_enable_private_comments" name="wpcomments_enable_private_comments" value="1" <?php checked(1, $enable_private_comments); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            启用私密评论功能
                                        </label>
                                        <p class="description">启用后，用户可以选择将评论设为私密，只有网站管理员可以查看。</p><br>
                                        
                                        <label for="wpcomments_network_enable_comment_limiter">
                                            <input type="hidden" name="wpcomments_enable_comment_limiter" value="0">
                            <input type="checkbox" id="wpcomments_enable_comment_limiter" name="wpcomments_enable_comment_limiter" value="1" <?php checked(1, $enable_comment_limiter); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            启用评论字符限制
                                        </label>
                                        <p class="description">启用后，可以限制评论的最小和最大字符数，提高评论质量。</p>
                                        
                                        <p class="description"><?php echo $disable_comments ? '网络级别已禁用评论功能，以下选项不可设置。' : '这些设置将作为新站点的默认配置。如果启用了"允许各站点覆盖网络设置"，各站点可以独立修改这些设置。'; ?></p>
                                    </fieldset>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="wpcomments-submit-wrapper">
                        <button type="submit" class="button button-primary"><?php _e('保存基础设置', 'wpcomments'); ?></button>
                    </div>
                    </form>
                </div>



                <!-- Features Section -->
                <div class="wpcomments-section" id="wpcomments-section-features" style="<?php echo $active_tab !== 'features' ? 'display: none;' : ''; ?>">
                    <h2><?php _e('功能增强', 'wpcomments'); ?></h2>
                    <p class="wpcomments-section-desc"><?php _e('配置评论显示和交互增强功能，应用于所有网络站点。', 'wpcomments'); ?></p>

                    <form method="post" action="<?php echo esc_url(network_admin_url('edit.php?action=wpcomments_network_settings')); ?>" id="wpcomments-features-form">
                        <?php wp_nonce_field('wpcomments_network_settings_nonce'); ?>
                        <input type="hidden" name="tab" value="features">

                        <table class="form-table">
                            <tr>
                                <th><?php _e('用户角色徽章', 'wpcomments'); ?></th>
                                <td>
                                    <label class="wpcomments-switch">
                                        <input type="checkbox" name="wpcomments_enable_role_badge" value="1" <?php checked($enable_role_badge); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                        <span class="wpcomments-slider"></span>
                                        <span class="wpcomments-switch-label"><?php _e('在评论中显示用户角色徽章', 'wpcomments'); ?></span>
                                    </label>
                                    <p class="description"><?php _e('启用后，将在评论者姓名旁显示其用户角色徽章，如管理员、编辑者等，便于识别用户身份。', 'wpcomments'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('评论举报系统', 'wpcomments'); ?></th>
                                <td>
                                    <label class="wpcomments-switch">
                                        <input type="checkbox" name="wpcomments_enable_report_comments" value="1" <?php checked($enable_report_comments); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                        <span class="wpcomments-slider"></span>
                                        <span class="wpcomments-switch-label"><?php _e('启用评论举报功能', 'wpcomments'); ?></span>
                                    </label>
                                    <p class="description"><?php _e('启用后，用户可以举报不当评论，当举报次数达到阈值时，评论将自动转为待审核状态。', 'wpcomments'); ?></p>

                                    <fieldset style="margin-top:10px;">
                                        <label>
                                            <input type="hidden" name="wpcomments_report_logged_in_only" value="0">
                                            <input type="checkbox" name="wpcomments_report_logged_in_only" value="1" <?php checked(get_site_option('wpcomments_report_logged_in_only', 0)); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                            <?php _e('仅登录用户可举报', 'wpcomments'); ?>
                                        </label>
                                        <p class="description"><?php _e('启用后，未登录用户将无法发起举报。', 'wpcomments'); ?></p>

                                        <label>
                                            <?php _e('最小举报间隔（分钟）', 'wpcomments'); ?>
                                            <input type="number" name="wpcomments_report_min_interval" value="<?php echo esc_attr(get_site_option('wpcomments_report_min_interval', 10)); ?>" min="1" max="1440" class="small-text" <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                        </label>
                                        <p class="description"><?php _e('同一主体（登录用户或来源IP）针对同一评论的最小间隔，默认 10 分钟。', 'wpcomments'); ?></p>

                                        <label>
                                            <?php _e('举报数据保留天数', 'wpcomments'); ?>
                                            <input type="number" name="wpcomments_report_retention_days" value="<?php echo esc_attr(get_site_option('wpcomments_report_retention_days', 90)); ?>" min="1" max="3650" class="small-text" <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                        </label>
                                        <p class="description"><?php _e('超过该天数的举报记录将自动清理（每日）。', 'wpcomments'); ?></p>

                                        <label>
                                            <?php _e('举报隐私提示（支持 {days}）', 'wpcomments'); ?>
                                            <input type="text" name="wpcomments_report_privacy_notice" value="<?php echo esc_attr(get_site_option('wpcomments_report_privacy_notice', __('举报将记录必要信息用于防刷与审核，数据保留 {days} 天。', 'wpcomments'))); ?>" class="regular-text" <?php echo $disable_comments ? 'disabled' : ''; ?> />
                                        </label>
                                        <p class="description"><?php _e('该提示用于举报按钮的提示文本与无障碍标签，{days} 将替换为保留天数。', 'wpcomments'); ?></p>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>

                        <div class="wpcomments-submit-wrapper">
                            <button type="submit" class="button button-primary"><?php _e('保存功能设置', 'wpcomments'); ?></button>
                        </div>
                    </form>
                </div>

                <!-- Management Tools Section -->
                <div class="wpcomments-section" id="wpcomments-section-management" style="<?php echo $active_tab !== 'management' ? 'display: none;' : ''; ?>">
                    <h2><?php _e('管理工具', 'wpcomments'); ?></h2>
                    <p class="wpcomments-section-desc"><?php _e('配置评论管理和审核工具，应用于所有网络站点。', 'wpcomments'); ?></p>

                    <form method="post" action="<?php echo esc_url(network_admin_url('edit.php?action=wpcomments_network_settings')); ?>" id="wpcomments-management-form">
                        <?php wp_nonce_field('wpcomments_network_settings_nonce'); ?>
                        <input type="hidden" name="tab" value="management">
                        <input type="hidden" name="wpcomments_enable_delete_pending" value="0">
                        <input type="hidden" name="wpcomments_enable_frequently_replies" value="0">
                        <input type="hidden" name="wpcomments_enable_moderation_info" value="0">
                        <input type="hidden" name="wpcomments_enable_sticky_comments" value="0">
                        <input type="hidden" name="wpcomments_enable_gongan_beian" value="0">

                        <table class="form-table">
                            <tr>
                                <th><?php _e('删除待审核评论', 'wpcomments'); ?></th>
                                <td>
                                    <label class="wpcomments-switch">
                                        <input type="checkbox" name="wpcomments_enable_delete_pending" value="1" <?php checked($enable_delete_pending); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                        <span class="wpcomments-slider"></span>
                                        <span class="wpcomments-switch-label"><?php _e('启用批量删除待审核评论功能', 'wpcomments'); ?></span>
                                    </label>
                                    <p class="description"><?php _e('启用后，管理员可以批量删除所有待审核状态的评论，提高评论管理效率。', 'wpcomments'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th><?php _e('常用回复功能', 'wpcomments'); ?></th>
                                <td>
                                    <label class="wpcomments-switch">
                                        <input type="checkbox" name="wpcomments_enable_frequently_replies" value="1" <?php checked($enable_frequently_replies); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                        <span class="wpcomments-slider"></span>
                                        <span class="wpcomments-switch-label"><?php _e('启用常用回复模板功能', 'wpcomments'); ?></span>
                                    </label>
                                    <p class="description"><?php _e('启用后，您可以创建和管理常用回复模板，在回复评论时快速插入预设内容。', 'wpcomments'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('评论审核信息', 'wpcomments'); ?></th>
                                <td>
                                    <label class="wpcomments-switch">
                                        <input type="checkbox" name="wpcomments_enable_moderation_info" value="1" <?php checked($enable_moderation_info); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                        <span class="wpcomments-slider"></span>
                                        <span class="wpcomments-switch-label"><?php _e('启用审核信息显示功能', 'wpcomments'); ?></span>
                                    </label>
                                    <p class="description"><?php _e('启用后，将显示评论审核状态信息，帮助管理员了解评论的审核进度。', 'wpcomments'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('置顶评论功能', 'wpcomments'); ?></th>
                                <td>
                                    <label class="wpcomments-switch">
                                        <input type="checkbox" name="wpcomments_enable_sticky_comments" value="1" <?php checked($enable_sticky_comments); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                        <span class="wpcomments-slider"></span>
                                        <span class="wpcomments-switch-label"><?php _e('启用评论置顶功能', 'wpcomments'); ?></span>
                                    </label>
                                    <p class="description"><?php _e('启用后，管理员可以将重要评论置顶显示，置顶评论将始终显示在评论列表的顶部。', 'wpcomments'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('公安备案合规辅助', 'wpcomments'); ?></th>
                                <td>
                                    <label class="wpcomments-switch">
                                        <input type="checkbox" name="wpcomments_enable_gongan_beian" value="1" <?php checked($enable_gongan_beian); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                        <span class="wpcomments-slider"></span>
                                        <span class="wpcomments-switch-label"><?php _e('在后台评论列表显示来源IP、端口与精确时间，并在插入评论时记录端口', 'wpcomments'); ?></span>
                                    </label>
                                    <p class="description"><?php _e('启用后，将在后台评论列表中显示评论的来源IP、端口与精确时间，并在插入评论时记录端口信息，以满足公安备案相关合规要求（具体以当地监管要求为准）。', 'wpcomments'); ?></p>
                                </td>
                            </tr>
                        </table>

                        <div class="wpcomments-submit-wrapper">
                            <button type="submit" class="button button-primary"><?php _e('保存管理设置', 'wpcomments'); ?></button>
                        </div>
                    </form>
                </div>

                <!-- Notifications Section -->
                <div class="wpcomments-section" id="wpcomments-section-notifications" style="<?php echo $active_tab !== 'notifications' ? 'display: none;' : ''; ?>">
                    <h2><?php _e('通知设置', 'wpcomments'); ?></h2>
                    <p class="wpcomments-section-desc"><?php _e('配置邮件通知和订阅功能，应用于所有网络站点。', 'wpcomments'); ?></p>

                    <form method="post" action="<?php echo esc_url(network_admin_url('edit.php?action=wpcomments_network_settings')); ?>" id="wpcomments-notifications-form">
                        <?php wp_nonce_field('wpcomments_network_settings_nonce'); ?>
                        <input type="hidden" name="tab" value="notifications">
                        <input type="hidden" name="wpcomments_enable_email_notification" value="0">

                        <table class="form-table">
                            <tr>
                                <th><?php _e('邮件通知功能', 'wpcomments'); ?></th>
                                <td>
                                    <label class="wpcomments-switch">
                                        <input type="checkbox" name="wpcomments_enable_email_notification" value="1" <?php checked($enable_email_notification); ?> <?php echo $disable_comments ? 'disabled' : ''; ?>>
                                        <span class="wpcomments-slider"></span>
                                        <span class="wpcomments-switch-label"><?php _e('启用评论邮件通知系统', 'wpcomments'); ?></span>
                                    </label>
                                    <p class="description"><?php _e('启用后，将向管理员和用户发送评论相关的邮件通知。', 'wpcomments'); ?></p>
                                </td>
                            </tr>
                        </table>

                        <div class="wpcomments-submit-wrapper">
                            <button type="submit" class="button button-primary"><?php _e('保存通知设置', 'wpcomments'); ?></button>
                        </div>
                    </form>
                </div>

                <!-- Tools Section -->
                <div class="wpcomments-section" id="wpcomments-section-tools" style="<?php echo $active_tab !== 'tools' ? 'display: none;' : ''; ?>">
                    <h2><?php _e('实用工具', 'wpcomments'); ?></h2>
                    <p class="wpcomments-section-desc"><?php _e('网络级别的批量操作和管理工具。', 'wpcomments'); ?></p>

                    <div class="wpcomments-stats-card">
                        <h3><?php _e('网络统计', 'wpcomments'); ?></h3>
                        <p><?php printf(__('当前网络共有 %d 个站点', 'wpcomments'), get_blog_count()); ?></p>
                        
                        <?php
                        // 获取网络评论统计
                        global $wpdb;
                        $total_comments = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments}");
                        $pending_comments = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = '0'");
                        ?>
                        
                        <p><?php printf(__('网络总评论数: %d 条', 'wpcomments'), $total_comments); ?></p>
                        <p><?php printf(__('待审核评论数: %d 条', 'wpcomments'), $pending_comments); ?></p>
                    </div>

                    <div class="wpcomments-stats-card">
                        <h3><?php _e('批量操作', 'wpcomments'); ?></h3>
                        <p><?php _e('对整个网络执行批量操作，请谨慎使用。', 'wpcomments'); ?></p>
                        <div class="wpcomments-action-buttons">
                            <button type="button" class="button button-secondary" id="sync-all-sites">
                                <?php _e('同步设置到所有站点', 'wpcomments'); ?>
                            </button>
                            <button type="button" class="button button-secondary" id="export-network-settings">
                                <?php _e('导出网络设置', 'wpcomments'); ?>
                            </button>
                            <button type="button" class="button button-secondary" id="import-network-settings">
                                <?php _e('导入网络设置', 'wpcomments'); ?>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <?php
    }
    
    public function save_network_settings() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(wp_unslash($_POST['_wpnonce']), 'wpcomments_network_settings_nonce')) {
            wp_die('安全验证失败');
        }
        
        if (!current_user_can('manage_network_options')) {
            wp_die('权限不足');
        }
        
        $tab = isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : 'network';
        
        // Save settings based on the current tab
        switch ($tab) {
            case 'network':
                $network_enabled = isset($_POST['wpcomments_network_enabled']) ? (bool) $_POST['wpcomments_network_enabled'] : false;
                $network_enforce = isset($_POST['wpcomments_network_enforce']) ? (bool) $_POST['wpcomments_network_enforce'] : false;
                $allowed_options = array(
                    'wpcomments_disable_comments', 'wpcomments_enable_validation',
                    'wpcomments_enable_remove_website_field', 'wpcomments_enable_private_comments',
                    'wpcomments_enable_comment_limiter', 'wpcomments_enable_role_badge',
                    'wpcomments_enable_report_comments', 'wpcomments_report_logged_in_only',
                    'wpcomments_report_min_interval', 'wpcomments_report_retention_days',
                    'wpcomments_report_privacy_notice', 'wpcomments_enable_delete_pending',
                    'wpcomments_enable_frequently_replies', 'wpcomments_enable_moderation_info',
                    'wpcomments_enable_sticky_comments', 'wpcomments_enable_gongan_beian',
                    'wpcomments_enable_email_notification',
                );
                $controlled_options = isset($_POST['wpcomments_network_controlled_options'])
                    ? array_values(array_intersect(
                        $allowed_options,
                        array_map('sanitize_key', (array) wp_unslash($_POST['wpcomments_network_controlled_options']))
                    ))
                    : array();
                
                update_site_option('wpcomments_network_enabled', $network_enabled);
                update_site_option('wpcomments_network_enforce', $network_enforce);
                update_site_option('wpcomments_network_controlled_options', $controlled_options);
                break;
                
            case 'basic':
                $disable_comments = isset($_POST['wpcomments_disable_comments']) ? (bool) $_POST['wpcomments_disable_comments'] : false;
                $allow_site_override = isset($_POST['wpcomments_allow_site_override']) ? (bool) $_POST['wpcomments_allow_site_override'] : false;

                // 先保存禁用开关与覆盖策略
                update_site_option('wpcomments_disable_comments', $disable_comments);
                update_site_option('wpcomments_allow_site_override', $allow_site_override);

                // 读取 POST 值（若未提交则保持现值），并在禁用评论时不覆盖原有设置
                $current_validation = (bool) get_site_option('wpcomments_enable_validation', false);
                $current_remove_website = (bool) get_site_option('wpcomments_enable_remove_website_field', false);
                $current_private = (bool) get_site_option('wpcomments_enable_private_comments', false);
                $current_limiter = (bool) get_site_option('wpcomments_enable_comment_limiter', false);

                $enable_validation = isset($_POST['wpcomments_enable_validation']) ? (bool) $_POST['wpcomments_enable_validation'] : $current_validation;
                $enable_remove_website_field = isset($_POST['wpcomments_enable_remove_website_field']) ? (bool) $_POST['wpcomments_enable_remove_website_field'] : $current_remove_website;
                $enable_private_comments = isset($_POST['wpcomments_enable_private_comments']) ? (bool) $_POST['wpcomments_enable_private_comments'] : $current_private;
                $enable_comment_limiter = isset($_POST['wpcomments_enable_comment_limiter']) ? (bool) $_POST['wpcomments_enable_comment_limiter'] : $current_limiter;

                if ($disable_comments) {
                    // 当网络禁用评论时，不更新这些从 UI 上被禁用的选项，避免被隐藏域的 0 覆盖
                    $enable_validation = $current_validation;
                    $enable_remove_website_field = $current_remove_website;
                    $enable_private_comments = $current_private;
                    $enable_comment_limiter = $current_limiter;
                }

                update_site_option('wpcomments_enable_validation', $enable_validation);
                update_site_option('wpcomments_enable_remove_website_field', $enable_remove_website_field);
                update_site_option('wpcomments_enable_private_comments', $enable_private_comments);
                update_site_option('wpcomments_enable_comment_limiter', $enable_comment_limiter);
                break;
                
            case 'features':
                $enable_role_badge = isset($_POST['wpcomments_enable_role_badge']) ? (bool) $_POST['wpcomments_enable_role_badge'] : false;
                $enable_report_comments = isset($_POST['wpcomments_enable_report_comments']) ? (bool) $_POST['wpcomments_enable_report_comments'] : false;
                $report_logged_in_only = isset($_POST['wpcomments_report_logged_in_only']) ? (bool) $_POST['wpcomments_report_logged_in_only'] : false;
                $report_min_interval = isset($_POST['wpcomments_report_min_interval']) ? min(1440, absint($_POST['wpcomments_report_min_interval'])) : 10;
                $report_retention_days = isset($_POST['wpcomments_report_retention_days']) ? max(1, min(3650, absint($_POST['wpcomments_report_retention_days']))) : 90;
                
                update_site_option('wpcomments_enable_role_badge', $enable_role_badge);
                update_site_option('wpcomments_enable_report_comments', $enable_report_comments);
                update_site_option('wpcomments_report_logged_in_only', $report_logged_in_only);
                update_site_option('wpcomments_report_min_interval', $report_min_interval);
                update_site_option('wpcomments_report_retention_days', $report_retention_days);
                if (isset($_POST['wpcomments_report_privacy_notice'])) {
                    update_site_option('wpcomments_report_privacy_notice', sanitize_text_field($_POST['wpcomments_report_privacy_notice']));
                }
                break;
                
            case 'management':
                $enable_delete_pending = isset($_POST['wpcomments_enable_delete_pending']) ? (bool) $_POST['wpcomments_enable_delete_pending'] : false;
                $enable_frequently_replies = isset($_POST['wpcomments_enable_frequently_replies']) ? (bool) $_POST['wpcomments_enable_frequently_replies'] : false;
                $enable_moderation_info = isset($_POST['wpcomments_enable_moderation_info']) ? (bool) $_POST['wpcomments_enable_moderation_info'] : false;
                $enable_sticky_comments = isset($_POST['wpcomments_enable_sticky_comments']) ? (bool) $_POST['wpcomments_enable_sticky_comments'] : false;
                $enable_gongan_beian = isset($_POST['wpcomments_enable_gongan_beian']) ? (bool) $_POST['wpcomments_enable_gongan_beian'] : false;
                
                update_site_option('wpcomments_enable_delete_pending', $enable_delete_pending);
                update_site_option('wpcomments_enable_frequently_replies', $enable_frequently_replies);
                update_site_option('wpcomments_enable_moderation_info', $enable_moderation_info);
                update_site_option('wpcomments_enable_sticky_comments', $enable_sticky_comments);
                update_site_option('wpcomments_enable_gongan_beian', $enable_gongan_beian);
                break;
                
            case 'notifications':
                $enable_email_notification = isset($_POST['wpcomments_enable_email_notification']) ? (bool) $_POST['wpcomments_enable_email_notification'] : false;
                
                update_site_option('wpcomments_enable_email_notification', $enable_email_notification);
                break;
        }
        
        wp_redirect(add_query_arg(array('page' => $this->page_slug, 'settings-updated' => 'true', 'tab' => $tab), network_admin_url('settings.php')));
        exit;
    }
    
    public function enqueue_admin_styles($hook) {
        if ('settings_page_' . $this->page_slug . '-network' !== $hook) {
            return;
        }
        
        // 使用统一的管理界面样式
        wp_enqueue_style(
            'wpcomments-admin',
            WPCOMMENTS_URL . 'assets/css/admin.css',
            [],
            WPCOMMENTS_VERSION
        );
    }
    
    
    public function enqueue_admin_assets($hook) {
        if ('settings_page_' . $this->page_slug !== $hook) {
            return;
        }

        // 脚本
        wp_enqueue_script('jquery');
        wp_enqueue_script('wpcomments-admin', WPCOMMENTS_URL . 'assets/js/admin.js', array('jquery'), WPCOMMENTS_VERSION, true);

        // 样式
        wp_enqueue_style(
            'wpcomments-admin',
            WPCOMMENTS_URL . 'assets/css/admin.css',
            [],
            WPCOMMENTS_VERSION
        );

        // 本地化数据
        wp_localize_script('wpcomments-admin', 'wpcomments', [
            'nonce' => wp_create_nonce('wpcomments_admin_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'plugin_url' => WPCOMMENTS_URL,
            'assets_url' => WPCOMMENTS_URL . 'assets/',
            'is_network_admin' => '1',
            'is_multisite' => is_multisite() ? '1' : '0',
        ]);

        wp_localize_script('wpcomments-admin', 'wpcomments_l10n', [
            'settings_saved' => __('设置已成功保存。', 'wpcomments')
        ]);
    }

    public static function get_effective_setting($setting_name, $default = false) {
        if (!is_multisite()) {
            return get_option($setting_name, $default);
        }

        // 网络级管理开关
        $network_enabled = (bool) get_site_option('wpcomments_network_enabled', 1);
        $network_enforce = (bool) get_site_option('wpcomments_network_enforce', 0);
        $allow_override  = (bool) get_site_option('wpcomments_allow_site_override', true);

        $controlled_options = get_site_option('wpcomments_network_controlled_options', array());
        if (!is_array($controlled_options)) {
            $controlled_options = explode(',', (string) $controlled_options);
        }

        // 完全强制或不允许覆盖：一律返回网络值
        if ($network_enforce || !$allow_override) {
            return get_site_option($setting_name, $default);
        }

        // 网络启用且该选项受控：返回网络值
        if ($network_enabled && in_array($setting_name, $controlled_options, true)) {
            return get_site_option($setting_name, $default);
        }

        // 其余情况：返回站点值
        return get_option($setting_name, $default);
    }
}
