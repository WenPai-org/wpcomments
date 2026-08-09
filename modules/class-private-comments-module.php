<?php

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

class Private_Comments_Module implements WPComments_Module_Interface {
    
    public function id(): string {
        return 'private_comments';
    }
    
    public function is_enabled(array $settings): bool {
        return !empty($settings['private_comments']);
    }
    
    public function register(): void {
        $this->init();
    }
    
    public function get_name(): string {
        return __('私密评论', 'wpcomments');
    }
    
    public function get_description(): string {
        return __('允许用户提交仅管理员可见的私密评论', 'wpcomments');
    }
    
    private function init(): void {
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
            $data['comment_approved'] = 0;
        }
        return $data;
    }
    
    public function filter_private_comment($comment) {
        if (!is_admin() && is_user_logged_in() && current_user_can('moderate_comments')) {
            if ($this->is_private_comment($comment->comment_ID)) {
                $comment->comment_approved = 1;
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
            wp_set_comment_status($comment_id, 'hold');
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
}
