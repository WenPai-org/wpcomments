<?php
/**
 * WPComments 核心类
 * 
 * @package WPComments
 * @since 1.0.0
 */

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

class WPComments_Core {
    
    private static $instance = null;

    /** @var array<string,bool> */
    private $feature_settings = array();
    
    private $role_badge_registered = false;
    private $delete_pending_registered = false;
    private $remove_website_registered = false;
    private $validation_registered = false;
    private $private_comments_registered = false;
    private $character_limiter_registered = false;
    private $reporting_registered = false;
    private $sticky_registered = false;
    
    private function __construct(array $feature_settings) {
        $this->feature_settings = $feature_settings;
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        $this->load_features();
    }
    
    private function load_features() {
        $settings = array(
            'delete_pending'      => !empty($this->feature_settings['delete_pending_enabled']),
            'remove_website_field'=> !empty($this->feature_settings['remove_website_field_enabled']),
            'validation'          => !empty($this->feature_settings['validation_enabled']),
            'private_comments'    => !empty($this->feature_settings['private_comments_enabled']),
            'character_limiter'   => !empty($this->feature_settings['comment_limiter_enabled']),
            'comment_reporting'   => !empty($this->feature_settings['report_comments_enabled']),
            'sticky_comments'     => !empty($this->feature_settings['sticky_comments_enabled']),
            'role_badge'          => !empty($this->feature_settings['role_badge_enabled']),
            'gongan_beian'        => !empty($this->feature_settings['gongan_beian_enabled']),
        );
        
        $module_manager = \WPComments\WPComments_Module_Manager::get_instance();
        
        require_once WPCOMMENTS_PATH . 'modules/class-delete-pending-module.php';
        require_once WPCOMMENTS_PATH . 'modules/class-remove-website-field-module.php';
        require_once WPCOMMENTS_PATH . 'modules/class-validation-module.php';
        require_once WPCOMMENTS_PATH . 'modules/class-private-comments-module.php';
        require_once WPCOMMENTS_PATH . 'modules/class-character-limiter-module.php';
        require_once WPCOMMENTS_PATH . 'modules/class-comment-reporting-module.php';
        require_once WPCOMMENTS_PATH . 'modules/class-sticky-comments-module.php';
        require_once WPCOMMENTS_PATH . 'modules/class-role-badge-module.php';
        require_once WPCOMMENTS_PATH . 'modules/class-gongan-beian-module.php';
        
        $module_manager->register_module(new \WPComments\Delete_Pending_Module());
        $module_manager->register_module(new \WPComments\Remove_Website_Field_Module());
        $module_manager->register_module(new \WPComments\Validation_Module());
        $module_manager->register_module(new \WPComments\Private_Comments_Module());
        $module_manager->register_module(new \WPComments\Character_Limiter_Module());
        $module_manager->register_module(new \WPComments\Comment_Reporting_Module());
        $module_manager->register_module(new \WPComments\Sticky_Comments_Module());
        $module_manager->register_module(new \WPComments\Role_Badge_Module());
        $module_manager->register_module(new \WPComments\Gongan_Beian_Module());
        
        $module_manager->boot($settings);
        
        $this->init_legacy_features();
    }
    
    private function init_legacy_features(): void {
        if (!empty($this->feature_settings['email_notification_enabled'])) {
            new \WPComments\WPComments_Email_Notification();
        }
        if (!empty($this->feature_settings['frequently_replies_enabled'])) {
            new \WPComments\WPComments_Frequently_Replies();
        }
        if (!empty($this->feature_settings['moderation_info_enabled'])) {
            \WPComments\WPComments_Moderation_Info::get_instance();
        }
    }
    
    public static function get_instance(array $feature_settings = array()) {
        if (null === self::$instance) {
            self::$instance = new self($feature_settings);
        }
        return self::$instance;
    }
    
    public function enqueue_frontend_assets() {
        // Feature modules enqueue only the assets they actually ship.
    }
    
    public function enqueue_admin_assets() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && strpos((string) $screen->id, 'wpcomments') !== false) {
            wp_enqueue_style('wpcomments-admin', WPCOMMENTS_URL . 'assets/css/admin.css', array(), WPCOMMENTS_VERSION);
            wp_enqueue_script('wpcomments-admin', WPCOMMENTS_URL . 'assets/js/admin.js', array('jquery'), WPCOMMENTS_VERSION, true);
        }
    }
    
    public function register_role_badge_module(): void {
        if ($this->role_badge_registered) { return; }
        $this->role_badge_registered = true;
    }
    
    public function register_delete_pending_module(): void {
        if ($this->delete_pending_registered) { return; }
        $this->delete_pending_registered = true;
    }
    
    public function register_remove_website_module(): void {
        if ($this->remove_website_registered) { return; }
        $this->remove_website_registered = true;
    }
    
    public function register_validation_module(): void {
        if ($this->validation_registered) { return; }
        $this->validation_registered = true;
    }
    
    public function register_private_comments_module(): void {
        if ($this->private_comments_registered) { return; }
        $this->private_comments_registered = true;
    }
    
    public function register_character_limiter_module(): void {
        if ($this->character_limiter_registered) { return; }
        $this->character_limiter_registered = true;
    }
    
    public function register_reporting_module(): void {
        if ($this->reporting_registered) { return; }
        $this->reporting_registered = true;
    }
    
    public function register_sticky_module(): void {
        if ($this->sticky_registered) { return; }
        $this->sticky_registered = true;
    }
}
