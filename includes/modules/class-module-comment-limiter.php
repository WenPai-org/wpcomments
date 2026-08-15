<?php
/**
 * Legacy Comment Limiter module wrapper.
 *
 * @deprecated 2.0.1 Use \WPComments\Character_Limiter_Module directly.
 */

namespace WPComments\Modules;

use WPComments\WPComments_Module_Interface;

if (!defined('ABSPATH')) {
    exit;
}

class Comment_Limiter_Module implements WPComments_Module_Interface {
    public function id(): string {
        return 'comment_limiter';
    }

    public function is_enabled(array $settings): bool {
        return !empty($settings['comment_limiter_enabled']);
    }

    public function register(): void {
        if (class_exists('WPComments\\Character_Limiter_Module')) {
            $module = new \WPComments\Character_Limiter_Module();
            $module->register();
        }
    }
}
