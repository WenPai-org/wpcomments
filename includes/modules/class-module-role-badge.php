<?php
/**
 * Role Badge Module
 */

namespace WPComments\Modules;

use WPComments\WPComments_Module_Interface;
use WPComments\WPComments_Core;

if (!defined('ABSPATH')) {
    exit;
}

class Role_Badge_Module implements WPComments_Module_Interface {
    public function id(): string { return 'role_badge'; }

    public function is_enabled(array $settings): bool {
        return !empty($settings['role_badge_enabled']);
    }

    public function register(): void {
        $core = WPComments_Core::get_instance();
        if ($core) {
            $core->register_role_badge_module();
        }
    }

    // 前端样式由模块负责
    public function enqueue_frontend(): void {
        wp_enqueue_style('wpcomments-role-badge', WPCOMMENTS_URL . 'assets/comment-author-role-badge.css', array(), WPCOMMENTS_VERSION);
    }
}
