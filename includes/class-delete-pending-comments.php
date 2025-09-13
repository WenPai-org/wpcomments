<?php

namespace WpComments;

class Delete_Pending_Comments {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('init', array($this, 'load_textdomain'));
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('wpcomments', false, dirname(plugin_basename(__FILE__)) . '/../languages/');
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'edit-comments.php',
            __('Delete Pending Comments', 'wpcomments'),
            __('Delete Pending Comments', 'wpcomments'),
            'manage_options',
            'wpcomments-delete-pending',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        global $wpdb;
        
        $magic_string = __('I am sure I want to delete all pending comments and realize this cannot be undone', 'wpcomments');
        
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        
        <div class="wrap">
            <h1><?php _e('Delete Pending Comments', 'wpcomments'); ?></h1>
            
            <?php
            if (isset($_POST['wpcomments_delete_pending'])) {
                if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'wpcomments-delete-pending-comments')) {
                    wp_die(__('Security check failed', 'wpcomments'));
                }
                
                if (stripslashes($_POST['wpcomments_delete_pending']) === $magic_string) {
                    $deleted_count = $wpdb->query("DELETE FROM $wpdb->comments WHERE comment_approved = '0'");
                    ?>
                    <div class="notice notice-success">
                        <p>
                            <?php 
                            printf(
                                _n(
                                    '%d pending comment has been deleted successfully.',
                                    '%d pending comments have been deleted successfully.',
                                    $deleted_count,
                                    'wpcomments'
                                ),
                                number_format_i18n($deleted_count)
                            );
                            ?>
                        </p>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="notice notice-error">
                        <p><?php _e('Please try again. Did you copy the text properly?', 'wpcomments'); ?></p>
                    </div>
                    <?php
                }
            }
            
            $pending_comment_ids = $wpdb->get_col("SELECT comment_ID FROM $wpdb->comments WHERE comment_approved = '0'");
            $pending_comments_count = count($pending_comment_ids);
            
            if ($pending_comments_count > 0) {
                ?>
                <div class="card">
                    <h2><?php _e('Pending Comments Overview', 'wpcomments'); ?></h2>
                    <p>
                        <?php
                        printf(
                            _n(
                                'You have %s pending comment in your site. Do you want to delete it?',
                                'You have %s pending comments in your site. Do you want to delete all of them?',
                                $pending_comments_count,
                                'wpcomments'
                            ),
                            '<strong>' . number_format_i18n($pending_comments_count) . '</strong>'
                        );
                        ?>
                    </p>
                    
                    <div class="notice notice-warning">
                        <p><strong><?php _e('Warning:', 'wpcomments'); ?></strong> <?php _e('This action cannot be undone. Please make sure you have a backup of your database before proceeding.', 'wpcomments'); ?></p>
                    </div>
                    
                    <p><?php _e('To confirm deletion, please type the following text exactly into the textbox:', 'wpcomments'); ?></p>
                    
                    <blockquote style="background: #f1f1f1; padding: 10px; border-left: 4px solid #0073aa;">
                        <em><?php echo esc_html($magic_string); ?></em>
                    </blockquote>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('wpcomments-delete-pending-comments'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="wpcomments_delete_pending"><?php _e('Confirmation Text', 'wpcomments'); ?></label>
                                </th>
                                <td>
                                    <input name="wpcomments_delete_pending" type="text" id="wpcomments_delete_pending" class="regular-text" autocomplete="off" />
                                    <p class="description"><?php _e('Type the exact text shown above to confirm deletion.', 'wpcomments'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Delete All Pending Comments', 'wpcomments'), 'delete', 'submit', true, array('onclick' => 'return confirm("' . esc_js(__('Are you absolutely sure you want to delete all pending comments? This action cannot be undone!', 'wpcomments')) . '");')); ?>
                    </form>
                </div>
                <?php
            } else {
                ?>
                <div class="notice notice-info">
                    <p><?php _e('There are no pending comments in your site.', 'wpcomments'); ?></p>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }
    
    public function get_pending_comments_count() {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = '0'");
    }
    
    public function delete_pending_comments() {
        global $wpdb;
        return $wpdb->query("DELETE FROM $wpdb->comments WHERE comment_approved = '0'");
    }
}