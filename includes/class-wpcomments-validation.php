<?php
/**
 * Comment validation functionality.
 *
 * @package WPComments
 * @since   1.0.0
 */

namespace WPComments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPComments_Validation
 *
 * Handles comment form validation functionality.
 */
class WPComments_Validation {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'add_validation_script'));
        add_filter('preprocess_comment', array($this, 'block_active_links'));
        
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'load_admin_scripts'));
        }
    }
    
    public function enqueue_scripts() {
        if (is_single() && comments_open()) {
            wp_enqueue_script(
                'wpcomments-jquery-validate',
                WPCOMMENTS_URL . 'assets/js/jquery.validate.min.js',
                array('jquery'),
                '1.19.2',
                true
            );
            
            wp_enqueue_style(
                'wpcomments-validation-style',
                WPCOMMENTS_URL . 'assets/css/validation-style.css',
                array(),
                WPCOMMENTS_VERSION
            );
        }
    }
    
    public function add_validation_script() {
        if (!is_single() || !comments_open()) {
            return;
        }
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                if ($("#commentform").hasClass("comment-form")) {
                    $("#new-post, #commentform").validate({
                        rules: {
                            author             : {required: true, minlength: 2},
                            bbp_anonymous_name : {required: true, minlength: 2},
                            email              : {required: true, email: true},
                            bbp_anonymous_email: {required: true, email: true},
                            bbp_reply_content  : {required: true, minlength: 20},
                            comment            : {required: true, minlength: 20},
                            url                : {required: false, url: true }
                        },
                        messages: {
                            author             : "<?php echo esc_js(__('Please enter your name.', 'wpcomments')); ?>",
                            bbp_anonymous_name : "<?php echo esc_js(__('Please enter your name.', 'wpcomments')); ?>",
                            email              : "<?php echo esc_js(__('Please enter a valid email address.', 'wpcomments')); ?>",
                            bbp_anonymous_email: "<?php echo esc_js(__('Please enter a valid email address.', 'wpcomments')); ?>",
                            bbp_reply_content  : "<?php echo esc_js(__('The message must be at least 20 characters.', 'wpcomments')); ?>",
                            comment            : "<?php echo esc_js(__('The message must be at least 20 characters.', 'wpcomments')); ?>",
                            url                : "<?php echo esc_js(__('Please enter a valid URL.', 'wpcomments')); ?>"
                        }
                    });
                }
            });
        </script>
        <?php
    }
    
    public function block_active_links($commentdata) {
        if (!is_admin() && str_contains($commentdata['comment_content'], "href=")) {
            wp_die(
                __("Active links in comments are prohibited. Go back and edit the post.", 'wpcomments') . 
                '<br /><br /><a href="javascript:history.go(-1);">' . 
                __("Go back and edit a comment.", 'wpcomments') . 
                '</a>'
            );
        }
        return $commentdata;
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'wpcomments-settings',
            __('Comment Validation', 'wpcomments'),
            __('Comment Validation', 'wpcomments'),
            'manage_options',
            'wpcomments-validation',
            array($this, 'admin_page')
        );
    }
    
    public function load_admin_scripts() {
        wp_enqueue_style(
            'wpcomments-validation-admin',
            WPCOMMENTS_URL . 'assets/css/validation-admin.css',
            array(),
            WPCOMMENTS_VERSION
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap wpcomments-validation-admin">
            <h1><?php echo esc_html(__('Comment Validation Settings', 'wpcomments')); ?></h1>
            
            <div class="card">
                <h2><?php echo esc_html(__('Validation Rules', 'wpcomments')); ?></h2>
                <p><?php echo esc_html(__('The comment validation feature automatically validates comment forms with the following rules:', 'wpcomments')); ?></p>
                
                <ul>
                    <li><?php echo esc_html(__('Name must be at least 2 characters long', 'wpcomments')); ?></li>
                    <li><?php echo esc_html(__('Email must be a valid email address', 'wpcomments')); ?></li>
                    <li><?php echo esc_html(__('Comment must be at least 20 characters long', 'wpcomments')); ?></li>
                    <li><?php echo esc_html(__('Website URL must be valid (if provided)', 'wpcomments')); ?></li>
                    <li><?php echo esc_html(__('Active links in comments are blocked', 'wpcomments')); ?></li>
                </ul>
                
                <p><?php echo esc_html(__('This feature works automatically for standard WordPress comment forms and bbPress forms.', 'wpcomments')); ?></p>
            </div>
            
            <div class="card">
                <h2><?php echo esc_html(__('Technical Information', 'wpcomments')); ?></h2>
                <p><?php echo esc_html(__('The validation is powered by jQuery Validate plugin and works on the frontend to provide immediate feedback to users.', 'wpcomments')); ?></p>
                <p><?php echo esc_html(__('Server-side validation is also implemented to prevent malicious submissions.', 'wpcomments')); ?></p>
            </div>
        </div>
        <?php
    }
}