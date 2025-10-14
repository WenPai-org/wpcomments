<?php
/**
 * WPComments Bootstrap Class
 *
 * @package WPComments
 * @since   2.0.0
 */

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bootstrap class for WPComments plugin
 * 
 * Handles the initialization of different modes:
 * - Disable Mode: Simple comment disabling functionality
 * - Enhanced Mode: Full modular comment enhancement system
 */
class WPComments_Bootstrap {
    
    /** @var self|null */
    private static $instance = null;
    
    /** @var array */
    private $feature_settings = [];
    
    /** @var bool */
    private $is_initialized = false;

    /**
     * Get singleton instance
     *
     * @return self
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init(): void {
        if ($this->is_initialized) {
            return;
        }

        $this->load_dependencies();
        $this->feature_settings = $this->get_feature_settings();
        
        $comments_disabled = !empty($this->feature_settings['comments_disabled']);

        if ($comments_disabled) {
            $this->init_disable_mode();
        } else {
            $this->init_enhanced_mode();
        }

        $this->init_admin_interface();
        $this->is_initialized = true;
    }

    /**
     * Load required dependencies
     *
     * @return void
     */
    private function load_dependencies(): void {
        $core_files = [
            'class-wpcomments-settings.php',
            'class-wpcomments-network-settings.php',
        ];

        foreach ($core_files as $file) {
            require_once WPCOMMENTS_PATH . 'includes/' . $file;
        }
    }

    /**
     * Initialize disable mode - simple comment disabling
     *
     * @return void
     */
    private function init_disable_mode(): void {
        require_once WPCOMMENTS_PATH . 'includes/class-wpcomments-disable-mode.php';
        
        $disable_mode = new WPComments_Disable_Mode();
        $disable_mode->setup();
    }

    /**
     * Initialize enhanced mode - full modular system
     *
     * @return void
     */
    private function init_enhanced_mode(): void {
        $this->load_enhanced_dependencies();
        
        WPComments_Core::get_instance();
    }

    /**
     * Load dependencies for enhanced mode
     *
     * @return void
     */
    private function load_enhanced_dependencies(): void {
        require_once WPCOMMENTS_PATH . 'includes/class-wpcomments-core.php';
        require_once WPCOMMENTS_PATH . 'includes/class-wpcomments-modules.php';

        $legacy_includes = [
            'class-wpcomments-email-notification.php',
            'class-wpcomments-frequently-replies.php',
            'class-wpcomments-moderation-info.php',
        ];

        foreach ($legacy_includes as $include) {
            $file_path = WPCOMMENTS_PATH . 'includes/' . $include;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }



    /**
     * Initialize admin interface
     *
     * @return void
     */
    private function init_admin_interface(): void {
        if (is_admin()) {
            new WPComments_Settings();
        }

        if (is_multisite() && is_network_admin()) {
            new WPComments_Network_Settings();
        }
    }

    /**
     * Get feature settings based on network or site configuration
     *
     * @return array Feature settings array
     */
    private function get_feature_settings(): array {
        $defaults = [
            'comments_disabled'             => false,
            'role_badge_enabled'            => true,
            'delete_pending_enabled'        => true,
            'remove_website_field_enabled'  => false,
            'frequently_replies_enabled'    => true,
            'validation_enabled'            => true,
            'moderation_info_enabled'       => true,
            'email_notification_enabled'    => true,
            'private_comments_enabled'      => true,
            'comment_limiter_enabled'       => true,
            'report_comments_enabled'       => false,
            'sticky_comments_enabled'       => true,
        ];

        if (is_multisite()) {
            return [
                'comments_disabled'             => WPComments_Network_Settings::get_effective_setting('wpcomments_disable_comments', $defaults['comments_disabled']),
                'role_badge_enabled'            => WPComments_Network_Settings::get_effective_setting('wpcomments_enable_role_badge', $defaults['role_badge_enabled']),
                'delete_pending_enabled'        => WPComments_Network_Settings::get_effective_setting('wpcomments_enable_delete_pending', $defaults['delete_pending_enabled']),
                'remove_website_field_enabled'  => WPComments_Network_Settings::get_effective_setting('wpcomments_enable_remove_website_field', $defaults['remove_website_field_enabled']),
                'frequently_replies_enabled'    => WPComments_Network_Settings::get_effective_setting('wpcomments_enable_frequently_replies', $defaults['frequently_replies_enabled']),
                'validation_enabled'            => WPComments_Network_Settings::get_effective_setting('wpcomments_enable_validation', $defaults['validation_enabled']),
                'moderation_info_enabled'       => WPComments_Network_Settings::get_effective_setting('wpcomments_enable_moderation_info', $defaults['moderation_info_enabled']),
                'email_notification_enabled'    => WPComments_Network_Settings::get_effective_setting('wpcomments_enable_email_notification', $defaults['email_notification_enabled']),
                'private_comments_enabled'      => WPComments_Network_Settings::get_effective_setting('wpcomments_enable_private_comments', $defaults['private_comments_enabled']),
                'comment_limiter_enabled'       => WPComments_Network_Settings::get_effective_setting('wpcomments_enable_comment_limiter', $defaults['comment_limiter_enabled']),
                'report_comments_enabled'       => WPComments_Network_Settings::get_effective_setting('wpcomments_enable_report_comments', $defaults['report_comments_enabled']),
                'sticky_comments_enabled'       => WPComments_Network_Settings::get_effective_setting('wpcomments_enable_sticky_comments', $defaults['sticky_comments_enabled']),
            ];
        }

        return [
            'comments_disabled'             => get_option('wpcomments_disable_comments', $defaults['comments_disabled']),
            'role_badge_enabled'            => get_option('wpcomments_enable_role_badge', $defaults['role_badge_enabled']),
            'delete_pending_enabled'        => get_option('wpcomments_enable_delete_pending', $defaults['delete_pending_enabled']),
            'remove_website_field_enabled'  => get_option('wpcomments_enable_remove_website_field', $defaults['remove_website_field_enabled']),
            'frequently_replies_enabled'    => get_option('wpcomments_enable_frequently_replies', $defaults['frequently_replies_enabled']),
            'validation_enabled'            => get_option('wpcomments_enable_validation', $defaults['validation_enabled']),
            'moderation_info_enabled'       => get_option('wpcomments_enable_moderation_info', $defaults['moderation_info_enabled']),
            'email_notification_enabled'    => get_option('wpcomments_enable_email_notification', $defaults['email_notification_enabled']),
            'private_comments_enabled'      => get_option('wpcomments_enable_private_comments', $defaults['private_comments_enabled']),
            'comment_limiter_enabled'       => get_option('wpcomments_enable_comment_limiter', $defaults['comment_limiter_enabled']),
            'report_comments_enabled'       => get_option('wpcomments_enable_report_comments', $defaults['report_comments_enabled']),
            'sticky_comments_enabled'       => get_option('wpcomments_enable_sticky_comments', $defaults['sticky_comments_enabled']),
        ];
    }

    /**
     * Reload the plugin with new settings
     *
     * @param array $new_settings New settings to apply
     * @return void
     */
    public function reload(array $new_settings = []): void {
        $this->is_initialized = false;
        
        if (!empty($new_settings)) {
            $this->feature_settings = $new_settings;
        }
        
        $this->init();
    }

    /**
     * Get current feature settings
     *
     * @return array
     */
    public function get_current_settings(): array {
        return $this->feature_settings;
    }
}