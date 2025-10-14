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
        return __('评论举报', 'wpcomments');
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
            wp_enqueue_script('wpcomments-reporting', WPCOMMENTS_URL . 'assets/js/reporting.js', array('jquery'), WPCOMMENTS_VERSION, true);
            
            wp_localize_script('wpcomments-reporting', 'wpcomments_reporting', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpcomments_report_comment'),
                'report_text' => __('举报', 'wpcomments'),
                'reported_text' => __('已举报', 'wpcomments'),
                'confirm_text' => __('确定要举报这条评论吗？', 'wpcomments'),
                'success_text' => __('举报已提交，感谢您的反馈', 'wpcomments'),
                'error_text' => __('举报失败，请稍后重试', 'wpcomments'),
                'already_reported_text' => __('您已经举报过这条评论', 'wpcomments'),
            ));
        }
    }
    
    public function ajax_report_comment() {
        check_ajax_referer('wpcomments_report_comment', 'nonce');
        
        $comment_id = intval($_POST['comment_id']);
        $reason = sanitize_text_field($_POST['reason']);
        
        if (!$comment_id) {
            wp_send_json_error(__('无效的评论ID', 'wpcomments'));
        }
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(__('评论不存在', 'wpcomments'));
        }
        
        $user_ip = $this->get_user_ip_hash();
        
        if ($this->has_user_reported($comment_id, $user_ip)) {
            wp_send_json_error(__('您已经举报过这条评论', 'wpcomments'));
        }
        
        $this->save_report($comment_id, $user_ip, $reason);
        
        wp_send_json_success(__('举报已提交，感谢您的反馈', 'wpcomments'));
    }
    
    public function add_report_link($content, $comment) {
        if (!is_admin() && !current_user_can('moderate_comments')) {
            $report_link = '<a href="#" class="wpcomments-report-link" data-comment-id="' . $comment->comment_ID . '">' . __('举报', 'wpcomments') . '</a>';
            $content .= '<div class="wpcomments-report-container">' . $report_link . '</div>';
        }
        return $content;
    }
    
    public function add_reports_admin_page() {
        add_comments_page(
            __('评论举报', 'wpcomments'),
            __('评论举报', 'wpcomments'),
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
        ");
        
        echo '<div class="wrap">';
        echo '<h1>' . __('评论举报', 'wpcomments') . '</h1>';
        
        if (empty($reports)) {
            echo '<p>' . __('暂无举报记录', 'wpcomments') . '</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('评论内容', 'wpcomments') . '</th>';
            echo '<th>' . __('评论作者', 'wpcomments') . '</th>';
            echo '<th>' . __('举报原因', 'wpcomments') . '</th>';
            echo '<th>' . __('举报时间', 'wpcomments') . '</th>';
            echo '<th>' . __('操作', 'wpcomments') . '</th>';
            echo '</tr></thead><tbody>';
            
            foreach ($reports as $report) {
                echo '<tr>';
                echo '<td>' . wp_trim_words($report->comment_content, 20) . '</td>';
                echo '<td>' . esc_html($report->comment_author) . '</td>';
                echo '<td>' . esc_html($report->reason) . '</td>';
                echo '<td>' . $report->report_date . '</td>';
                echo '<td><a href="' . admin_url('comment.php?action=editcomment&c=' . $report->comment_id) . '">' . __('查看评论', 'wpcomments') . '</a></td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        }
        
        echo '</div>';
    }
    
    private function get_user_ip_hash() {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
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
        
        $wpdb->insert(
            $table_name,
            array(
                'comment_id' => $comment_id,
                'user_ip_hash' => $user_ip_hash,
                'reason' => $reason,
                'report_date' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );
    }
}