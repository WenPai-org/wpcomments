<?php
/**
 * WPComments Core Functionality
 * 
 * 合并所有核心功能到一个文件中，简化插件结构
 *
 * @package WPComments
 * @since   2.0.0
 */

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WPComments 核心功能类
 * 
 * 包含所有评论相关的核心功能
 */
class WPComments_Core {
    
    /** @var self|null */
    private static $instance = null;

    /** @var bool */
    private $report_registered = false;
    /** @var bool */
    private $sticky_registered = false;

    /**
     * Track which comments already received a report link via reply area to avoid duplicate fallback in content.
     * @var array<int,bool>
     */
    private $report_links_output = array();

    /**
     * 构造函数
     */
    public function __construct() {
        self::$instance = $this;
        $this->init_hooks();
        $this->load_features();
    }
    
    /**
     * 初始化钩子
     */
    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * 加载功能模块
     */
    private function load_features() {
        $settings = \WPComments\get_feature_settings();
        
        // 模块初始化由模块管理器负责，核心不直接在此初始化
        
    }

    /**
     * 获取核心实例（供模块调用）
     */
    public static function get_instance(): ?self {
        return self::$instance;
    }

    /**
     * 加载文本域
     */
    public function load_textdomain() {
        load_plugin_textdomain('wpcomments', false, dirname(plugin_basename(WPCOMMENTS_PLUGIN_FILE)) . '/languages/');
    }
    
    /**
     * 加载前端资源
     */
    public function enqueue_frontend_assets() {
        // 现已由模块管理器集中调用各模块的 enqueue 方法
    }
    
    /**
     * 加载管理后台资源
     */
    public function enqueue_admin_assets($hook) {
        // 只在评论相关页面加载
        if (strpos($hook, 'comment') !== false || strpos($hook, 'wpcomments') !== false) {
            wp_enqueue_style('wpcomments-admin', WPCOMMENTS_URL . 'assets/css/admin.css', array(), WPCOMMENTS_VERSION);
            wp_enqueue_script('wpcomments-admin', WPCOMMENTS_URL . 'assets/js/admin.js', array('jquery'), WPCOMMENTS_VERSION, true);
        }
    }
    
    // ========== 角色徽章功能 ==========
    
    /** 对外提供的注册方法 */
    public function register_role_badge_module(): void {
        if ($this->role_badge_registered) { return; }
        $this->init_role_badge();
        $this->role_badge_registered = true;
    }
    
    /**
     * 初始化角色徽章功能
     */
    private function init_role_badge() {
        add_action('init', array($this, 'load_admin_textdomain_in_frontend'));
        add_filter('get_comment_author', array($this, 'get_comment_author_role'), 10, 3);
        add_filter('get_comment_author_link', array($this, 'comment_author_role'));
        
        if ($this->is_block_theme()) {
            add_action('wp_head', array($this, 'add_fse_theme_styles'));
            add_filter('render_block', array($this, 'modify_comment_blocks'), 10, 2);
        }
    }
    
    protected $user_role = '';

    // registration flags for modules
    private $role_badge_registered = false;
    private $delete_pending_registered = false;
    private $remove_website_field_registered = false;
    private $validation_registered = false;
    private $private_comments_registered = false;
    private $comment_limiter_registered = false;
    
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
        return $author . $this->user_role;
    }
    
    private function is_block_theme() {
        return function_exists('wp_is_block_theme') && wp_is_block_theme();
    }
    
    public function add_fse_theme_styles() {
        echo '<style>
            .wp-block-comment-author-name .comment-author-role-badge {
                display: inline-block;
                margin-left: 8px;
                font-size: 0.8em;
                padding: 2px 6px;
                border-radius: 3px;
                background: #0073aa;
                color: white;
            }
        </style>';
    }
    
    public function modify_comment_blocks($block_content, $block) {
        if ($block['blockName'] === 'core/comment-author-name') {
            return $block_content . $this->user_role;
        }
        return $block_content;
    }
    
    // ========== 删除待审核评论功能 ==========
    
    public function register_delete_pending_module(): void {
        if ($this->delete_pending_registered) { return; }
        $this->init_delete_pending();
        $this->delete_pending_registered = true;
    }
    
    private function init_delete_pending() {
        add_action('admin_menu', array($this, 'add_delete_pending_menu'));
        add_action('wp_ajax_wpcomments_delete_pending', array($this, 'ajax_delete_pending_comments'));
    }
    
    public function add_delete_pending_menu() {
        add_comments_page(
            __('删除待审核评论', 'wpcomments'),
            __('删除待审核评论', 'wpcomments'),
            'moderate_comments',
            'wpcomments-delete-pending',
            array($this, 'render_delete_pending_page')
        );
    }
    
    public function render_delete_pending_page() {
        $pending_count = wp_count_comments()->moderated;
        ?>
        <div class="wrap wpcomments-settings">
            <h1><?php _e('删除待审核评论', 'wpcomments'); ?></h1>
            
            <div class="wpcomments-card">
                <h2><?php _e('批量删除待审核评论', 'wpcomments'); ?></h2>
                <p class="wpcomments-section-desc"><?php printf(__('当前有 %d 条待审核评论。', 'wpcomments'), $pending_count); ?></p>
                
                <?php if ($pending_count > 0): ?>
                <div class="wpcomments-action-buttons">
                    <button type="button" id="delete-all-pending" class="button button-primary">
                        <?php _e('删除所有待审核评论', 'wpcomments'); ?>
                    </button>
                </div>
                
                <script>
                jQuery(document).ready(function($) {
                    $('#delete-all-pending').on('click', function() {
                        if (!confirm('<?php _e('确定要删除所有待审核评论吗？此操作不可撤销。', 'wpcomments'); ?>')) {
                            return;
                        }
                        
                        var $button = $(this);
                        $button.prop('disabled', true).text('<?php _e('删除中...', 'wpcomments'); ?>');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'wpcomments_delete_pending',
                                nonce: '<?php echo wp_create_nonce('wpcomments_delete_pending'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    alert('<?php _e('删除成功！', 'wpcomments'); ?>');
                                    location.reload();
                                } else {
                                    alert('<?php _e('删除失败：', 'wpcomments'); ?>' + response.data);
                                }
                            },
                            error: function() {
                                alert('<?php _e('请求失败，请重试。', 'wpcomments'); ?>');
                            },
                            complete: function() {
                                $button.prop('disabled', false).text('<?php _e('删除所有待审核评论', 'wpcomments'); ?>');
                            }
                        });
                    });
                });
                </script>
                <?php else: ?>
                <p><?php _e('没有待审核的评论。', 'wpcomments'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    public function ajax_delete_pending_comments() {
        check_ajax_referer('wpcomments_delete_pending', 'nonce');
        
        if (!current_user_can('moderate_comments')) {
            wp_send_json_error(__('权限不足', 'wpcomments'));
        }
        
        $total_deleted = 0;
        while (true) {
            $pending_ids = get_comments(array(
                'status' => 'hold',
                'number' => 200,
                'fields' => 'ids',
            ));
            if (empty($pending_ids)) {
                break;
            }
            foreach ($pending_ids as $cid) {
                if (wp_delete_comment($cid, true)) {
                    $total_deleted++;
                }
            }
        }
        
        wp_send_json_success(sprintf(__('成功删除 %d 条待审核评论', 'wpcomments'), $total_deleted));
    }
    

    

    
    // ========== 移除网站链接字段功能 ==========
    
    public function register_remove_website_field_module(): void {
        if ($this->remove_website_field_registered) { return; }
        $this->init_remove_website_field();
        $this->remove_website_field_registered = true;
    }
    
    private function init_remove_website_field() {
        add_filter('comment_form_default_fields', array($this, 'remove_website_field'));
    }
    
    public function remove_website_field($fields) {
        unset($fields['url']);
        return $fields;
    }
    
    // ========== 评论表单验证功能 ==========
    
    public function register_validation_module(): void {
        if ($this->validation_registered) { return; }
        $this->init_validation();
        $this->validation_registered = true;
    }
    
    private function init_validation() {
        // enqueue moved to module manager via Validation_Module
        add_action('pre_comment_on_post', array($this, 'validate_comment_submission'));
    }
    
    public function enqueue_validation_scripts() {
        if (is_singular() && comments_open()) {
            wp_enqueue_script('wpcomments-validation', WPCOMMENTS_URL . 'assets/js/validation.js', array('jquery'), WPCOMMENTS_VERSION, true);
            
            wp_localize_script('wpcomments-validation', 'wpcomments_validation', array(
                'required_name' => __('请输入您的姓名', 'wpcomments'),
                'required_email' => __('请输入您的邮箱地址', 'wpcomments'),
                'invalid_email' => __('请输入有效的邮箱地址', 'wpcomments'),
                'required_comment' => __('请输入评论内容', 'wpcomments'),
            ));
        }
    }
    
    public function validate_comment_submission($post_id) {
        if (empty($_POST['comment'])) {
            wp_die(__('评论内容不能为空', 'wpcomments'));
        }
        
        if (empty($_POST['author']) && get_option('require_name_email')) {
            wp_die(__('请输入您的姓名', 'wpcomments'));
        }
        
        if (empty($_POST['email']) && get_option('require_name_email')) {
            wp_die(__('请输入您的邮箱地址', 'wpcomments'));
        }
        
        if (!empty($_POST['email']) && !is_email($_POST['email'])) {
            wp_die(__('请输入有效的邮箱地址', 'wpcomments'));
        }
    }
    
    // ========== 私密评论功能 ==========
    
    public function register_private_comments_module(): void {
        if ($this->private_comments_registered) { return; }
        $this->init_private_comments();
        $this->private_comments_registered = true;
    }
    
    private function init_private_comments() {
        add_filter('comment_form_field_comment', array($this, 'add_private_comment_field'));
        add_action('comment_post', array($this, 'save_private_comment_meta'));
        add_filter('wp_update_comment_data', array($this, 'update_private_comment_data'));
        add_filter('get_comment', array($this, 'filter_private_comment'));
        add_filter('comment_text', array($this, 'add_private_comment_notice'), 10, 2);
        add_filter('the_comments', array($this, 'filter_private_comments_list'));
        add_action('wp_set_comment_status', array($this, 'prevent_private_comment_approval'), 10, 2);
        add_filter('comment_row_actions', array($this, 'modify_private_comment_actions'), 10, 2);
        add_filter('edit_comment_misc_actions', array($this, 'add_private_comment_edit_notice'), 10, 2);
    }
    
    public function add_private_comment_field($field) {
        $options = $this->get_private_comment_options();
        $checked = $options['default_checked'] ? ' checked="checked"' : '';
        $text = $options['label_text'] ?: __('Keep this comment private (visible to site owners only)', 'wpcomments');
        
        $field .= '<p class="comment-form-private-comment"><input id="wp-comment-private" name="wp-comment-private" type="checkbox" value="1"' . $checked . ' /> <label for="wp-comment-private">' . esc_html($text) . '</label></p>';
        
        return $field;
    }
    
    public function save_private_comment_meta($comment_id) {
        if (isset($_POST['wp-comment-private']) && $_POST['wp-comment-private'] == '1') {
            // 设置为私密评论并标记为待审核
            $commentarr = array('comment_ID' => $comment_id, 'comment_approved' => 0);
            wp_update_comment($commentarr);
            update_comment_meta($comment_id, 'wpcomments_private', 1);
        }
    }
    
    public function is_private_comment($comment_id) {
        return get_comment_meta($comment_id, 'wpcomments_private', true) == 1;
    }
    
    public function update_private_comment_data($data) {
        if ($this->is_private_comment($data['comment_ID'])) {
            $data['comment_approved'] = 0; // 私密评论始终保持待审核状态
        }
        return $data;
    }
    
    public function filter_private_comment($comment) {
        if (!is_admin() && is_user_logged_in() && current_user_can('moderate_comments')) {
            if ($this->is_private_comment($comment->comment_ID)) {
                $comment->comment_approved = 1; // 对管理员显示私密评论
            }
        }
        return $comment;
    }
    
    public function add_private_comment_notice($content, $comment) {
        if (!is_admin() && is_user_logged_in() && current_user_can('moderate_comments')) {
            if ($this->is_private_comment($comment->comment_ID)) {
                $notice = '<p><em class="wpcomments-private-notice">' . __('Private comment (visible to site owners only)', 'wpcomments') . '</em></p>';
                $content = $notice . '<div class="wpcomments-private-content">' . $content . '</div>';
            }
        }
        return $content;
    }
    
    public function filter_private_comments_list($comments) {
        if (!is_admin() && is_user_logged_in() && current_user_can('moderate_comments')) {
            foreach ($comments as $index => $comment) {
                if ($this->is_private_comment($comment->comment_ID)) {
                    $comments[$index]->comment_approved = 1;
                }
            }
        }
        return $comments;
    }
    
    public function prevent_private_comment_approval($comment_id, $comment_status) {
        if (($comment_status === 'approve' || $comment_status === '1') && $this->is_private_comment($comment_id)) {
            wp_set_comment_status($comment_id, 0);
            if (wp_doing_ajax()) {
                wp_send_json_error(__('Private comments cannot be made public', 'wpcomments'));
            }
        }
    }
    
    public function modify_private_comment_actions($actions, $comment) {
        if ($this->is_private_comment($comment->comment_ID)) {
            if (isset($actions['approve'])) {
                $actions['approve'] = '<span class="wpcomments-disabled-action" title="' . esc_attr__('Private comments cannot be made public', 'wpcomments') . '">' . __('Approve') . '</span>';
            }
            if (isset($actions['reply'])) {
                $actions['reply'] = '<span class="wpcomments-disabled-action" title="' . esc_attr__('Private comments cannot receive replies', 'wpcomments') . '">' . __('Reply') . '</span>';
            }
        }
        return $actions;
    }
    
    public function add_private_comment_edit_notice($html, $comment) {
        if ($this->is_private_comment($comment->comment_ID)) {
            $html .= '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    var approveInput = document.querySelector(\'input[name="comment_status"][value="1"]\');
                    if (approveInput) {
                        approveInput.disabled = true;
                        var label = approveInput.closest("label");
                        if (label) {
                            label.title = "' . esc_js(__('Private comments cannot be made public', 'wpcomments')) . '";
                            label.style.opacity = "0.5";
                        }
                    }
                });
            </script>';
        }
        return $html;
    }
    
    private function get_private_comment_options() {
        $defaults = array(
            'default_checked' => false,
            'label_text' => __('Keep this comment private (visible to site owners only)', 'wpcomments')
        );
        
        $options = get_option('wpcomments_private_comment_options', $defaults);
        return wp_parse_args($options, $defaults);
    }
    
    // ========== 评论字符限制功能 ==========
    
    public function register_comment_limiter_module(): void {
        if ($this->comment_limiter_registered) { return; }
        $this->init_comment_limiter();
        $this->comment_limiter_registered = true;
    }
    
    private function init_comment_limiter() {
        // enqueue moved to module manager via Comment_Limiter_Module
        add_filter('preprocess_comment', array($this, 'validate_comment_length'));
        add_action('comment_form_after_fields', array($this, 'add_comment_counter'));
    }
    
    public function enqueue_limiter_scripts() {
        if (is_singular() && comments_open()) {
            wp_enqueue_script('wpcomments-limiter', WPCOMMENTS_URL . 'assets/js/comment-limiter.js', array('jquery'), WPCOMMENTS_VERSION, true);
            
            $options = $this->get_limiter_options();
            wp_localize_script('wpcomments-limiter', 'wpcommentsLimiter', array(
                'minChars' => $options['minimum_characters'],
                'maxChars' => $options['maximum_characters'],
                'minMessage' => $options['minimum_message'],
                'maxMessage' => $options['maximum_message'],
                'isAdmin' => current_user_can('manage_options') ? 'yes' : 'no',
                'enableAdminFeature' => $options['enable_admin_feature']
            ));

            // 前端样式（仅限字符限制器相关）
            wp_add_inline_style('wp-block-library', '\n                #wpcomments-counter-container { margin-top: 10px; }\n                #wpcomments-progress-bar { height: 5px; background-color: #ddd; border-radius: 3px; overflow: hidden; }\n                #wpcomments-progress-fill { height: 100%; width: 0%; background-color: #4caf50; transition: width 0.3s ease; }\n                #wpcomments-char-counter { margin-top: 5px; font-size: 14px; color: #666; }\n                .wpcomments-disabled { opacity: 0.6; cursor: not-allowed; }\n            ');
        }
    }
    
    public function validate_comment_length($commentdata) {
        $options = $this->get_limiter_options();
        
        // 管理员豁免检查
        if (current_user_can('manage_options') && $options['enable_admin_feature'] === 'no') {
            return $commentdata;
        }
        
        $content_length = strlen($commentdata['comment_content']);
        
        if ($content_length > $options['maximum_characters']) {
            wp_die(
                esc_html($options['maximum_message']),
                __('Comment Length Error', 'wpcomments'),
                array('back_link' => true)
            );
        }
        
        if ($content_length < $options['minimum_characters']) {
            wp_die(
                esc_html($options['minimum_message']),
                __('Comment Length Error', 'wpcomments'),
                array('back_link' => true)
            );
        }
        
        return $commentdata;
    }
    
    public function add_comment_counter() {
        echo '<div id="wpcomments-counter-container" style="margin-top: 10px;">
            <div id="wpcomments-progress-bar" style="height: 5px; background-color: #ddd; border-radius: 3px; overflow: hidden;">
                <div id="wpcomments-progress-fill" style="height: 100%; width: 0%; background-color: #4caf50; transition: width 0.3s ease;"></div>
            </div>
            <div id="wpcomments-char-counter" style="margin-top: 5px; font-size: 14px; color: #666;">Characters: 0</div>
        </div>';
    }
    
    private function get_limiter_options() {
        $defaults = array(
            'maximum_characters' => 1000,
            'maximum_message' => __('Your comment exceeds the maximum limit allowed. Please try to reduce it.', 'wpcomments'),
            'minimum_characters' => 20,
            'minimum_message' => __('Your comment is below the minimum allowed limit. Please try to be more expressive.', 'wpcomments'),
            'enable_admin_feature' => 'no'
        );
        
        $options = get_option('wpcomments_comment_limiter_options', $defaults);
        return wp_parse_args($options, $defaults);
    }
    
    // ========== 评论举报功能 ==========
    
    /** 对外提供的注册方法（由模块管理器调用） */
    public function register_report_module(): void {
        if ($this->report_registered) { return; }
        $this->init_report_comments();
        $this->report_registered = true;
    }

    private function init_report_comments() {
        // Enqueue moved to module's enqueue methods via Module Manager
        add_action('wp_ajax_wpcomments_report_comment', array($this, 'handle_comment_report'));
        add_action('wp_ajax_nopriv_wpcomments_report_comment', array($this, 'handle_comment_report'));
        add_filter('comment_reply_link', array($this, 'add_report_link_to_reply'), 10, 3);
        add_filter('comment_text', array($this, 'add_report_link_to_content'), 999, 2);
    }
    
    public function enqueue_report_scripts() {
        if (is_singular() && comments_open()) {
            wp_enqueue_script('wpcomments-report', WPCOMMENTS_URL . 'assets/js/comment-report.js', array('jquery'), WPCOMMENTS_VERSION, true);
            
            wp_localize_script('wpcomments-report', 'wpcommentsReport', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpcomments_report_nonce'),
                'reportText' => __('Report comment', 'wpcomments'),
                'thankYouMessage' => __('Thank you for your report. We will review this comment.', 'wpcomments'),
                'alreadyReportedMessage' => __('You have already reported this comment.', 'wpcomments'),
                'errorMessage' => __('An error occurred. Please try again.', 'wpcomments')
            ));
        }
    }
    
    public function handle_comment_report() {
        check_ajax_referer('wpcomments_report_nonce', 'nonce');
        
        $comment_id = intval($_POST['comment_id']);
        if (!$comment_id) {
            wp_send_json_error(__('Invalid comment ID', 'wpcomments'));
        }
        
        // 登录要求
        $login_only = (bool) (class_exists('WPComments\\WPComments_Network_Settings') ? \WPComments\WPComments_Network_Settings::get_effective_setting('wpcomments_report_logged_in_only', 0) : get_option('wpcomments_report_logged_in_only', 0));
        if ($login_only && !is_user_logged_in()) {
            wp_send_json_error(__('Only logged-in users can report comments.', 'wpcomments'));
        }
        
        // 检查是否已经举报过
        if ($this->user_already_reported($comment_id)) {
            wp_send_json_error(__('You have already reported this comment.', 'wpcomments'));
        }
        
        // 记录举报
        $this->record_comment_report($comment_id);
        
        // 检查是否达到举报阈值
        $threshold = class_exists('WPComments\\WPComments_Network_Settings') 
            ? (int) WPComments_Network_Settings::get_effective_setting('wpcomments_report_threshold', get_option('wpcomments_report_threshold', 3))
            : (int) get_option('wpcomments_report_threshold', 3);
        $report_count = get_comment_meta($comment_id, 'wpcomments_report_count', true) ?: 0;
        
        if ($report_count >= $threshold) {
            wp_set_comment_status($comment_id, 'hold');
            do_action('wpcomments_comment_flagged', $comment_id);
        }
        
        wp_send_json_success(__('Thank you for your report. We will review this comment.', 'wpcomments'));
    }
    
    public function add_report_link_to_reply($comment_reply_link, $args, $comment) {
        if (!get_option('thread_comments')) {
            return $comment_reply_link;
        }
        
        $report_link = $this->get_report_link($comment->comment_ID);
        if ($report_link) {
            // 标记该评论已在回复区域输出举报链接
            $this->report_links_output[(int) $comment->comment_ID] = true;
            $comment_reply_link .= ' <span class="wpcomments-report-link">' . $report_link . '</span>';
        }
        
        return $comment_reply_link;
    }
    
    public function add_report_link_to_content($comment_content, $comment) {
        if (is_admin()) {
            return $comment_content;
        }

        // 如果已在“回复”区域输出，则不再追加到内容区域，避免重复
        if (!empty($this->report_links_output[(int) $comment->comment_ID])) {
            return $comment_content;
        }
        
        $report_link = $this->get_report_link($comment->comment_ID);
        if ($report_link) {
            $comment_content .= '<br><span class="wpcomments-report-link">' . $report_link . '</span>';
        }
        
        return $comment_content;
    }
    
    private function get_report_link($comment_id) {
        // 不显示给评论作者自己
        if (is_user_logged_in()) {
            $comment = get_comment($comment_id);
            if (get_current_user_id() == $comment->user_id) {
                return '';
            }
        }
        
        // 检查是否已经举报过
        if ($this->user_already_reported($comment_id)) {
            return '<span class="wpcomments-already-reported">' . __('Reported', 'wpcomments') . '</span>';
        }
        
        // 登录限制：如果配置了仅登录用户可举报且当前未登录，不输出举报链接
        $login_only = (bool) (class_exists('WPComments\WPComments_Network_Settings') ? WPComments_Network_Settings::get_effective_setting('wpcomments_report_logged_in_only', 0) : get_option('wpcomments_report_logged_in_only', 0));
        if ($login_only && !is_user_logged_in()) {
            return '';
        }
        
        // 隐私提示：允许后台自定义；支持 {days} 占位符
        $retention_days = (int) (class_exists('WPComments\WPComments_Network_Settings') ? WPComments_Network_Settings::get_effective_setting('wpcomments_report_retention_days', apply_filters('wpcomments_report_retention_days', 90)) : get_option('wpcomments_report_retention_days', apply_filters('wpcomments_report_retention_days', 90)));
        $notice_tpl = class_exists('WPComments\WPComments_Network_Settings') ? WPComments_Network_Settings::get_effective_setting('wpcomments_report_privacy_notice', __('举报将记录必要信息用于防刷与审核，数据保留 {days} 天。', 'wpcomments')) : get_option('wpcomments_report_privacy_notice', __('举报将记录必要信息用于防刷与审核，数据保留 {days} 天。', 'wpcomments'));
        $privacy_notice = str_replace('{days}', (string) $retention_days, $notice_tpl);
        $privacy_notice_attr = esc_attr($privacy_notice);
        
        return '<a href="#" class="wpcomments-report-btn" data-comment-id="' . esc_attr($comment_id) . '" rel="nofollow" title="' . $privacy_notice_attr . '" aria-label="' . $privacy_notice_attr . '">' . __('Report', 'wpcomments') . '</a>';
    }
    
    private function user_already_reported($comment_id) {
        $raw_ip = $this->get_user_ip();
        $hashed_ip = hash_hmac('sha1', $raw_ip, wp_salt());
        $user_id = get_current_user_id();
        
        // 检查 cookie
        if (isset($_COOKIE['wpcomments_reports'])) {
            $reported_comments = json_decode(stripslashes($_COOKIE['wpcomments_reports']), true);
            if (is_array($reported_comments) && in_array($comment_id, $reported_comments)) {
                return true;
            }
        }
        
        // 频控：同一主体短时间内仅允许一次
        $lock_key = $this->get_report_lock_key($comment_id, $user_id, $hashed_ip);
        if (get_transient($lock_key)) {
            return true;
        }
        
        // 检查数据库记录（基于哈希 IP 和用户 ID）
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpcomments_reports';
        
        if ($user_id) {
            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table_name} WHERE comment_id = %d AND (user_ip = %s OR user_id = %d)",
                    $comment_id,
                    $hashed_ip,
                    $user_id
                )
            );
        } else {
            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table_name} WHERE comment_id = %d AND user_ip = %s",
                    $comment_id,
                    $hashed_ip
                )
            );
        }
        
        return !empty($existing);
    }
    
    private function record_comment_report($comment_id) {
        global $wpdb;
        
        $raw_ip = $this->get_user_ip();
        $hashed_ip = hash_hmac('sha1', $raw_ip, wp_salt());
        $user_id = get_current_user_id();
        
        // 创建举报记录表（如果不存在）
        $this->create_reports_table();
        
        // 插入举报记录（使用哈希 IP）
        $table_name = $wpdb->prefix . 'wpcomments_reports';
        $wpdb->insert(
            $table_name,
            array(
                'comment_id' => $comment_id,
                'user_id' => $user_id ?: null,
                'user_ip' => $hashed_ip,
                'report_time' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s')
        );
        
        // 更新评论的举报计数
        $current_count = get_comment_meta($comment_id, 'wpcomments_report_count', true) ?: 0;
        update_comment_meta($comment_id, 'wpcomments_report_count', $current_count + 1);
        
        // 设置 cookie
        $reported_comments = array();
        if (isset($_COOKIE['wpcomments_reports'])) {
            $reported_comments = json_decode(stripslashes($_COOKIE['wpcomments_reports']), true) ?: array();
        }
        $reported_comments[] = $comment_id;
        setcookie('wpcomments_reports', json_encode($reported_comments), time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
        
        // 设置频控，防止短时间内重复举报（可配置，默认10分钟）
        $lock_key = $this->get_report_lock_key($comment_id, $user_id, $hashed_ip);
        $min_interval = max(1, (int) (class_exists('WPComments\WPComments_Network_Settings') ? WPComments_Network_Settings::get_effective_setting('wpcomments_report_min_interval', 10) : get_option('wpcomments_report_min_interval', 10)));
        set_transient($lock_key, 1, $min_interval * MINUTE_IN_SECONDS);
    }
    private function get_report_lock_key($comment_id, $user_id, $hashed_ip) {
        if ($user_id) {
            return 'wpcomments_report_lock_uid_' . (int) $user_id . '_' . (int) $comment_id;
        }
        return 'wpcomments_report_lock_ip_' . $hashed_ip . '_' . (int) $comment_id;
    }
    
    private function create_reports_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpcomments_reports';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comment_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NULL,
            user_ip varchar(45) NOT NULL,
            report_time datetime NOT NULL,
            PRIMARY KEY (id),
            KEY comment_id (comment_id),
            KEY user_id (user_id),
            KEY user_ip (user_ip)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function get_user_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    // ========== 置顶评论功能 ==========
    
    /** 对外提供的注册方法（由模块管理器调用） */
    public function register_sticky_module(): void {
        if ($this->sticky_registered) { return; }
        $this->init_sticky_comments();
        $this->sticky_registered = true;
    }

    private function init_sticky_comments() {
        // 管理端功能
        add_filter('comment_row_actions', array($this, 'add_sticky_comment_actions'), 10, 2);
        add_filter('edit_comment_misc_actions', array($this, 'add_sticky_comment_edit_section'), 10, 2);
        add_action('admin_post_pin_comment', array($this, 'handle_pin_comment'));
        add_action('admin_post_unpin_comment', array($this, 'handle_unpin_comment'));
        // Enqueue moved to module's enqueue methods via Module Manager
        
        // 前端功能
        add_filter('comments_clauses', array($this, 'modify_comments_query_for_sticky'), 10, 2);
        add_filter('the_comments', array($this, 'adjust_comments_order_for_sticky'));
        // Enqueue moved to module's enqueue methods via Module Manager
        add_filter('comment_class', array($this, 'add_sticky_comment_class'), 10, 4);
    }
    
    public function add_sticky_comment_actions($actions, $comment) {
        // 只对顶级评论显示置顶选项
        if ($comment->comment_parent != 0) {
            return $actions;
        }
        
        $is_pinned = get_comment_meta($comment->comment_ID, 'wpcomments_sticky', true);
        
        if ($is_pinned) {
            $actions['unpin_comment'] = $this->get_unpin_button($comment->comment_ID);
        } else {
            $actions['pin_comment'] = $this->get_pin_button($comment->comment_ID);
        }
        
        return $actions;
    }
    
    public function add_sticky_comment_edit_section($html, $comment) {
        // 只对顶级评论显示置顶选项
        if ($comment->comment_parent != 0) {
            return $html;
        }
        
        $is_pinned = get_comment_meta($comment->comment_ID, 'wpcomments_sticky', true);
        
        $html .= '<div class="misc-pub-section misc-pub-sticky-comment">';
        $html .= '<span class="dashicons dashicons-sticky"></span>';
        $html .= '<span> ' . esc_html__('置顶评论:', 'wpcomments') . ' </span>';
        
        if ($is_pinned) {
            $html .= $this->get_unpin_button($comment->comment_ID);
        } else {
            $html .= $this->get_pin_button($comment->comment_ID);
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function get_pin_button($comment_id) {
        $url = add_query_arg(
            array(
                'cid' => $comment_id,
                'action' => 'pin_comment',
            ),
            admin_url('admin-post.php')
        );
        $url = wp_nonce_url($url, 'wpcomments_sticky_comment');
        
        return '<a class="wpcomments-sticky-pin" href="' . esc_url($url) . '">' . __('置顶', 'wpcomments') . '</a>';
    }
    
    private function get_unpin_button($comment_id) {
        $url = add_query_arg(
            array(
                'cid' => $comment_id,
                'action' => 'unpin_comment',
            ),
            admin_url('admin-post.php')
        );
        $url = wp_nonce_url($url, 'wpcomments_sticky_comment');
        
        return '<a class="wpcomments-sticky-unpin" href="' . esc_url($url) . '">' . __('取消置顶', 'wpcomments') . '</a>';
    }
    
    public function handle_pin_comment() {
        $this->verify_sticky_nonce();
        
        $comment_id = isset($_GET['cid']) ? intval($_GET['cid']) : 0;
        if (!$comment_id) {
            wp_die(__('无效的评论ID', 'wpcomments'));
        }
        
        $result = update_comment_meta($comment_id, 'wpcomments_sticky', 1);
        
        if ($result) {
            $status = array('message' => __('评论已置顶', 'wpcomments'));
        } else {
            $status = array('error' => __('置顶失败', 'wpcomments'));
        }
        
        $this->redirect_after_sticky_action($status);
    }
    
    public function handle_unpin_comment() {
        $this->verify_sticky_nonce();
        
        $comment_id = isset($_GET['cid']) ? intval($_GET['cid']) : 0;
        if (!$comment_id) {
            wp_die(__('无效的评论ID', 'wpcomments'));
        }
        
        $result = delete_comment_meta($comment_id, 'wpcomments_sticky');
        
        if ($result) {
            $status = array('message' => __('已取消置顶', 'wpcomments'));
        } else {
            $status = array('error' => __('取消置顶失败', 'wpcomments'));
        }
        
        $this->redirect_after_sticky_action($status);
    }
    
    private function verify_sticky_nonce() {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'wpcomments_sticky_comment')) {
            wp_die(__('安全验证失败，请重试', 'wpcomments'));
        }
        
        if (!current_user_can('moderate_comments')) {
            wp_die(__('权限不足', 'wpcomments'));
        }
    }
    
    private function redirect_after_sticky_action($status) {
        $redirect_url = wp_get_referer() ?: admin_url('edit-comments.php');
        $redirect_url = add_query_arg($status, $redirect_url);
        wp_redirect($redirect_url);
        exit;
    }
    
    public function modify_comments_query_for_sticky($clauses, $query) {
        if (is_admin()) {
            return $clauses;
        }
        
        global $wpdb;
        
        // 添加 JOIN 来获取置顶状态
        $clauses['join'] .= " LEFT JOIN {$wpdb->commentmeta} AS sticky_meta 
            ON {$wpdb->comments}.comment_ID = sticky_meta.comment_id 
            AND sticky_meta.meta_key = 'wpcomments_sticky'";
        
        // 修改排序：置顶评论在前，然后按原有顺序
        $original_order = get_option('comment_order', 'desc');
        $clauses['orderby'] = "CASE WHEN sticky_meta.meta_value = '1' THEN 0 ELSE 1 END, {$wpdb->comments}.comment_date_gmt {$original_order}";
        
        return $clauses;
    }
    
    public function adjust_comments_order_for_sticky($comments) {
        if (is_admin()) {
            return $comments;
        }
        
        // WordPress 在 desc 模式下会反转评论数组
        // 由于我们已经在查询中指定了正确的顺序，需要避免被反转
        if ('desc' === get_option('comment_order')) {
            // 检查是否有置顶评论
            $has_sticky = false;
            foreach ($comments as $comment) {
                if (get_comment_meta($comment->comment_ID, 'wpcomments_sticky', true)) {
                    $has_sticky = true;
                    break;
                }
            }
            
            // 如果有置顶评论，保持我们的自定义顺序
            if ($has_sticky) {
                return $comments;
            }
        }
        
        return $comments;
    }
    
    public function add_sticky_comment_class($classes, $class, $comment_id, $comment) {
        if (get_comment_meta($comment_id, 'wpcomments_sticky', true)) {
            $classes[] = 'wpcomments-sticky-comment';
        }
        return $classes;
    }
    
    public function enqueue_sticky_admin_assets($hook) {
        if (strpos($hook, 'comment') !== false) {
            wp_add_inline_style('wp-admin', '
                .misc-pub-sticky-comment {
                    border-top: 1px solid #ddd;
                    padding: 10px 0;
                }
                .misc-pub-sticky-comment .dashicons {
                    color: #82878c;
                    margin-right: 5px;
                }
                .wpcomments-sticky-pin,
                .wpcomments-sticky-unpin {
                    color: #0073aa;
                    text-decoration: none;
                }
                .wpcomments-sticky-pin:hover,
                .wpcomments-sticky-unpin:hover {
                    color: #005177;
                    text-decoration: underline;
                }
            ');
        }
    }
    
    public function enqueue_sticky_frontend_assets() {
        if (is_singular() && comments_open()) {
            wp_add_inline_style('wp-block-library', '
                .wpcomments-sticky-comment {
                    position: relative;
                    border-left: 4px solid #0073aa;
                    background-color: #f8f9fa;
                    padding-left: 15px;
                }
                .wpcomments-sticky-comment::before {
                    content: "📌 ' . esc_html__('置顶评论', 'wpcomments') . '";
                    display: block;
                    font-size: 12px;
                    color: #0073aa;
                    font-weight: bold;
                    margin-bottom: 8px;
                }
                .wpcomments-sticky-comment .comment-content {
                    background: transparent;
                }
            ');
        }
    }
    

}

