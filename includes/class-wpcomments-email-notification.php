<?php
/**
 * Email notification functionality.
 *
 * @package WPComments
 * @since   1.0.0
 */

namespace WPComments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPComments_Email_Notification
 *
 * Handles email notification functionality for comments.
 */
class WPComments_Email_Notification {
    
    const VERSION = '1.0.0';

    /** @var bool */
    private static $hooks_registered = false;
    
    public function __construct() {
        if (self::$hooks_registered) {
            return;
        }
        self::$hooks_registered = true;
        add_action('wp_insert_comment', array($this, 'comment_notification'), 99, 2);
        add_action('wp_set_comment_status', array($this, 'comment_status_update'), 99, 2);
        add_filter('preprocess_comment', array($this, 'verify_comment_meta_data'));
        add_filter('comment_form_default_fields', array($this, 'comment_fields'));
        add_filter('comment_form_submit_field', array($this, 'comment_fields_logged_in'));
        add_action('comment_post', array($this, 'persist_subscription_opt_in'));
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_wpcomments_unsubscribe', array($this, 'ajax_unsubscribe'));
        add_action('wp_ajax_nopriv_wpcomments_unsubscribe', array($this, 'ajax_unsubscribe'));
    }
    
    public function init() {
        $request_uri = isset($_SERVER['REQUEST_URI'])
            ? wp_unslash($_SERVER['REQUEST_URI'])
            : '';
        if (preg_match('/wpcomments\/unsubscribe/', $request_uri)) {
            $comment_id = filter_input(INPUT_GET, 'comment', FILTER_SANITIZE_NUMBER_INT);
            $comment = get_comment($comment_id);

            if (!$comment) {
                echo 'Invalid request.';
                exit;
            }

            $user_key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
            $real_key = $this->secret_key($comment_id);

            if (!$user_key || !hash_equals($real_key, $user_key)) {
                echo 'Invalid request.';
                exit;
            }

            $uri = get_permalink($comment->comment_post_ID);
            $this->persist_subscription_opt_out($comment_id);
            wp_safe_redirect($uri ?: home_url('/'));
            exit;
        }
    }
    
    public function add_admin_menu() {
        add_comments_page(
            '评论订阅管理',
            '评论订阅',
            'manage_options',
            'wpcomments_email_subscriptions',
            array($this, 'output_admin_subscriptions_page')
        );
    }
    
    public function output_admin_subscriptions_page() {
        if (!class_exists('WP_List_Table')) {
            require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
        }
        
        require_once WPCOMMENTS_PATH . 'includes/class-wpcomments-subscriptions-table.php';
        
        $subscriptions_table = new WPComments_Subscriptions_Table();
        $subscriptions_table->prepare_items();
        
        echo '<div class="wrap">';
        echo '<h1>评论订阅管理</h1>';
        echo '<form method="post">';
        $subscriptions_table->display();
        echo '</form>';
        echo '</div>';
    }
    
    public function output_admin_settings_page() {
        // 成功提示统一交由前端 JS 的 #wpcomments-status 控制，避免重复提示

        $settings = $this->get_all_settings();
        ?>
        <form method="post" action="" id="wpcomments-email-settings-form">
            <?php wp_nonce_field('wpcomments_email_settings', 'wpcomments_email_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">管理员通知</th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpcomments_email_admin_notification" value="1" <?php checked(1, $settings['admin_notification']); ?> />
                            启用管理员新评论通知
                        </label>
                        <p class="description">有新评论时发送邮件通知给管理员</p>
                        
                        <br><br>
                        <label for="wpcomments_email_admin_email">管理员邮箱：</label>
                        <input type="email" id="wpcomments_email_admin_email" name="wpcomments_email_admin_email" value="<?php echo esc_attr($settings['admin_email']); ?>" class="regular-text" />
                        <p class="description">留空使用网站管理员邮箱</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">默认订阅状态</th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpcomments_email_default_subscribe" value="1" <?php checked(1, $settings['default_subscribe']); ?> />
                            默认勾选订阅复选框
                        </label>
                        <p class="description">新评论表单中的订阅复选框是否默认勾选</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">GDPR 合规</th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpcomments_email_gdpr_enabled" value="1" <?php checked(1, $settings['gdpr_enabled']); ?> />
                            启用 GDPR 合规复选框
                        </label>
                        <p class="description">显示隐私政策同意复选框</p>
                        
                        <br><br>
                        <label for="wpcomments_email_privacy_url">隐私政策页面URL：</label>
                        <input type="url" id="wpcomments_email_privacy_url" name="wpcomments_email_privacy_url" value="<?php echo esc_attr($settings['privacy_url']); ?>" class="regular-text" />
                        <p class="description">隐私政策页面的完整URL</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">邮件发件人</th>
                    <td>
                        <label for="wpcomments_email_from_email">发件人邮箱：</label>
                        <input type="email" id="wpcomments_email_from_email" name="wpcomments_email_from_email" value="<?php echo esc_attr($settings['from_email']); ?>" class="regular-text" />
                        <p class="description">留空使用 WordPress 默认发件人邮箱</p>
                        
                        <br><br>
                        <label for="wpcomments_email_from_name">发件人姓名：</label>
                        <input type="text" id="wpcomments_email_from_name" name="wpcomments_email_from_name" value="<?php echo esc_attr($settings['from_name']); ?>" class="regular-text" />
                        <p class="description">留空使用网站名称</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">邮件主题</th>
                    <td>
                        <label>
                            <input type="radio" name="wpcomments_email_subject_type" value="default" <?php checked('default', $settings['subject_type']); ?> />
                            使用默认主题格式
                        </label>
                        <br>
                        <label>
                            <input type="radio" name="wpcomments_email_subject_type" value="custom" <?php checked('custom', $settings['subject_type']); ?> />
                            自定义主题
                        </label>
                        
                        <br><br>
                        <label for="wpcomments_email_custom_subject">自定义主题：</label>
                        <input type="text" id="wpcomments_email_custom_subject" name="wpcomments_email_custom_subject" value="<?php echo esc_attr($settings['custom_subject']); ?>" class="regular-text" />
                        <p class="description">可用变量：{site_name}, {post_title}, {comment_author}</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">订阅管理</th>
                    <td>
                        <a href="<?php echo esc_url(admin_url('edit-comments.php?page=wpcomments_email_subscriptions')); ?>" class="button">管理订阅列表</a>
                        <p class="description">查看和管理所有评论订阅</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('保存设置'); ?>
        </form>
        <?php
    }
    
    public function comment_notification($comment_id, $comment, $send_admin_notification = true) {
        if (!$comment instanceof \WP_Comment) {
            return;
        }

        $post = get_post($comment->comment_post_ID);
        if (!$post) {
            return;
        }

        if ($send_admin_notification && in_array($comment->comment_approved, array('0', '1'), true)) {
            $this->send_admin_notification_email($comment, $post);
        }

        if ($comment->comment_approved !== '1') {
            return;
        }

        if ($comment->comment_parent == 0) {
            return;
        }

        $parent_comment = get_comment($comment->comment_parent);
        if (!$parent_comment) {
            return;
        }

        $subscription_status = get_comment_meta($parent_comment->comment_ID, 'wpcomments_subscribe_to_comment', true);
        if ($subscription_status !== 'on') {
            return;
        }

        if ($parent_comment->comment_author_email === $comment->comment_author_email) {
            return;
        }

        $this->send_notification_email($parent_comment, $comment, $post);
    }
    
    public function comment_status_update($comment_id, $comment_status) {
        $comment = get_comment($comment_id);

        if ('approve' === $comment_status && $comment instanceof \WP_Comment) {
            // Admins were notified when the comment was created. Approval only
            // unlocks reply notifications and must not send a duplicate admin mail.
            $this->comment_notification($comment->comment_ID, $comment, false);
        }
    }
    
    public function comment_fields($fields) {
        $label = apply_filters('wpcomments_email_checkbox_label', '有人回复时邮件通知我');
        $checked = $this->get_default_checked() ? 'checked' : '';

        $subscribe_html = '<p class="comment-form-comment-subscribe"><label for="wpcomments_subscribe_to_comment"><input id="wpcomments_subscribe_to_comment" name="wpcomments_subscribe_to_comment" type="checkbox" value="on" ' . $checked . '>' . wp_kses_post($label) . '</label></p>';

        $fields['wpcomments_subscribe_to_comment'] = apply_filters('wpcomments_comment_subscribe_html', $subscribe_html, $label, $this->get_default_checked());

        if ($this->get_display_gdpr_notice()) {
            $fields['wpcomments_gdpr'] = $this->render_gdpr_notice();
        }

        return $fields;
    }
    
    public function comment_fields_logged_in($submit_field) {
        $checkbox = '';

        if (is_user_logged_in()) {
            $label = apply_filters('wpcomments_email_checkbox_label', '有人回复时邮件通知我');
            $checked = $this->get_default_checked() ? 'checked' : '';

            $subscribe_html = '<p class="comment-form-comment-subscribe"><label for="wpcomments_subscribe_to_comment"><input id="wpcomments_subscribe_to_comment" name="wpcomments_subscribe_to_comment" type="checkbox" value="on" ' . $checked . '>' . wp_kses_post($label) . '</label></p>';

            $checkbox = apply_filters('wpcomments_comment_subscribe_html', $subscribe_html, $label, $this->get_default_checked());

            if ($this->get_display_gdpr_notice()) {
                $checkbox .= $this->render_gdpr_notice();
            }
        }

        return $checkbox . $submit_field;
    }
    
    public function persist_subscription_opt_in($comment_id) {
        $value = isset($_POST['wpcomments_subscribe_to_comment'])
            && 'on' === sanitize_key(wp_unslash($_POST['wpcomments_subscribe_to_comment']))
            ? 'on'
            : 'off';
        return add_comment_meta($comment_id, 'wpcomments_subscribe_to_comment', $value, true);
    }
    
    public function persist_subscription_opt_out($comment_id) {
        return update_comment_meta($comment_id, 'wpcomments_subscribe_to_comment', 'off');
    }
    
    public function verify_comment_meta_data($comment) {
        if ($this->get_display_gdpr_notice() && !is_admin()) {
            if (!isset($_POST['wpcomments_gdpr'])) {
                wp_die('错误：您必须同意条款才能发送评论。请点击浏览器的返回按钮，如果您同意条款请重新提交您的评论。');
            }
        }
        return $comment;
    }
    
    private function send_notification_email($parent_comment, $reply_comment, $post) {
        $email = apply_filters('wpcomments_notification_email', $parent_comment->comment_author_email, $parent_comment, $reply_comment, $post);
        $subject = apply_filters('wpcomments_notification_subject', $this->get_email_subject($post), $parent_comment, $reply_comment, $post);
        $body = apply_filters('wpcomments_notification_body', $this->get_email_body($parent_comment, $reply_comment, $post), $parent_comment, $reply_comment, $post);
        
        add_filter('wp_mail_content_type', array($this, 'mail_content_type_filter'));
        
        $from_email = $this->get_setting('wpcomments_email_from_email', '');
        $from_name  = $this->get_setting('wpcomments_email_from_name', '');
        if (!empty($from_email)) {
            add_filter('wp_mail_from', array($this, 'mail_from_filter'));
        }
        if (!empty($from_name)) {
            add_filter('wp_mail_from_name', array($this, 'mail_from_name_filter'));
        }
        
        $mail_sent = wp_mail($email, $subject, $body);
        
        do_action('wpcomments_after_notification_sent', $mail_sent, $email, $subject, $body, $parent_comment, $reply_comment, $post);
        
        remove_filter('wp_mail_content_type', array($this, 'mail_content_type_filter'));
        if (!empty($from_email)) {
            remove_filter('wp_mail_from', array($this, 'mail_from_filter'));
        }
        if (!empty($from_name)) {
            remove_filter('wp_mail_from_name', array($this, 'mail_from_name_filter'));
        }
    }
    
    private function send_admin_notification_email($comment, $post) {
        if (!$this->get_setting('wpcomments_email_admin_notification', false)) {
            return;
        }
        
        $admin_email = $this->get_setting('wpcomments_email_admin_email', get_option('admin_email'));
        if (empty($admin_email)) {
            return;
        }
        
        $subject = apply_filters('wpcomments_admin_notification_subject', $this->get_admin_email_subject($post), $comment, $post);
        $body = apply_filters('wpcomments_admin_notification_body', $this->get_admin_email_body($comment, $post), $comment, $post);
        
        add_filter('wp_mail_content_type', array($this, 'mail_content_type_filter'));
        
        $from_email = $this->get_setting('wpcomments_email_from_email', '');
        $from_name  = $this->get_setting('wpcomments_email_from_name', '');
        if (!empty($from_email)) {
            add_filter('wp_mail_from', array($this, 'mail_from_filter'));
        }
        if (!empty($from_name)) {
            add_filter('wp_mail_from_name', array($this, 'mail_from_name_filter'));
        }
        
        $mail_sent = wp_mail($admin_email, $subject, $body);
        
        do_action('wpcomments_after_admin_notification_sent', $mail_sent, $admin_email, $subject, $body, $comment, $post);
        
        remove_filter('wp_mail_content_type', array($this, 'mail_content_type_filter'));
        if (!empty($from_email)) {
            remove_filter('wp_mail_from', array($this, 'mail_from_filter'));
        }
        if (!empty($from_name)) {
            remove_filter('wp_mail_from_name', array($this, 'mail_from_name_filter'));
        }
    }
    
    private function get_admin_email_body($comment, $post) {
        $template_path = $this->get_notification_template_path('new-comment');
        
        if (file_exists($template_path)) {
            ob_start();
            
            $subject = '新评论通知 - ' . get_bloginfo('name');
            $comment_author = $comment->comment_author;
            $comment_author_email = $comment->comment_author_email;
            $comment_author_url = $comment->comment_author_url;
            $comment_content = $comment->comment_content;
            $comment_date = $comment->comment_date;
            $post_title = $post->post_title;
            $post_url = get_permalink($post->ID);
            
            $moderation_links = $this->get_admin_moderation_links($comment);
            $approve_url = $moderation_links['approve_url'];
            $spam_url = $moderation_links['spam_url'];
            $delete_url = $moderation_links['delete_url'];
            $delete_label = $moderation_links['delete_label'];
            $settings_url = admin_url('options-general.php?page=wpcomments-settings&tab=email');
            
            include $template_path;
            return ob_get_clean();
        }
        
        $body = '<html><body>';
        $body .= '<h2>新评论通知</h2>';
        $body .= '<p>您的网站收到一条新评论：</p>';
        $body .= '<p><strong>文章：</strong>' . esc_html($post->post_title) . '</p>';
        $body .= '<p><strong>评论者：</strong>' . esc_html($comment->comment_author) . '</p>';
        $body .= '<p><strong>邮箱：</strong>' . esc_html($comment->comment_author_email) . '</p>';
        $body .= '<p><strong>内容：</strong></p>';
        $body .= '<blockquote>' . wpautop(esc_html($comment->comment_content)) . '</blockquote>';

        $moderation_links = $this->get_admin_moderation_links($comment);
        $actions = array();
        if (!empty($moderation_links['approve_url'])) {
            $actions[] = '<a href="' . esc_url($moderation_links['approve_url']) . '">批准评论</a>';
        }
        $actions[] = '<a href="' . esc_url($moderation_links['spam_url']) . '">标记为垃圾</a>';
        $actions[] = '<a href="' . esc_url($moderation_links['delete_url']) . '">' . esc_html($moderation_links['delete_label']) . '</a>';
        $body .= '<p>' . implode(' | ', $actions) . '</p>';
        $body .= '</body></html>';
        
        return apply_filters('wpcomments_default_admin_email_body', $body, $comment, $post);
    }

    private function get_admin_moderation_links($comment) {
        $comment_id = (int) $comment->comment_ID;
        $approve_url = '';

        if ('0' === $comment->comment_approved) {
            $approve_url = wp_nonce_url(
                admin_url('comment.php?action=approvecomment&c=' . $comment_id . '&noredir=1'),
                'approve-comment_' . $comment_id
            );
        }

        $spam_url = wp_nonce_url(
            admin_url('comment.php?action=spamcomment&c=' . $comment_id . '&noredir=1'),
            'delete-comment_' . $comment_id
        );

        $use_trash = defined('EMPTY_TRASH_DAYS') && EMPTY_TRASH_DAYS;
        $delete_action = $use_trash ? 'trashcomment' : 'deletecomment';
        $delete_label = $use_trash ? '移至回收站' : '永久删除评论';
        $delete_url = wp_nonce_url(
            admin_url('comment.php?action=' . $delete_action . '&c=' . $comment_id . '&noredir=1'),
            'delete-comment_' . $comment_id
        );

        return array(
            'approve_url' => $approve_url,
            'spam_url' => $spam_url,
            'delete_url' => $delete_url,
            'delete_label' => $delete_label,
        );
    }
    
    private function get_admin_email_subject($post) {
        $default_subject = '[' . get_bloginfo('name') . '] - 新评论通知';
        return apply_filters('wpcomments_default_admin_email_subject', $default_subject, $post);
    }
    
    private function get_email_subject($post) {
        $subject_type = $this->get_setting('wpcomments_email_subject_type', 'default');
        
        if ($subject_type === 'custom') {
            $custom_subject = $this->get_setting('wpcomments_email_custom_subject', '');
            if (!empty($custom_subject)) {
                return $custom_subject;
            }
        }
        
        $default_subject = '[' . get_bloginfo('name') . '] - 您的评论有新回复';
        return apply_filters('wpcomments_default_email_subject', $default_subject, $post);
    }
    
    private function get_email_body($parent_comment, $reply_comment, $post) {
        $template_path = $this->get_notification_template_path('comment-reply');
        
        if (file_exists($template_path)) {
            ob_start();
            
            $subject = '您的评论有新回复 - ' . get_bloginfo('name');
            $post_title = $post->post_title;
            $post_url = get_permalink($post->ID);
            $original_comment_content = $parent_comment->comment_content;
            $original_comment_date = $parent_comment->comment_date;
            $reply_author = $reply_comment->comment_author;
            $reply_content = $reply_comment->comment_content;
            $reply_date = $reply_comment->comment_date;
            $reply_author_url = $reply_comment->comment_author_url;
            $reply_id = $reply_comment->comment_ID;
            $reply_url = get_permalink($post->ID) . '#respond';
            $unsubscribe_url = $this->get_unsubscribe_link($parent_comment);
            
            include $template_path;
            return ob_get_clean();
        }
        
        $body = '<html><body>';
        $body .= '<h2>您的评论有新回复</h2>';
        $body .= '<p>您好 ' . $parent_comment->comment_author . '，</p>';
        $body .= '<p>您在文章《' . $post->post_title . '》中的评论有新回复：</p>';
        $body .= '<blockquote>' . wpautop($reply_comment->comment_content) . '</blockquote>';
        $body .= '<p>回复者：' . $reply_comment->comment_author . '</p>';
        $body .= '<p><a href="' . get_permalink($post->ID) . '#comment-' . $reply_comment->comment_ID . '">查看回复</a></p>';
        $body .= '<p><a href="' . $this->get_unsubscribe_link($parent_comment) . '">取消订阅</a></p>';
        $body .= '</body></html>';
        
        return apply_filters('wpcomments_default_email_body', $body, $parent_comment, $reply_comment, $post);
    }
    
    private function get_notification_template_path($template_type = 'comment-reply') {
        $template_name = 'email-' . $template_type . '.php';
        
        $custom_template = locate_template('templates/wpcomments/' . $template_name);
        
        if ($custom_template) {
            return $custom_template;
        }
        
        return WPCOMMENTS_PATH . 'templates/' . $template_name;
    }
    
    public function mail_content_type_filter($content_type) {
        return 'text/html';
    }
    
    public function mail_from_filter($email) {
        $from_email = $this->get_setting('wpcomments_email_from_email', '');
        return !empty($from_email) ? $from_email : $email;
    }
    
    public function mail_from_name_filter($name) {
        $from_name = $this->get_setting('wpcomments_email_from_name', '');
        return !empty($from_name) ? $from_name : $name;
    }
    
    public function get_unsubscribe_link($comment) {
        $key = $this->secret_key($comment->comment_ID);
        
        $params = array(
            'comment' => $comment->comment_ID,
            'key' => $key
        );
        
        $uri = site_url() . '/wpcomments/unsubscribe?' . http_build_query($params);
        return $uri;
    }
    
    private function secret_key($comment_id) {
        return hash_hmac('sha512', $comment_id, wp_salt(), false);
    }
    
    public function get_setting($option, $default = '') {
        return get_option($option, $default);
    }
    
    public function get_default_checked() {
        return (bool) $this->get_setting('wpcomments_email_default_subscribe', 0);
    }
    
    public function get_display_gdpr_notice() {
        return (bool) $this->get_setting('wpcomments_email_gdpr_enabled', 0);
    }
    
    public function get_privacy_policy_url() {
        return $this->get_setting('wpcomments_email_privacy_url', '');
    }
    
    public function render_gdpr_notice() {
        $label = apply_filters(
            'wpcomments_gdpr_checkbox_label',
            sprintf('我同意 %s 收集和存储我在此表单中提交的数据。', get_option('blogname'))
        );

        $privacy_policy_url = $this->get_privacy_policy_url();
        $privacy_policy = '<a target="_blank" rel="noopener noreferrer" href="' . esc_url($privacy_policy_url) . '">(隐私政策)</a>';

        $final_gdpr_html = '<p class="comment-form-comment-subscribe"><label for="wpcomments_gdpr"><input id="wpcomments_gdpr" name="wpcomments_gdpr" type="checkbox" value="yes" required="required">' . wp_kses_post($label) . ' ' . $privacy_policy . ' <span class="required">*</span></label></p>';

        return apply_filters(
            'wpcomments_gdpr_checkbox_html',
            $final_gdpr_html,
            $label,
            $privacy_policy_url
        );
    }
    
    private function save_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('权限不足', 'wpcomments'));
        }
        if (!isset($_POST['wpcomments_email_nonce']) || !wp_verify_nonce(wp_unslash($_POST['wpcomments_email_nonce']), 'wpcomments_email_settings')) {
            return;
        }
        
        $settings = array(
            'wpcomments_email_admin_notification' => isset($_POST['wpcomments_email_admin_notification']) ? 1 : 0,
            'wpcomments_email_admin_email' => isset($_POST['wpcomments_email_admin_email']) ? sanitize_email(wp_unslash($_POST['wpcomments_email_admin_email'])) : '',
            'wpcomments_email_default_subscribe' => isset($_POST['wpcomments_email_default_subscribe']) ? 1 : 0,
            'wpcomments_email_gdpr_enabled' => isset($_POST['wpcomments_email_gdpr_enabled']) ? 1 : 0,
            'wpcomments_email_privacy_url' => isset($_POST['wpcomments_email_privacy_url']) ? esc_url_raw(wp_unslash($_POST['wpcomments_email_privacy_url'])) : '',
            'wpcomments_email_from_email' => isset($_POST['wpcomments_email_from_email']) ? sanitize_email(wp_unslash($_POST['wpcomments_email_from_email'])) : '',
            'wpcomments_email_from_name' => isset($_POST['wpcomments_email_from_name']) ? sanitize_text_field(wp_unslash($_POST['wpcomments_email_from_name'])) : '',
            'wpcomments_email_subject_type' => isset($_POST['wpcomments_email_subject_type']) && 'custom' === wp_unslash($_POST['wpcomments_email_subject_type']) ? 'custom' : 'default',
            'wpcomments_email_custom_subject' => isset($_POST['wpcomments_email_custom_subject']) ? sanitize_text_field(wp_unslash($_POST['wpcomments_email_custom_subject'])) : ''
        );
        
        foreach ($settings as $key => $value) {
            update_option($key, $value);
        }
    }
    
    private function get_all_settings() {
        return array(
            'admin_notification' => get_option('wpcomments_email_admin_notification', 0),
            'admin_email' => get_option('wpcomments_email_admin_email', ''),
            'default_subscribe' => get_option('wpcomments_email_default_subscribe', 0),
            'gdpr_enabled' => get_option('wpcomments_email_gdpr_enabled', 0),
            'privacy_url' => get_option('wpcomments_email_privacy_url', ''),
            'from_email' => get_option('wpcomments_email_from_email', ''),
            'from_name' => get_option('wpcomments_email_from_name', ''),
            'subject_type' => get_option('wpcomments_email_subject_type', 'default'),
            'custom_subject' => get_option('wpcomments_email_custom_subject', '')
        );
    }
    
    public function admin_init() {
        // 处理在独立页面保存设置
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ('wpcomments-email-settings' === $page) {
            if (isset($_POST['wpcomments_email_nonce']) && wp_verify_nonce(wp_unslash($_POST['wpcomments_email_nonce']), 'wpcomments_email_settings')) {
                $this->save_settings();
                $redirect = wp_get_referer();
                if ($redirect) {
                    $redirect = add_query_arg('settings-updated', 'true', $redirect);
                } else {
                    $redirect = add_query_arg('settings-updated', 'true', admin_url('edit-comments.php?page=wpcomments-email-settings'));
                }
                wp_safe_redirect($redirect);
                exit;
            }
        }

        // 处理在主设置页面（通知标签页）中保存设置
        if ('wpcomments-settings' === $page) {
            $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';
            if ($tab === 'notifications' && isset($_POST['wpcomments_email_nonce']) && wp_verify_nonce(wp_unslash($_POST['wpcomments_email_nonce']), 'wpcomments_email_settings')) {
                $this->save_settings();
                wp_safe_redirect(add_query_arg(array(
                    'page' => 'wpcomments-settings',
                    'tab' => 'notifications',
                    'settings-updated' => 'true'
                ), admin_url('options-general.php')));
                exit;
            }
        }
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wpcomments') !== false) {
            // 样式与脚本
            wp_enqueue_style('wpcomments-admin', WPCOMMENTS_URL . 'assets/css/admin.css', array(), self::VERSION);
            wp_enqueue_script('wpcomments-admin', WPCOMMENTS_URL . 'assets/js/admin.js', array('jquery'), self::VERSION, true);

            // 本地化数据，确保统一的状态提示和环境信息
            wp_localize_script('wpcomments-admin', 'wpcomments', array(
                'nonce' => wp_create_nonce('wpcomments_admin_nonce'),
                'ajaxurl' => admin_url('admin-ajax.php'),
                'plugin_url' => WPCOMMENTS_URL,
                'assets_url' => WPCOMMENTS_URL . 'assets/',
                'is_network_admin' => is_network_admin() ? '1' : '0',
                'is_multisite' => is_multisite() ? '1' : '0',
            ));

            wp_localize_script('wpcomments-admin', 'wpcomments_l10n', array(
                'settings_saved' => __('设置已成功保存。', 'wpcomments')
            ));
        }
    }
    
    public function enqueue_frontend_scripts() {
        if (is_single() || is_page()) {
            wp_enqueue_style('wpcomments-frontend', plugin_dir_url(__FILE__) . '../assets/frontend.css', array(), self::VERSION);
            wp_enqueue_script('wpcomments-frontend', plugin_dir_url(__FILE__) . '../assets/frontend.js', array('jquery'), self::VERSION, true);
            
            wp_localize_script('wpcomments-frontend', 'wpcomments_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpcomments_ajax_nonce')
            ));
        }
     }
     
     public function ajax_unsubscribe() {
         check_ajax_referer('wpcomments_ajax_nonce', 'nonce');
         
         $comment_id = isset($_POST['comment_id']) ? absint($_POST['comment_id']) : 0;
         $key = isset($_POST['key']) ? sanitize_text_field(wp_unslash($_POST['key'])) : '';
         
         if (!$comment_id || !$key) {
             wp_die('Invalid request');
         }
         
         $comment = get_comment($comment_id);
         if (!$comment) {
             wp_die('Comment not found');
         }
         
         $real_key = $this->secret_key($comment_id);
         if (!hash_equals($real_key, $key)) {
             wp_die('Invalid key');
         }
         
         $this->persist_subscription_opt_out($comment_id);
         
         wp_send_json_success(array(
             'message' => '订阅已取消'
         ));
     }
 }
