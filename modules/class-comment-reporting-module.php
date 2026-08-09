<?php

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

class Comment_Reporting_Module implements WPComments_Module_Interface {
    
    public function id(): string {
        return 'comment_reporting';
    }
    
    public function is_enabled(array $settings): bool {
        return !empty($settings['comment_reporting']);
    }
    
    public function register(): void {
        $this->init();
    }
    
    public function get_name(): string {
        return esc_html__('评论举报', 'wpcomments');
    }
    
    public function get_description(): string {
        return __('允许用户举报不当评论', 'wpcomments');
    }
    
    private function init(): void {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_reporting_scripts'));
        add_action('wp_ajax_wpcomments_report_comment', array($this, 'ajax_report_comment'));
        add_action('wp_ajax_nopriv_wpcomments_report_comment', array($this, 'ajax_report_comment'));
        add_filter('comment_text', array($this, 'add_report_link'), 10, 2);
        add_action('admin_menu', array($this, 'add_reports_admin_page'));
    }
    
    public function enqueue_reporting_scripts() {
        if (is_singular() && comments_open()) {
            wp_enqueue_script('wpcomments-reporting', WPCOMMENTS_URL . 'assets/js/comment-report.js', array('jquery'), WPCOMMENTS_VERSION, true);
            
            wp_localize_script('wpcomments-reporting', 'wpcommentsReport', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpcomments_report_comment'),
                'reportText' => __('举报', 'wpcomments'),
                'thankYouMessage' => __('举报已提交，感谢您的反馈', 'wpcomments'),
                'errorMessage' => __('举报失败，请稍后重试', 'wpcomments'),
                'alreadyReportedMessage' => __('您已经举报过这条评论', 'wpcomments'),
            ));
        }
    }
    
    public function ajax_report_comment() {
        check_ajax_referer('wpcomments_report_comment', 'nonce');
        
        $comment_id = isset($_POST['comment_id']) ? absint($_POST['comment_id']) : 0;
        $reason = isset($_POST['reason'])
            ? sanitize_text_field(wp_unslash($_POST['reason']))
            : 'user_report';
        $reason = substr($reason, 0, 255);
        
        if (!$comment_id) {
            wp_send_json_error(__('无效的评论ID', 'wpcomments'));
        }
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(__('评论不存在', 'wpcomments'));
        }
        if ('1' !== (string) $comment->comment_approved) {
            wp_send_json_error(__('该评论不可举报', 'wpcomments'), 403);
        }

        $logged_in_only = $this->get_effective_setting('wpcomments_report_logged_in_only', false);
        if ($logged_in_only && !is_user_logged_in()) {
            wp_send_json_error(__('请登录后举报评论', 'wpcomments'), 403);
        }
        
        $user_ip = $this->get_user_ip_hash();
        
        if ($this->has_user_reported($comment_id, $user_ip)) {
            wp_send_json_error(__('您已经举报过这条评论', 'wpcomments'));
        }

        if ($this->has_recent_report($user_ip)) {
            wp_send_json_error(__('举报过于频繁，请稍后再试', 'wpcomments'), 429);
        }
        
        if (!$this->save_report($comment_id, $user_ip, $reason)) {
            wp_send_json_error(__('举报保存失败，请稍后再试', 'wpcomments'), 500);
        }
        
        wp_send_json_success(__('举报已提交，感谢您的反馈', 'wpcomments'));
    }
    
    public function add_report_link($content, $comment) {
        if (!$comment instanceof \WP_Comment) {
            return $content;
        }
        if (!is_admin() && !current_user_can('moderate_comments')) {
            $report_link = '<button type="button" class="wpcomments-report-btn" data-comment-id="' . esc_attr($comment->comment_ID) . '">' . esc_html__('举报', 'wpcomments') . '</button>';
            $content .= '<div class="wpcomments-report-container">' . $report_link . '</div>';
        }
        return $content;
    }
    
    public function add_reports_admin_page() {
        add_comments_page(
            esc_html__('评论举报', 'wpcomments'),
            esc_html__('评论举报', 'wpcomments'),
            'moderate_comments',
            'wpcomments-reports',
            array($this, 'render_reports_page')
        );
    }
    
    public function render_reports_page() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpcomments_reports';
        $reports = $wpdb->get_results("
            SELECT r.*, c.comment_content, c.comment_author, c.comment_date 
            FROM {$table_name} r 
            LEFT JOIN {$wpdb->comments} c ON r.comment_id = c.comment_ID 
            ORDER BY r.report_date DESC
            LIMIT 500
        ");
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('评论举报', 'wpcomments') . '</h1>';
        
        if (empty($reports)) {
            echo '<p>' . esc_html__('暂无举报记录', 'wpcomments') . '</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__('评论内容', 'wpcomments') . '</th>';
            echo '<th>' . esc_html__('评论作者', 'wpcomments') . '</th>';
            echo '<th>' . esc_html__('举报原因', 'wpcomments') . '</th>';
            echo '<th>' . esc_html__('举报时间', 'wpcomments') . '</th>';
            echo '<th>' . esc_html__('操作', 'wpcomments') . '</th>';
            echo '</tr></thead><tbody>';
            
            foreach ($reports as $report) {
                echo '<tr>';
                echo '<td>' . esc_html(wp_trim_words((string) $report->comment_content, 20)) . '</td>';
                echo '<td>' . esc_html($report->comment_author) . '</td>';
                echo '<td>' . esc_html($report->reason) . '</td>';
                echo '<td>' . esc_html($report->report_date) . '</td>';
                echo '<td><a href="' . esc_url(admin_url('comment.php?action=editcomment&c=' . absint($report->comment_id))) . '">' . esc_html__('查看评论', 'wpcomments') . '</a></td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        }
        
        echo '</div>';
    }
    
    private function get_user_ip_hash() {
        $ip = isset($_SERVER['REMOTE_ADDR'])
            ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
            : '';
        $ip = (string) apply_filters('wpcomments_report_client_ip', $ip);
        return hash('sha256', $ip . NONCE_SALT);
    }
    
    private function has_user_reported($comment_id, $user_ip_hash) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpcomments_reports';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE comment_id = %d AND user_ip_hash = %s",
            $comment_id,
            $user_ip_hash
        ));
        
        return $count > 0;
    }
    
    private function save_report($comment_id, $user_ip_hash, $reason) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpcomments_reports';
        
        $now = current_time('mysql');
        return false !== $wpdb->insert(
            $table_name,
            array(
                'comment_id' => $comment_id,
                'user_id' => get_current_user_id(),
                'user_ip' => $user_ip_hash,
                'user_ip_hash' => $user_ip_hash,
                'reason' => $reason,
                'report_time' => $now,
                'report_date' => $now,
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
        );
    }

    private function has_recent_report($user_ip_hash) {
        global $wpdb;
        $minutes = (int) $this->get_effective_setting('wpcomments_report_min_interval', 10);
        $minutes = max(0, min(1440, $minutes));
        if (0 === $minutes) {
            return false;
        }

        $table_name = $wpdb->prefix . 'wpcomments_reports';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE user_ip_hash = %s AND report_date >= ( NOW() - INTERVAL %d MINUTE )",
            $user_ip_hash,
            $minutes
        ));
        return (int) $count > 0;
    }

    private function get_effective_setting($name, $default) {
        if (class_exists('WPComments\\WPComments_Network_Settings')) {
            return WPComments_Network_Settings::get_effective_setting($name, $default);
        }
        return get_option($name, $default);
    }
}
