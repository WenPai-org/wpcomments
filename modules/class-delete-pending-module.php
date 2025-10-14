<?php

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

class Delete_Pending_Module implements WPComments_Module_Interface {
    
    public function id(): string {
        return 'delete_pending';
    }
    
    public function is_enabled(array $settings): bool {
        return !empty($settings['delete_pending']);
    }
    
    public function register(): void {
        $this->init();
    }
    
    public function get_name(): string {
        return __('删除待审核评论', 'wpcomments');
    }
    
    public function get_description(): string {
        return __('批量删除所有待审核的评论', 'wpcomments');
    }
    
    private function init(): void {
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
}