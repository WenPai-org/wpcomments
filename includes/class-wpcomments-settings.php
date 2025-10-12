<?php

/**
 * WPComments Settings Class
 *
 * @package WPComments
 * @since   2.0.0
 */

namespace WPComments;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * WPComments Settings Class
 */
class WPComments_Settings
{

    /**
     * Settings tabs.
     *
     * @var array
     */
    private $tabs = array();

    /**
     * Current tab.
     *
     * @var string
     */
    private $current_tab = 'general';

    /**
     * Option group.
     *
     * @var string
     */
    private $option_group = 'wpcomments_settings';

    // Separate option groups per tab to avoid cross-reset when saving via options.php
    private $group_basic = 'wpcomments_settings_basic';
    private $group_features = 'wpcomments_settings_features';
    private $group_management = 'wpcomments_settings_management';
    private $group_notifications = 'wpcomments_settings_notifications';

    /**
     * Page slug.
     *
     * @var string
     */
    private $page_slug = 'wpcomments-settings';

    /**
     * Constructor.
     */
    public function __construct()
    {
        // 多站点：仅当网络设置明确强制并且网络禁用评论时，才完全不加载站点设置页
        if (is_multisite()) {
            $network_enabled   = (bool) get_site_option('wpcomments_network_enabled', 1);
            $network_enforce   = (bool) get_site_option('wpcomments_network_enforce', 0);
            $allow_override    = (bool) get_site_option('wpcomments_allow_site_override', true);
            $controlled_options = get_site_option('wpcomments_network_controlled_options', array());
            if (!is_array($controlled_options)) {
                $controlled_options = explode(',', (string) $controlled_options);
            }

            $network_controls_disable = ($network_enforce || !$allow_override || ($network_enabled && in_array('wpcomments_disable_comments', $controlled_options, true)));
            if ($network_controls_disable) {
                $network_disable_comments = (bool) get_site_option('wpcomments_disable_comments', 0);
                if ($network_disable_comments) {
                    return;
                }
            }
        }

        $site_disable_comments = get_option('wpcomments_disable_comments', 0);
        if ($site_disable_comments) {
            return;
        }

        $this->init_tabs();
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'handle_form_submission'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('update_option_wpcomments_enable_role_badge', array($this, 'handle_setting_update'));
        add_action('update_option_wpcomments_enable_delete_pending', array($this, 'handle_setting_update'));
        add_action('update_option_wpcomments_enable_validation', array($this, 'handle_setting_update'));
    }

    private function init_tabs()
    {
        $this->tabs = array(
            'basic' => array(
                'title' => '基础设置',
                'description' => '评论系统的核心功能控制'
            ),
            'features' => array(
                'title' => '功能增强',
                'description' => '评论显示和交互增强功能'
            ),
            'management' => array(
                'title' => '管理工具',
                'description' => '评论管理和审核工具'
            ),
            'notifications' => array(
                'title' => '通知设置',
                'description' => '邮件通知和订阅配置'
            ),
            'advanced' => array(
                'title' => '高级设置',
                'description' => '高级选项和调试功能'
            )
        );

        $this->current_tab = isset($_GET['tab']) && array_key_exists($_GET['tab'], $this->tabs)
            ? sanitize_text_field($_GET['tab'])
            : 'basic';
    }

    public function add_admin_menu()
    {
        if (current_user_can('manage_options')) {
            add_options_page(
                'WPComments Settings',
                'WPComments',
                'manage_options',
                $this->page_slug,
                array($this, 'render_settings_page')
            );
        }
    }

    public function register_settings()
    {
        // 基础设置选项（Basic Tab）
        $settings_basic = array(
            'wpcomments_disable_comments',
            'wpcomments_enable_validation',
            'wpcomments_enable_remove_website_field',
            'wpcomments_enable_private_comments',
            'wpcomments_enable_comment_limiter'
        );
        foreach ($settings_basic as $setting) {
            register_setting($this->group_basic, $setting, array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_checkbox'),
                'default' => false
            ));
        }
        // 复杂数组选项（Basic Tab）
        register_setting($this->group_basic, 'wpcomments_private_comment_options', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_private_comment_options'),
            'default' => array(
                'default_checked' => false,
                'label_text' => __('Keep this comment private (visible to site owners only)', 'wpcomments')
            )
        ));
        register_setting($this->group_basic, 'wpcomments_comment_limiter_options', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_limiter_options'),
            'default' => array(
                'maximum_characters' => 1000,
                'maximum_message' => __('Your comment exceeds the maximum limit allowed. Please try to reduce it.', 'wpcomments'),
                'minimum_characters' => 20,
                'minimum_message' => __('Your comment is below the minimum allowed limit. Please try to be more expressive.', 'wpcomments'),
                'enable_admin_feature' => 'no'
            )
        ));

        // 功能增强（Features Tab）
        $settings_features = array(
            'wpcomments_enable_role_badge',
            'wpcomments_enable_report_comments'
        );
        foreach ($settings_features as $setting) {
            register_setting($this->group_features, $setting, array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_checkbox'),
                'default' => false
            ));
        }
        register_setting($this->group_features, 'wpcomments_report_threshold', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 3
        ));
        register_setting($this->group_features, 'wpcomments_report_privacy_notice', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => __('举报将记录必要信息用于防刷与审核，数据保留 {days} 天。', 'wpcomments')
        ));
        register_setting($this->group_features, 'wpcomments_report_logged_in_only', array(
            'type' => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => false
        ));
        register_setting($this->group_features, 'wpcomments_report_min_interval', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 10
        ));
        register_setting($this->group_features, 'wpcomments_report_retention_days', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 90
        ));

        // 管理工具（Management Tab）
        $settings_management = array(
            'wpcomments_enable_delete_pending',
            'wpcomments_enable_frequently_replies',
            'wpcomments_enable_moderation_info',
            'wpcomments_enable_sticky_comments'
        );
        foreach ($settings_management as $setting) {
            register_setting($this->group_management, $setting, array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_checkbox'),
                'default' => false
            ));
        }

        // 通知设置（Notifications Tab）
        register_setting($this->group_notifications, 'wpcomments_enable_email_notification', array(
            'type' => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => false
        ));
    }

    public function sanitize_checkbox($value)
    {
        return !empty($value) ? 1 : 0;
    }
    
    public function sanitize_private_comment_options($options) {
        $sanitized = array();
        $sanitized['default_checked'] = !empty($options['default_checked']) ? 1 : 0;
        $sanitized['label_text'] = sanitize_text_field($options['label_text'] ?? '');
        return $sanitized;
    }
    
    public function sanitize_limiter_options($options) {
        $sanitized = array();
        $sanitized['maximum_characters'] = absint($options['maximum_characters'] ?? 1000);
        $sanitized['minimum_characters'] = absint($options['minimum_characters'] ?? 20);
        $sanitized['maximum_message'] = sanitize_text_field($options['maximum_message'] ?? '');
        $sanitized['minimum_message'] = sanitize_text_field($options['minimum_message'] ?? '');
        $sanitized['enable_admin_feature'] = in_array($options['enable_admin_feature'] ?? 'no', array('yes', 'no')) ? $options['enable_admin_feature'] : 'no';
        
        // 验证逻辑：最大值必须大于最小值
        if ($sanitized['maximum_characters'] <= $sanitized['minimum_characters']) {
            add_settings_error('wpcomments_settings', 'invalid_limiter_values', __('Maximum characters must be greater than minimum characters.', 'wpcomments'));
            // 返回之前的设置
            return get_option('wpcomments_comment_limiter_options');
        }
        
        return $sanitized;
    }
    
    /**
     * 处理表单提交
     */
    public function handle_form_submission() {
        // 如果使用了 WordPress Settings API（options.php 提交），不要在此拦截，避免覆盖和提前重定向
        if (isset($_POST['option_page']) && in_array($_POST['option_page'], [
            $this->group_basic,
            $this->group_features,
            $this->group_management,
            $this->group_notifications,
            $this->option_group // 兼容旧分组
        ], true)) {
            return;
        }

        if (!isset($_POST['wpcomments_basic_nonce']) && !isset($_POST['wpcomments_features_nonce']) && 
            !isset($_POST['wpcomments_management_nonce']) && !isset($_POST['wpcomments_notifications_nonce']) && 
            !isset($_POST['wpcomments_advanced_nonce'])) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // 确定是哪个标签页的提交
        $tab = 'basic';
        if (isset($_POST['wpcomments_basic_nonce']) && wp_verify_nonce($_POST['wpcomments_basic_nonce'], 'wpcomments_basic_nonce')) {
            $tab = 'basic';
        } elseif (isset($_POST['wpcomments_features_nonce']) && wp_verify_nonce($_POST['wpcomments_features_nonce'], 'wpcomments_features_nonce')) {
            $tab = 'features';
        } elseif (isset($_POST['wpcomments_management_nonce']) && wp_verify_nonce($_POST['wpcomments_management_nonce'], 'wpcomments_management_nonce')) {
            $tab = 'management';
        } elseif (isset($_POST['wpcomments_notifications_nonce']) && wp_verify_nonce($_POST['wpcomments_notifications_nonce'], 'wpcomments_notifications_nonce')) {
            $tab = 'notifications';
        } elseif (isset($_POST['wpcomments_advanced_nonce']) && wp_verify_nonce($_POST['wpcomments_advanced_nonce'], 'wpcomments_advanced_nonce')) {
            $tab = 'advanced';
        } else {
            wp_die(__('Security check failed.'));
        }
        
        // 处理不同标签页的设置
        switch ($tab) {
            case 'basic':
                $this->save_basic_settings();
                break;
            case 'features':
                $this->save_features_settings();
                break;
            case 'management':
                $this->save_management_settings();
                break;
            case 'notifications':
                $this->save_notifications_settings();
                break;
            case 'advanced':
                $this->save_advanced_settings();
                break;
        }
        
        // 重定向到设置页面并显示成功消息
        wp_redirect(add_query_arg(array(
            'page' => $this->page_slug,
            'tab' => $tab,
            'settings-updated' => 'true'
        ), admin_url('options-general.php')));
        exit;
    }
    
    private function save_basic_settings() {
        // 基础设置
        update_option('wpcomments_disable_comments', !empty($_POST['wpcomments_disable_comments']));
        update_option('wpcomments_enable_validation', !empty($_POST['wpcomments_enable_validation']));
        update_option('wpcomments_enable_remove_website_field', !empty($_POST['wpcomments_enable_remove_website_field']));
        update_option('wpcomments_enable_private_comments', !empty($_POST['wpcomments_enable_private_comments']));
        update_option('wpcomments_enable_comment_limiter', !empty($_POST['wpcomments_enable_comment_limiter']));
        
        // 私密评论详细设置
        if (isset($_POST['wpcomments_private_comment_options'])) {
            $private_options = array(
                'default_checked' => !empty($_POST['wpcomments_private_comment_options']['default_checked']),
                'label_text' => sanitize_text_field($_POST['wpcomments_private_comment_options']['label_text'] ?? '')
            );
            update_option('wpcomments_private_comment_options', $private_options);
        }
        
        // 字符限制详细设置
        if (isset($_POST['wpcomments_comment_limiter_options'])) {
            $limiter_options = array(
                'minimum_characters' => absint($_POST['wpcomments_comment_limiter_options']['minimum_characters'] ?? 20),
                'maximum_characters' => absint($_POST['wpcomments_comment_limiter_options']['maximum_characters'] ?? 1000),
                'minimum_message' => sanitize_text_field($_POST['wpcomments_comment_limiter_options']['minimum_message'] ?? ''),
                'maximum_message' => sanitize_text_field($_POST['wpcomments_comment_limiter_options']['maximum_message'] ?? ''),
                'enable_admin_feature' => sanitize_text_field($_POST['wpcomments_comment_limiter_options']['enable_admin_feature'] ?? 'no')
            );
            
            // 验证最大值必须大于最小值
            if ($limiter_options['maximum_characters'] <= $limiter_options['minimum_characters']) {
                add_settings_error('wpcomments_settings', 'invalid_limiter_values', __('Maximum characters must be greater than minimum characters.', 'wpcomments'));
                return;
            }
            
            update_option('wpcomments_comment_limiter_options', $limiter_options);
        }
    }
    
    private function save_features_settings() {
        update_option('wpcomments_enable_role_badge', !empty($_POST['wpcomments_enable_role_badge']));
        update_option('wpcomments_enable_report_comments', !empty($_POST['wpcomments_enable_report_comments']));
        
        // 举报阈值设置
        if (isset($_POST['wpcomments_report_threshold'])) {
            $threshold = absint($_POST['wpcomments_report_threshold']);
            if ($threshold < 1) $threshold = 3;
            update_option('wpcomments_report_threshold', $threshold);
        }
    }
    
    private function save_management_settings() {
        update_option('wpcomments_enable_delete_pending', !empty($_POST['wpcomments_enable_delete_pending']));
        update_option('wpcomments_enable_frequently_replies', !empty($_POST['wpcomments_enable_frequently_replies']));
        update_option('wpcomments_enable_moderation_info', !empty($_POST['wpcomments_enable_moderation_info']));
        update_option('wpcomments_enable_sticky_comments', !empty($_POST['wpcomments_enable_sticky_comments']));
    }
    
    private function save_notifications_settings() {
        update_option('wpcomments_enable_email_notification', !empty($_POST['wpcomments_enable_email_notification']));
    }
    
    private function save_advanced_settings() {
        // 高级设置处理
    }

    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // 检查多站点网络控制
        if (is_multisite()) {
            $network_enabled = get_site_option('wpcomments_network_enabled', 1);
            $network_enforce = get_site_option('wpcomments_network_enforce', 0);
            $network_controlled_options = get_site_option('wpcomments_network_controlled_options', array());

            if (!is_array($network_controlled_options)) {
                $network_controlled_options = explode(',', $network_controlled_options);
            }
        }

        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'basic';
?>
        <div class="wrap wpcomments-settings">
            <h1><?php esc_html_e('WPComments 设置', 'wpcomments'); ?>
                <span style="font-size: 13px; padding-left: 10px;"><?php printf(esc_html__('版本: %s', 'wpcomments'), esc_html(WPCOMMENTS_VERSION)); ?></span>
                <a href="https://github.com/wpcomments/wpcomments" target="_blank" class="button button-secondary" style="margin-left: 10px;"><?php esc_html_e('文档', 'wpcomments'); ?></a>
                <a href="https://github.com/wpcomments/wpcomments/issues" target="_blank" class="button button-secondary"><?php esc_html_e('支持', 'wpcomments'); ?></a>
            </h1>

            <div id="wpcomments-status" class="notice" style="display:none; margin-top: 10px;"></div>
            <div class="wpcomments-card">
                <div class="wpcomments-tabs-wrapper">
                    <div class="wpcomments-sync-tabs">
                        <?php foreach ($this->tabs as $tab_key => $tab_info): ?>
                            <button type="button" class="wpcomments-tab <?php echo $active_tab === $tab_key ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($tab_key); ?>">
                                <?php echo esc_html($tab_info['title']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php foreach ($this->tabs as $tab_key => $tab_info): ?>
                    <div class="wpcomments-section" id="wpcomments-section-<?php echo esc_attr($tab_key); ?>" style="<?php echo $active_tab !== $tab_key ? 'display: none;' : ''; ?>">
                        <h2><?php echo esc_html($tab_info['title']); ?></h2>
                        <p class="wpcomments-section-desc"><?php echo esc_html($tab_info['description']); ?></p>

                        <?php if (is_multisite() && $network_enabled): ?>
                            <div class="wpcomments-network-notice">
                                <p>
                                    <?php
                                    $controlled_count = 0;
                                    $option_names = [
                                        'wpcomments_disable_comments' => __('禁用评论功能', 'wpcomments'),
                                        'wpcomments_enable_validation' => __('评论表单验证', 'wpcomments'),
                                        'wpcomments_enable_remove_website_field' => __('移除网站链接字段', 'wpcomments'),
                                        'wpcomments_enable_role_badge' => __('用户角色徽章', 'wpcomments'),
                                        'wpcomments_enable_delete_pending' => __('删除待审核评论', 'wpcomments'),
                                        'wpcomments_enable_frequently_replies' => __('常用回复功能', 'wpcomments'),
'wpcomments_enable_moderation_info' => __('评论审核信息', 'wpcomments'),
                                        'wpcomments_enable_email_notification' => __('邮件通知功能', 'wpcomments'),
                                        'wpcomments_enable_report_comments' => __('评论举报系统', 'wpcomments'),
                                        'wpcomments_report_logged_in_only' => __('仅登录用户可举报', 'wpcomments'),
                                        'wpcomments_report_min_interval' => __('举报最小间隔（分钟）', 'wpcomments'),
                                        'wpcomments_report_retention_days' => __('举报数据保留天数', 'wpcomments'),
                                        'wpcomments_report_privacy_notice' => __('举报隐私提示', 'wpcomments'),
                                        'wpcomments_enable_sticky_comments' => __('置顶评论功能', 'wpcomments'),
                                    ];

                                    foreach ($option_names as $option => $label) {
                                        if (in_array($option, $network_controlled_options)) {
                                            echo '<span class="dashicons dashicons-lock"></span> ' . esc_html($label) . '<br>';
                                            $controlled_count++;
                                        }
                                    }
                                    ?>

                                    <?php if ($controlled_count > 0): ?>
                                        <em><?php printf(_n('以上 %d 项设置由网络管理员控制，您的更改将不会生效。', '以上 %d 项设置由网络管理员控制，您的更改将不会生效。', $controlled_count, 'wpcomments'), $controlled_count); ?></em>
                                    <?php endif; ?>

                                    <?php if ($network_enforce): ?>
                                        <br><strong><?php _e('注意：网络管理员已强制使用网络设置，所有更改将被忽略。', 'wpcomments'); ?></strong>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php $this->render_tab_content($tab_key); ?>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    <?php
    }

    private function render_tab_content($tab_key)
    {
        switch ($tab_key) {
            case 'basic':
                $this->render_basic_tab();
                break;
            case 'features':
                $this->render_features_tab();
                break;
            case 'management':
                $this->render_management_tab();
                break;
            case 'notifications':
                $this->render_notifications_tab();
                break;
            case 'advanced':
                $this->render_advanced_tab();
                break;
        }
    }

    private function render_basic_tab()
    {
        // 检查网络控制状态
        $network_disabled = '';
        if (is_multisite()) {
            $network_enabled = get_site_option('wpcomments_network_enabled', 1);
            $network_enforce = get_site_option('wpcomments_network_enforce', 0);
            $network_controlled_options = get_site_option('wpcomments_network_controlled_options', array());

            if (!is_array($network_controlled_options)) {
                $network_controlled_options = explode(',', $network_controlled_options);
            }

            if ($network_enforce || ($network_enabled && in_array('wpcomments_disable_comments', $network_controlled_options))) {
                $network_disabled = 'disabled';
            }
        }

    ?>
        <form method="post" action="options.php" id="wpcomments-basic-form">
            <?php settings_fields($this->group_basic); ?>
            <table class="form-table">
                <tr>
                    <th><?php _e('评论系统状态', 'wpcomments'); ?></th>
                    <td>
                        <label class="wpcomments-switch">
                            <input type="hidden" name="wpcomments_disable_comments" value="0">
                            <input type="checkbox" name="wpcomments_disable_comments" value="1" <?php checked(get_option('wpcomments_disable_comments', false)); ?> <?php echo $network_disabled; ?>>
                            <span class="wpcomments-slider"></span>
                            <span class="wpcomments-switch-label"><?php _e('完全禁用评论功能', 'wpcomments'); ?></span>
                        </label>
                        <p class="description"><?php _e('启用后将完全禁用WordPress的评论系统，包括前台显示和后台管理。', 'wpcomments'); ?></p>
                    </td>
                </tr>

                <?php $this->render_checkbox_field(array('option_name' => 'wpcomments_enable_validation')); ?>
                <?php $this->render_checkbox_field(array('option_name' => 'wpcomments_enable_remove_website_field')); ?>
                <?php $this->render_checkbox_field(array('option_name' => 'wpcomments_enable_private_comments')); ?>
                <?php $this->render_checkbox_field(array('option_name' => 'wpcomments_enable_comment_limiter')); ?>
            </table>
            
            <?php $effective_private_enabled = class_exists('WPComments\\WPComments_Network_Settings') 
                ? \WPComments\WPComments_Network_Settings::get_effective_setting('wpcomments_enable_private_comments', get_option('wpcomments_enable_private_comments', false))
                : get_option('wpcomments_enable_private_comments', false);
            if ($effective_private_enabled): ?>
            <div class="wpcomments-stats-card">
                <h3><?php _e('私密评论设置', 'wpcomments'); ?></h3>
                <?php $private_options = get_option('wpcomments_private_comment_options', array()); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('默认选中', 'wpcomments'); ?></th>
                        <td>
                            <label class="wpcomments-switch">
                                <input type="checkbox" name="wpcomments_private_comment_options[default_checked]" value="1" <?php checked(!empty($private_options['default_checked'])); ?>>
                                <span class="wpcomments-slider"></span>
                                <span class="wpcomments-switch-label"><?php _e('默认勾选私密评论选项', 'wpcomments'); ?></span>
                            </label>
                            <p class="description"><?php _e('启用后，评论表单中的私密评论复选框将默认被选中。', 'wpcomments'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('标签文字', 'wpcomments'); ?></th>
                        <td>
                            <input type="text" name="wpcomments_private_comment_options[label_text]" value="<?php echo esc_attr($private_options['label_text'] ?? __('Keep this comment private (visible to site owners only)', 'wpcomments')); ?>" class="regular-text">
                            <p class="description"><?php _e('私密评论复选框旁显示的文字。', 'wpcomments'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>
            
            <?php $effective_limiter_enabled = class_exists('WPComments\\WPComments_Network_Settings') 
                ? \WPComments\WPComments_Network_Settings::get_effective_setting('wpcomments_enable_comment_limiter', get_option('wpcomments_enable_comment_limiter', false))
                : get_option('wpcomments_enable_comment_limiter', false);
            if ($effective_limiter_enabled): ?>
            <div class="wpcomments-stats-card">
                <h3><?php _e('评论字符限制设置', 'wpcomments'); ?></h3>
                <?php $limiter_options = get_option('wpcomments_comment_limiter_options', array()); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('最小字符数', 'wpcomments'); ?></th>
                        <td>
                            <input type="number" name="wpcomments_comment_limiter_options[minimum_characters]" value="<?php echo esc_attr($limiter_options['minimum_characters'] ?? 20); ?>" min="1" max="500" class="small-text">
                            <p class="description"><?php _e('评论最少需要多少个字符。', 'wpcomments'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('最大字符数', 'wpcomments'); ?></th>
                        <td>
                            <input type="number" name="wpcomments_comment_limiter_options[maximum_characters]" value="<?php echo esc_attr($limiter_options['maximum_characters'] ?? 1000); ?>" min="50" max="5000" class="small-text">
                            <p class="description"><?php _e('评论最多允许多少个字符。', 'wpcomments'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('最小字符错误消息', 'wpcomments'); ?></th>
                        <td>
                            <input type="text" name="wpcomments_comment_limiter_options[minimum_message]" value="<?php echo esc_attr($limiter_options['minimum_message'] ?? __('Your comment is below the minimum allowed limit. Please try to be more expressive.', 'wpcomments')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('最大字符错误消息', 'wpcomments'); ?></th>
                        <td>
                            <input type="text" name="wpcomments_comment_limiter_options[maximum_message]" value="<?php echo esc_attr($limiter_options['maximum_message'] ?? __('Your comment exceeds the maximum limit allowed. Please try to reduce it.', 'wpcomments')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('管理员豁免', 'wpcomments'); ?></th>
                        <td>
                            <label class="wpcomments-switch">
                                <input type="checkbox" name="wpcomments_comment_limiter_options[enable_admin_feature]" value="yes" <?php checked($limiter_options['enable_admin_feature'] ?? 'no', 'yes'); ?>>
                                <span class="wpcomments-slider"></span>
                                <span class="wpcomments-switch-label"><?php _e('管理员不受字符限制', 'wpcomments'); ?></span>
                            </label>
                            <p class="description"><?php _e('启用后，管理员发表评论时不受字符数限制。', 'wpcomments'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="wpcomments-submit-wrapper">
                <?php wp_nonce_field('wpcomments_basic_nonce', 'wpcomments_basic_nonce'); ?>
                <button type="submit" class="button button-primary"><?php _e('保存设置', 'wpcomments'); ?></button>
            </div>
        </form>
    <?php
    }

    public function render_checkbox_field($args)
    {
        $option_name = $args['option_name'];
        $value = get_option($option_name, false);

        // 检查多站点网络控制
        $disabled = '';
        if (is_multisite()) {
            $network_enabled = get_site_option('wpcomments_network_enabled', 1);
            $network_enforce = get_site_option('wpcomments_network_enforce', 0);
            $network_controlled_options = get_site_option('wpcomments_network_controlled_options', array());

            if (!is_array($network_controlled_options)) {
                $network_controlled_options = explode(',', $network_controlled_options);
            }

            // 如果网络强制模式或该选项被网络控制，则禁用
            if ($network_enforce || ($network_enabled && in_array($option_name, $network_controlled_options))) {
                $disabled = 'disabled';
                // 如果被网络控制，使用网络设置的值
                if (in_array($option_name, $network_controlled_options)) {
                    $network_value = get_site_option($option_name, $value);
                    $value = $network_value;
                }
            }
        }
    ?>
        <tr>
            <th><?php
                switch ($option_name) {
                    case 'wpcomments_disable_comments':
                        echo '禁用评论功能';
                        break;
                    case 'wpcomments_enable_role_badge':
                        echo '启用角色徽章';
                        break;
                    case 'wpcomments_enable_delete_pending':
                        echo '自动删除待审核评论';
                        break;
                    case 'wpcomments_enable_remove_website_field':
                        echo '移除网站链接字段';
                        break;
                    case 'wpcomments_enable_frequently_replies':
                        echo '启用常用回复功能';
                        break;
                    case 'wpcomments_enable_validation':
                        echo '启用评论表单验证';
                        break;
                    case 'wpcomments_enable_moderation_info':
                        echo '启用评论审核信息';
                        break;
                    case 'wpcomments_enable_email_notification':
                        echo '启用邮件通知功能';
                        break;
                    case 'wpcomments_enable_private_comments':
                        echo '启用私密评论';
                        break;
                    case 'wpcomments_enable_comment_limiter':
                        echo '启用评论字符限制';
                        break;
                    case 'wpcomments_enable_report_comments':
                        echo '启用评论举报系统';
                        break;
                    case 'wpcomments_enable_sticky_comments':
                        echo '启用置顶评论功能';
                        break;
                }
                ?></th>
            <td>
                <label class="wpcomments-switch">
                    <input type="hidden" name="<?php echo esc_attr($option_name); ?>" value="0" />
                    <input type="checkbox" name="<?php echo esc_attr($option_name); ?>" value="1" <?php checked($value); ?> <?php echo $disabled; ?>>
                    <span class="wpcomments-slider"></span>
                    <span class="wpcomments-switch-label"><?php
                                                            switch ($option_name) {
                                                                case 'wpcomments_enable_role_badge':
                                                                    echo '在评论中显示用户角色徽章';
                                                                    break;
                                                                case 'wpcomments_enable_delete_pending':
                                                                    echo '自动删除待审核评论';
                                                                    break;
                                                                case 'wpcomments_enable_remove_website_field':
                                                                    echo '移除评论表单中的网站链接字段';
                                                                    break;
                                                                case 'wpcomments_enable_frequently_replies':
                                                                    echo '启用常用回复功能';
                                                                    break;
                                                                case 'wpcomments_enable_validation':
                                                                    echo '启用评论表单验证';
                                                                    break;
                                                                case 'wpcomments_enable_moderation_info':
                                                                    echo '启用评论审核信息';
                                                                    break;
                                                                case 'wpcomments_enable_private_comments':
                                                                    echo '允许用户提交私密评论';
                                                                    break;
                                                                case 'wpcomments_enable_comment_limiter':
                                                                    echo '限制评论字符数量';
                                                                    break;
                                                                case 'wpcomments_enable_report_comments':
                                                                    echo '允许用户举报不当评论';
                                                                    break;
                                                                case 'wpcomments_enable_sticky_comments':
                                                                    echo '允许管理员置顶重要评论';
                                                                    break;
                                                            }
                                                            ?></span>
                </label>
                <p class="description"><?php
                                        switch ($option_name) {
                                            case 'wpcomments_enable_role_badge':
                                                echo '在评论作者名称旁显示其用户角色（如管理员、编辑等）。';
                                                break;
                                            case 'wpcomments_enable_delete_pending':
                                                echo '自动删除超过指定时间的待审核评论。';
                                                break;
                                            case 'wpcomments_enable_remove_website_field':
                                                echo '隐藏评论表单中的网站URL输入框。';
                                                break;
                                            case 'wpcomments_enable_frequently_replies':
                                                echo '启用后，您可以创建和管理常用回复模板，在回复评论时快速插入预设内容。';
                                                break;
                                            case 'wpcomments_enable_validation':
                                                echo '启用前端评论表单验证，包括必填字段检查、邮箱格式验证等，并阻止包含活跃链接的评论。';
                                                break;
                                            case 'wpcomments_enable_moderation_info':
                                                echo '在评论管理页面显示评论的修订历史信息，包括最后修改时间和编辑者信息。';
                                                break;
                                            case 'wpcomments_enable_private_comments':
                                                echo '启用后，用户可以选择将评论设为私密，只有网站管理员可以查看。私密评论不会公开显示。';
                                                break;
                                            case 'wpcomments_enable_comment_limiter':
                                                echo '限制评论的最小和最大字符数，防止过短或过长的评论，提高评论质量。';
                                                break;
                                            case 'wpcomments_enable_report_comments':
                                                echo '允许用户举报不当评论，当举报次数达到阈值时，评论将自动转为待审核状态。';
                                                break;
                                            case 'wpcomments_enable_sticky_comments':
                                                echo '启用后，管理员可以将重要评论置顶显示，置顶评论将始终显示在评论列表的顶部。';
                                                break;
                                        }
                                        ?></p>
            </td>
        </tr>
    <?php
    }

    private function render_features_tab()
    {
    ?>
        <form method="post" action="options.php" id="wpcomments-features-form">
            <?php settings_fields($this->group_features); ?>
            <table class="form-table">
                <?php
                $this->render_checkbox_field(array('option_name' => 'wpcomments_enable_role_badge'));
                $this->render_checkbox_field(array('option_name' => 'wpcomments_enable_report_comments'));
                ?>
            </table>
            
            <?php 
            $effective_report_enabled = class_exists('WPComments\\WPComments_Network_Settings') 
                ? \WPComments\WPComments_Network_Settings::get_effective_setting('wpcomments_enable_report_comments', get_option('wpcomments_enable_report_comments', false))
                : get_option('wpcomments_enable_report_comments', false);
            if ($effective_report_enabled): 
                // 计算网络控制状态
                $network_enabled = is_multisite() ? (bool) get_site_option('wpcomments_network_enabled', 1) : false;
                $network_enforce = is_multisite() ? (bool) get_site_option('wpcomments_network_enforce', 0) : false;
                $network_controlled_options = is_multisite() ? get_site_option('wpcomments_network_controlled_options', array()) : array();
                if (!is_array($network_controlled_options)) {
                    $network_controlled_options = explode(',', (string) $network_controlled_options);
                }
                $disabled_logged_in    = ($network_enforce || ($network_enabled && in_array('wpcomments_report_logged_in_only', $network_controlled_options, true))) ? 'disabled' : '';
                $disabled_min_interval = ($network_enforce || ($network_enabled && in_array('wpcomments_report_min_interval', $network_controlled_options, true))) ? 'disabled' : '';
                $disabled_retention    = ($network_enforce || ($network_enabled && in_array('wpcomments_report_retention_days', $network_controlled_options, true))) ? 'disabled' : '';
                $disabled_privacy      = ($network_enforce || ($network_enabled && in_array('wpcomments_report_privacy_notice', $network_controlled_options, true))) ? 'disabled' : '';

                $val_privacy = class_exists('WPComments\\WPComments_Network_Settings') 
                    ? \WPComments\WPComments_Network_Settings::get_effective_setting('wpcomments_report_privacy_notice', get_option('wpcomments_report_privacy_notice', __('举报将记录必要信息用于防刷与审核，数据保留 {days} 天。', 'wpcomments')))
                    : get_option('wpcomments_report_privacy_notice', __('举报将记录必要信息用于防刷与审核，数据保留 {days} 天。', 'wpcomments'));
                $val_logged_in = class_exists('WPComments\\WPComments_Network_Settings') 
                    ? (bool) \WPComments\WPComments_Network_Settings::get_effective_setting('wpcomments_report_logged_in_only', get_option('wpcomments_report_logged_in_only', 0))
                    : (bool) get_option('wpcomments_report_logged_in_only', 0);
                $val_min_interval = class_exists('WPComments\\WPComments_Network_Settings') 
                    ? (int) \WPComments\WPComments_Network_Settings::get_effective_setting('wpcomments_report_min_interval', get_option('wpcomments_report_min_interval', 10))
                    : (int) get_option('wpcomments_report_min_interval', 10);
                $val_retention = class_exists('WPComments\\WPComments_Network_Settings') 
                    ? (int) \WPComments\WPComments_Network_Settings::get_effective_setting('wpcomments_report_retention_days', get_option('wpcomments_report_retention_days', 90))
                    : (int) get_option('wpcomments_report_retention_days', 90);
            ?>
            <div class="wpcomments-stats-card">
                <h3><?php _e('举报系统设置', 'wpcomments'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><?php _e('举报阈值', 'wpcomments'); ?></th>
                        <td>
                            <input type="number" name="wpcomments_report_threshold" value="<?php echo esc_attr(get_option('wpcomments_report_threshold', 3)); ?>" min="1" max="10" class="small-text">
                            <p class="description"><?php _e('评论被举报多少次后自动转为待审核状态', 'wpcomments'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('举报隐私提示', 'wpcomments'); ?></th>
                        <td>
                            <input type="text" name="wpcomments_report_privacy_notice" value="<?php echo esc_attr($val_privacy); ?>" class="regular-text" <?php echo $disabled_privacy; ?> />
                            <p class="description"><?php _e('用于“举报”按钮的悬浮提示与无障碍描述。支持 {days} 占位符，将被替换为保留天数。', 'wpcomments'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('仅登录用户可举报', 'wpcomments'); ?></th>
                        <td>
                            <label class="wpcomments-switch">
                                <?php if (empty($disabled_logged_in)) : ?>
                                    <input type="hidden" name="wpcomments_report_logged_in_only" value="0">
                                <?php endif; ?>
                                <input type="checkbox" name="wpcomments_report_logged_in_only" value="1" <?php checked($val_logged_in); ?> <?php echo $disabled_logged_in; ?>>
                                <span class="wpcomments-slider"></span>
                                <span class="wpcomments-switch-label"><?php _e('启用后，仅登录用户可发起举报', 'wpcomments'); ?></span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('最小举报间隔（分钟）', 'wpcomments'); ?></th>
                        <td>
                            <input type="number" name="wpcomments_report_min_interval" value="<?php echo esc_attr($val_min_interval); ?>" min="1" max="1440" class="small-text" <?php echo $disabled_min_interval; ?>>
                            <p class="description"><?php _e('同一主体（登录用户或来源IP）针对同一评论的最小间隔，默认 10 分钟。', 'wpcomments'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('举报数据保留天数', 'wpcomments'); ?></th>
                        <td>
                            <input type="number" name="wpcomments_report_retention_days" value="<?php echo esc_attr($val_retention); ?>" min="1" max="3650" class="small-text" <?php echo $disabled_retention; ?>>
                            <p class="description"><?php _e('超过该天数的举报记录将自动清理（每日）。', 'wpcomments'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>
            <div class="wpcomments-submit-wrapper">
                <?php wp_nonce_field('wpcomments_features_nonce', 'wpcomments_features_nonce'); ?>
                <button type="submit" class="button button-primary"><?php _e('保存设置', 'wpcomments'); ?></button>
            </div>
        </form>
    <?php
    }

    private function render_management_tab()
    {
    ?>
        <form method="post" action="options.php" id="wpcomments-management-form">
            <?php settings_fields($this->group_management); ?>
            <table class="form-table">
                <?php
                $this->render_checkbox_field(array('option_name' => 'wpcomments_enable_delete_pending'));
                $this->render_checkbox_field(array('option_name' => 'wpcomments_enable_frequently_replies'));
                $this->render_checkbox_field(array('option_name' => 'wpcomments_enable_moderation_info'));
                $this->render_checkbox_field(array('option_name' => 'wpcomments_enable_sticky_comments'));
                ?>
            </table>

            <?php $effective_frequently_enabled = class_exists('WPComments\\WPComments_Network_Settings') 
                ? \WPComments\WPComments_Network_Settings::get_effective_setting('wpcomments_enable_frequently_replies', get_option('wpcomments_enable_frequently_replies', false))
                : get_option('wpcomments_enable_frequently_replies', false);
            if ($effective_frequently_enabled): ?>
                <div class="wpcomments-stats-card">
                    <h3><?php _e('常用回复管理', 'wpcomments'); ?></h3>
                    <p><?php _e('管理您的常用回复模板，提高评论回复效率。', 'wpcomments'); ?></p>
                    <div class="wpcomments-action-buttons">
                        <a href="<?php echo admin_url('edit-comments.php?page=wpcomments-frequently-replies'); ?>" class="button button-secondary">
                            <?php _e('管理常用回复', 'wpcomments'); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php $effective_delete_pending_enabled = class_exists('WPComments\\WPComments_Network_Settings') 
                ? \WPComments\WPComments_Network_Settings::get_effective_setting('wpcomments_enable_delete_pending', get_option('wpcomments_enable_delete_pending', false))
                : get_option('wpcomments_enable_delete_pending', false);
            if ($effective_delete_pending_enabled): ?>
                <div class="wpcomments-stats-card">
                    <h3><?php _e('删除待审核评论', 'wpcomments'); ?></h3>
                    <p><?php _e('批量删除站点内所有待审核状态的评论。', 'wpcomments'); ?></p>
                    <div class="wpcomments-action-buttons">
                        <a href="<?php echo admin_url('edit-comments.php?page=wpcomments-delete-pending'); ?>" class="button button-secondary">
                            <?php _e('打开删除工具', 'wpcomments'); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="wpcomments-submit-wrapper">
                <?php wp_nonce_field('wpcomments_management_nonce', 'wpcomments_management_nonce'); ?>
                <button type="submit" class="button button-primary"><?php _e('保存设置', 'wpcomments'); ?></button>
            </div>
        </form>
    <?php
    }

    private function render_notifications_tab()
    {
        if (!class_exists('WPComments\\WPComments_Email_Notification')) {
            echo '<div class="notice notice-error"><p>邮件通知功能类未加载。请确保插件正确安装。</p></div>';
            return;
        }

        $notification = new \WPComments\WPComments_Email_Notification();
        $notification->output_admin_settings_page();
    }



    private function render_advanced_tab()
    {
    ?>
        <div class="wpcomments-stats-card">
            <h3><?php _e('插件信息', 'wpcomments'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><?php _e('版本', 'wpcomments'); ?></th>
                    <td><?php echo esc_html(WPCOMMENTS_VERSION); ?></td>
                </tr>
                <tr>
                    <th><?php _e('插件路径', 'wpcomments'); ?></th>
                    <td><?php echo esc_html(WPCOMMENTS_PATH); ?></td>
                </tr>
                <tr>
                    <th><?php _e('网络激活', 'wpcomments'); ?></th>
                    <td><?php echo WPCOMMENTS_IS_NETWORK ? __('是', 'wpcomments') : __('否', 'wpcomments'); ?></td>
                </tr>
            </table>

            <div class="wpcomments-action-buttons">
                <a href="https://github.com/wpcomments/wpcomments" target="_blank" class="button button-secondary">
                    <?php _e('文档', 'wpcomments'); ?>
                </a>
                <a href="https://github.com/wpcomments/wpcomments/issues" target="_blank" class="button button-secondary">
                    <?php _e('报告问题', 'wpcomments'); ?>
                </a>
            </div>
        </div>
<?php
    }





    public function enqueue_admin_assets($hook)
    {
        if ('settings_page_' . $this->page_slug !== $hook) {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('wpcomments-admin', WPCOMMENTS_URL . 'assets/js/admin.js', array('jquery'), WPCOMMENTS_VERSION, true);

        // 使用统一的管理界面样式
        wp_enqueue_style(
            'wpcomments-admin',
            WPCOMMENTS_URL . 'assets/css/admin.css',
            [],
            WPCOMMENTS_VERSION
        );

        wp_localize_script('wpcomments-admin', 'wpcomments', [
            'nonce' => wp_create_nonce('wpcomments_admin_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'plugin_url' => WPCOMMENTS_URL,
            'assets_url' => WPCOMMENTS_URL . 'assets/',
            'is_network_admin' => '0',
            'is_multisite' => is_multisite() ? '1' : '0',
        ]);

        wp_localize_script('wpcomments-admin', 'wpcomments_l10n', [
            'settings_saved' => __('设置已成功保存。', 'wpcomments')
        ]);
    }

    public function handle_setting_update($old_value, $value = null, $option = null)
    {
        if (wp_doing_ajax() || wp_doing_cron()) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        add_action('shutdown', function () {
            if (!wp_next_scheduled('wpcomments_reinit_features')) {
                wp_schedule_single_event(time() + 1, 'wpcomments_reinit_features');
            }
        });
    }
}
