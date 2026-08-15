<?php

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 公安备案合规助手模块
 * 
 * 集成原独立插件“Gongan Beian”的核心功能：
 * - 在评论插入时记录用户端口（REMOTE_PORT）到评论元数据
 * - 在后台评论列表中新增列，展示 IP、端口与精确时间
 *
 * @since 2.0.0
 */
class Gongan_Beian_Module implements WPComments_Module_Interface {

    public function id(): string {
        return 'gongan_beian';
    }

    public function is_enabled(array $settings): bool {
        // 根据站点或网络有效设置决定是否启用
        $enabled = (bool) get_option('wpcomments_enable_gongan_beian', false);
        if (class_exists('WPComments\\WPComments_Network_Settings')) {
            $enabled = (bool) \WPComments\WPComments_Network_Settings::get_effective_setting('wpcomments_enable_gongan_beian', $enabled);
        }
        return $enabled;
    }

    public function register(): void {
        // 记录评论端口
        add_action('wp_insert_comment', array($this, 'record_comment_port'), 10, 2);

        // 后台列表列与输出
        add_filter('manage_edit-comments_columns', array($this, 'add_comments_columns'));
        add_action('manage_comments_custom_column', array($this, 'render_comments_custom_column'), 10, 2);

        // 简单的列宽样式
        add_action('admin_head', array($this, 'inject_admin_column_css'));
    }

    public function get_name(): string {
        return __('公安备案合规助手', 'wpcomments');
    }

    public function get_description(): string {
        return __('记录评论的 IP、端口与精确时间，辅助中国网站合规。', 'wpcomments');
    }

    /**
     * 在评论插入时记录用户端口到评论元数据
     * 使用与原插件一致的键名，以便兼容历史数据：yjwgaba_ipport
     */
    public function record_comment_port($comment_ID, $commentdata) {
        $ipport = isset($_SERVER['REMOTE_PORT']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_PORT'])) : '';
        if ($ipport !== '') {
            update_comment_meta($comment_ID, 'yjwgaba_ipport', $ipport);
        }
    }

    /**
     * 在“评论”列表中添加 IP & Port & Time 列
     */
    public function add_comments_columns($columns) {
        $new_columns = array();
        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;
            if ($key === 'author') {
                $new_columns['author_ip'] = __('IP & Port & Time', 'wpcomments');
            }
        }
        if (!isset($new_columns['author_ip'])) {
            $new_columns['author_ip'] = __('IP & Port & Time', 'wpcomments');
        }
        return $new_columns;
    }

    /**
     * 输出自定义列内容
     */
    public function render_comments_custom_column($column_name, $comment_id) {
        if ($column_name !== 'author_ip') {
            return;
        }

        $ip = get_comment_author_IP($comment_id);
        $port = get_comment_meta($comment_id, 'yjwgaba_ipport', true);
        $time = get_comment_date('Y-m-d H:i:s', $comment_id);

        $ip_display = esc_html($ip);
        $port_display = esc_html($port);
        $time_display = esc_html($time);

        echo esc_html($ip_display) . ' : ' . esc_html($port_display) . '<br><br>' . esc_html($time_display);
    }

    /**
     * 注入后台样式，控制列宽度
     */
    public function inject_admin_column_css() {
        echo '<style type="text/css">#author_ip { width: 160px; }</style>';
    }
}

?>
