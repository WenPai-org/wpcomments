<?php
/**
 * Frequently replies functionality.
 *
 * @package WPComments
 * @since   1.0.0
 */

namespace WPComments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPComments_Frequently_Replies
 *
 * Handles frequently used replies functionality.
 */
class WPComments_Frequently_Replies {
    
    private $option_name = 'wpcomments_frequently_replies';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_save_wpcomments_frequently_replies', array($this, 'save_replies_ajax'));
        add_action('wp_ajax_wpcomments_auto_save_replies', array($this, 'auto_save_replies_ajax'));
        add_action('wp_ajax_closed-postboxes', array($this, 'handle_postbox_ajax'));
        add_action('wp_ajax_meta-box-order', array($this, 'handle_postbox_ajax'));
    }
    
    public function add_admin_menu() {
        $hook_suffix = add_submenu_page(
            'edit-comments.php',
            __('常用回复', 'wpcomments'),
            __('常用回复', 'wpcomments'),
            'moderate_comments',
            'wpcomments-frequently-replies',
            array($this, 'render_options_page')
        );
        
        add_action('admin_print_styles-' . $hook_suffix, array($this, 'enqueue_option_page_style'));
        add_action('admin_print_scripts-' . $hook_suffix, array($this, 'enqueue_option_page_script'));
    }
    
    public function enqueue_assets($hook) {
        // Only load on comments admin screens where quick reply or reply workflows exist
        if ($hook !== 'edit-comments.php' && $hook !== 'comment.php') {
            return;
        }
        
        wp_enqueue_style(
            'wpcomments-modern-replies-modal',
            WPCOMMENTS_URL . 'assets/css/modern-replies-modal.css',
            array(),
            WPCOMMENTS_VERSION
        );
        
        wp_enqueue_script(
            'wpcomments-modern-replies-modal',
            WPCOMMENTS_URL . 'assets/js/modern-replies-modal.js',
            array('jquery', 'quicktags'),
            WPCOMMENTS_VERSION,
            true
        );
        
        $replies_list = get_option($this->option_name, array());
        $replies = array();
        
        foreach ($replies_list as $reply) {
            $replies[] = array(
                'slug' => esc_attr($reply['slug'] ?? ''),
                'title' => esc_attr($reply['title'] ?? ''),
                'content' => $this->sanitize_reply($reply['content'] ?? ''),
            );
        }
        
        $localized_data = array(
            'i18n' => array(
                'btn' => esc_attr__('常用回复', 'wpcomments'),
                'tip' => esc_attr__('点击选择一个回复快速插入', 'wpcomments'),
                'insert' => esc_html__('插入', 'wpcomments'),
                'cancel' => esc_html__('取消', 'wpcomments'),
                'pleaseSelect' => esc_html__('请选择一个回复进行插入', 'wpcomments'),
                'pleaseAdd' => esc_html__('请先添加一些回复模板', 'wpcomments'),
                'here' => esc_html__('这里', 'wpcomments'),
                'noItem' => esc_html__('没有找到回复模板', 'wpcomments'),
                'searchPlaceholder' => esc_html__('搜索回复模板...', 'wpcomments'),
                'preview' => esc_html__('预览', 'wpcomments'),
                'close' => esc_html__('关闭', 'wpcomments'),
                'optionsUrl' => esc_url(add_query_arg(array('page' => 'wpcomments-frequently-replies'), get_admin_url() . 'edit-comments.php')),
            ),
            'replies' => $replies,
        );
        
        wp_localize_script('wpcomments-modern-replies-modal', 'wpcommentsReplies', $localized_data);
    }
    
    public function enqueue_option_page_style() {
        wp_enqueue_style(
            'wpcomments-frequently-replies-options',
            WPCOMMENTS_URL . 'assets/css/frequently-replies-options.css',
            array(),
            WPCOMMENTS_VERSION
        );
        
    }
    
    public function enqueue_option_page_script() {
        // 加载统一的管理界面样式
        wp_enqueue_style(
            'wpcomments-admin',
            WPCOMMENTS_URL . 'assets/css/admin.css',
            [],
            WPCOMMENTS_VERSION
        );
        
        wp_enqueue_editor();
        wp_enqueue_script('postbox');
        wp_enqueue_script(
            'wpcomments-frequently-replies-options',
            WPCOMMENTS_URL . 'assets/js/frequently-replies-options.js',
            array('jquery', 'quicktags', 'thickbox', 'postbox'),
            WPCOMMENTS_VERSION,
            true
        );
        
        
        $option_page_localized = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpcomments-frequently-replies-nonce'),
            'i18n' => array(
                'title' => esc_html__('标题', 'wpcomments'),
                'content' => esc_html__('内容', 'wpcomments'),
                'remove' => esc_html__('删除', 'wpcomments'),
                'new_reply' => esc_html__('新回复模板', 'wpcomments'),
                'toggle_panel' => esc_html__('切换面板', 'wpcomments'),
                'title_placeholder' => esc_html__('输入回复模板的标题', 'wpcomments'),
                'content_placeholder' => esc_html__('输入回复内容...', 'wpcomments'),
                'title_description' => esc_html__('为这个回复模板起一个便于识别的名称。', 'wpcomments'),
                'content_description' => esc_html__('输入回复的具体内容，支持HTML标签。', 'wpcomments'),
                'save' => esc_html__('保存所有回复', 'wpcomments'),
                'saving' => esc_html__('保存中...', 'wpcomments'),
                'optionsSaved' => esc_html__('回复列表已保存！', 'wpcomments'),
                'error' => esc_html__('错误！', 'wpcomments'),
            ),
        );
        
        wp_localize_script('wpcomments-frequently-replies-options', 'wpcommentsFrequentlyRepliesOptions', $option_page_localized);
    }
    
    public function render_options_page() {
        $replies = get_option($this->option_name, array());
        $i = 0;
        ?>
        <div class="wrap wpcomments-settings wpcomments-frequently-replies-page" id="wpcomments-frequently-replies">
            <h1><?php echo esc_html__('常用回复列表', 'wpcomments'); ?>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=wpcomments-settings&tab=features')); ?>" class="button button-secondary" style="margin-left: 10px;"><?php esc_html_e('返回设置', 'wpcomments'); ?></a>
            </h1>
            
            <div class="wpcomments-card">
                <h2><?php esc_html_e('管理常用回复模板', 'wpcomments'); ?></h2>
                <p class="wpcomments-section-desc"><?php echo esc_html__('在这里管理您的常用回复模板，可以在回复评论时快速插入预设内容。', 'wpcomments'); ?></p>
            
            <form id="wpcomments-frequently-replies-form" name="wpcomments-frequently-replies-form" method="post">
                
                <div id="replies-item-wrapper">
                    <div id="replies-item-container">
                        <?php
                        if (!empty($replies)) :
                            foreach ($replies as $reply) : ?>
                                <div class="postbox reply-item">
                                    <div class="postbox-header">
                                        <h2 class="hndle">
                                            <span><?php echo esc_html__('回复模板', 'wpcomments'); ?> #<?php echo esc_html((string) ($i + 1)); ?></span>
                                        </h2>
                                        <div class="handle-actions">
                                            <button type="button" class="handlediv" aria-expanded="true">
                                                <span class="screen-reader-text"><?php echo esc_html__('切换面板', 'wpcomments'); ?></span>
                                                <span class="toggle-indicator" aria-hidden="true"></span>
                                            </button>
                                            <button type="button" class="wpcomments-remove button-link-delete">
                                                <?php echo esc_html__('删除', 'wpcomments'); ?>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="inside">
                                        <table class="form-table" role="presentation">
                                            <tbody>
                                                <tr>
                                                    <th scope="row">
                                                        <label for="replytitle-<?php echo esc_attr((string) $i); ?>"><?php echo esc_html__('标题', 'wpcomments'); ?></label>
                                                    </th>
                                                    <td>
                                                        <input type="text" name="replies[<?php echo esc_attr((string) $i); ?>][title]" class="regular-text" id="replytitle-<?php echo esc_attr((string) $i); ?>" value="<?php echo esc_attr($reply['title']); ?>" placeholder="<?php echo esc_attr__('输入回复模板的标题', 'wpcomments'); ?>">
                                                        <p class="description"><?php echo esc_html__('为这个回复模板起一个便于识别的名称。', 'wpcomments'); ?></p>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">
                                                        <label for="replycontent-<?php echo esc_attr((string) $i); ?>"><?php echo esc_html__('内容', 'wpcomments'); ?></label>
                                                    </th>
                                                    <td>
                                                        <textarea name="replies[<?php echo esc_attr((string) $i); ?>][content]" id="replycontent-<?php echo esc_attr((string) $i); ?>" rows="5" cols="50" placeholder="<?php echo esc_attr__('输入回复内容...', 'wpcomments'); ?>"><?php echo esc_textarea($reply['content']); ?></textarea>
                                                        <p class="description"><?php echo esc_html__('输入回复的具体内容，支持HTML标签。', 'wpcomments'); ?></p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php
                                $i++;
                            endforeach;
                        endif;
                        ?>
                        <div class="postbox reply-item">
                            <div class="postbox-header">
                                <h2 class="hndle">
                                    <span><?php echo esc_html__('新回复模板', 'wpcomments'); ?></span>
                                </h2>
                                <div class="handle-actions">
                                    <button type="button" class="handlediv button-link" aria-expanded="true">
                                        <span class="screen-reader-text"><?php echo esc_html__('切换面板', 'wpcomments'); ?></span>
                                        <span class="toggle-indicator" aria-hidden="true"></span>
                                    </button>
                                    <button type="button" class="wpcomments-remove button-link-delete">
                                        <?php echo esc_html__('删除', 'wpcomments'); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="inside">
                                <table class="form-table" role="presentation">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <label for="replytitle-<?php echo esc_attr((string) $i); ?>"><?php echo esc_html__('标题', 'wpcomments'); ?></label>
                                            </th>
                                            <td>
                                                <input type="text" name="replies[<?php echo esc_attr((string) $i); ?>][title]" class="regular-text" id="replytitle-<?php echo esc_attr((string) $i); ?>" value="" placeholder="<?php echo esc_attr__('输入回复模板的标题', 'wpcomments'); ?>">
                                                <p class="description"><?php echo esc_html__('为这个回复模板起一个便于识别的名称。', 'wpcomments'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label for="replycontent-<?php echo esc_attr((string) $i); ?>"><?php echo esc_html__('内容', 'wpcomments'); ?></label>
                                            </th>
                                            <td>
                                                <textarea name="replies[<?php echo esc_attr((string) $i); ?>][content]" id="replycontent-<?php echo esc_attr((string) $i); ?>" rows="5" cols="50" placeholder="<?php echo esc_attr__('输入回复内容...', 'wpcomments'); ?>"></textarea>
                                                <p class="description"><?php echo esc_html__('输入回复的具体内容，支持HTML标签。', 'wpcomments'); ?></p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="wpcomments-snackbar" class="notice notice-success is-dismissible" style="display: none;">
                    <p></p>
                </div>
                
                <div class="wpcomments-submit-wrapper">
                    <input type="hidden" name="action" value="save_wpcomments_frequently_replies">
                    <?php wp_nonce_field('wpcomments-frequently-replies-nonce', 'wpcomments_nonce'); ?>
                    <?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false); ?>
                    <input type="hidden" name="page" value="wpcomments-frequently-replies" />
                    
                    <div class="wpcomments-action-buttons">
                        <button type="button" class="button button-secondary" id="add-another-reply">
                            <?php echo esc_html__('添加新回复', 'wpcomments'); ?>
                        </button>
                        
                        <button id="save-options" class="button button-primary" type="submit">
                            <?php echo esc_html__('保存所有回复', 'wpcomments'); ?>
                        </button>
                    </div>
                </div>
            </form>
            </div>
        </div>
        <?php
    }
    
    public function save_replies_ajax() {
        check_ajax_referer('wpcomments-frequently-replies-nonce', 'wpcomments_nonce');
        
        if (!current_user_can('moderate_comments')) {
            wp_send_json_error(
                array(
                    'success' => false,
                    'response' => 403,
                    'message' => '访问被拒绝',
                ),
                403
            );
        }
        
        $posted_replies = isset($_POST['replies']) ? (array) wp_unslash($_POST['replies']) : array();
        $replies = array_map(
            function ($reply) {
                $reply = is_array($reply) ? $reply : array();
                return array(
                    'title' => sanitize_text_field($reply['title'] ?? ''),
                    'content' => $this->sanitize_reply($reply['content'] ?? ''),
                );
            },
            $posted_replies
        );
        $sanitized_replies = array();
        $i = 0;
        
        if (!empty($replies)) {
            foreach ($replies as $reply) {
                if (empty(trim($reply['title'])) && empty(trim($reply['content']))) {
                    continue;
                }
                
                $sanitized_replies[] = array(
                    'slug' => empty($reply['title']) ? "reply-title-$i" : sanitize_title($reply['title']),
                    'title' => empty($reply['title']) ? "回复 #$i" : $reply['title'],
                    'content' => $reply['content'],
                );
                
                $i++;
            }
        }
        
        $existing = get_option($this->option_name);
        
        if ($existing === $sanitized_replies) {
            wp_send_json_success(
                array(
                    'success' => true,
                    'response' => 200,
                    'message' => esc_html__('回复保存成功，没有变化！', 'wpcomments'),
                )
            );
        }
        
        $saved = update_option($this->option_name, $sanitized_replies, false);
        
        if ($saved) {
            wp_send_json_success(
                array(
                    'success' => true,
                    'response' => 200,
                    'message' => esc_html__('回复保存成功！', 'wpcomments'),
                )
            );
        }
        
        wp_send_json_error(
            array(
                'success' => false,
                'response' => 200,
                'message' => esc_html__('保存回复失败！', 'wpcomments'),
            )
        );
    }
    
    private function sanitize_reply($reply_content) {
        $allowed_tags = apply_filters('wpcomments_frequently_replies_allowed_tags', array(
            'a' => array(
                'href' => true,
                'title' => true,
            ),
            'span' => array(
                'class' => true,
                'data-mce-bogus' => true,
                'data-mce-type' => true,
            ),
            'p' => array(),
            'strong' => array(),
            'em' => array(),
            'ul' => array(),
            'ol' => array(),
            'li' => array(),
            'br' => array(),
            'blockquote' => array(),
            'code' => array(),
            'pre' => array(),
        ));
        
        return wp_kses($reply_content, $allowed_tags);
    }
    
    public function auto_save_replies_ajax() {
        if (!current_user_can('moderate_comments')) {
            wp_send_json_error(
                array(
                    'success' => false,
                    'response' => 403,
                    'message' => '访问被拒绝',
                ),
                403
            );
        }
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wpcomments_frequently_replies_nonce')) {
            wp_send_json_error(array('message' => esc_html__('安全验证失败', 'wpcomments')));
        }
        
        $posted_replies = isset($_POST['replies']) ? (array) wp_unslash($_POST['replies']) : array();
        $replies = array_map(
            function ($reply) {
                $reply = is_array($reply) ? $reply : array();
                return array(
                    'title' => sanitize_text_field($reply['title'] ?? ''),
                    'content' => $this->sanitize_reply($reply['content'] ?? ''),
                );
            },
            $posted_replies
        );
        $sanitized_replies = array();
        
        foreach ($replies as $reply) {
            if (!empty($reply['title']) || !empty($reply['content'])) {
                $sanitized_replies[] = $reply;
            }
        }
        
        update_option('wpcomments_frequently_replies', $sanitized_replies);
        
        wp_send_json_success(array(
            'message' => esc_html__('自动保存成功', 'wpcomments'),
            'count' => count($sanitized_replies)
        ));
    }

    public function handle_postbox_ajax() {
        if (!current_user_can('manage_options')) {
            wp_die('-1');
        }

        check_ajax_referer('meta-box-order');
        
        $page = isset($_POST['page']) ? sanitize_key(wp_unslash($_POST['page'])) : '';
        
        if ($page === 'wpcomments-frequently-replies') {
            if (isset($_POST['closed'])) {
                $closed = array_map('sanitize_key', explode(',', sanitize_text_field(wp_unslash($_POST['closed']))));
                update_user_meta(get_current_user_id(), 'closedpostboxes_' . $page, $closed);
            }
            if (isset($_POST['order'])) {
                $order = array_map('sanitize_text_field', (array) wp_unslash($_POST['order']));
                update_user_meta(get_current_user_id(), 'meta-box-order_' . $page, $order);
            }
        }
        
        wp_die('1');
    }
}
