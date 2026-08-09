<?php
/**
 * Legacy Sticky Comments module wrapper.
 *
 * @deprecated 2.0.1 Use \WPComments\Sticky_Comments_Module directly.
 */

namespace WPComments\Modules;

use WPComments\WPComments_Module_Interface;

if (!defined('ABSPATH')) {
    exit;
}

class Sticky_Module implements WPComments_Module_Interface {
    public function id(): string {
        return 'sticky_comments';
    }

    public function is_enabled(array $settings): bool {
        return !empty($settings['sticky_comments_enabled']);
    }

    public function register(): void {
        if (class_exists('WPComments\\Sticky_Comments_Module')) {
            $module = new \WPComments\Sticky_Comments_Module();
            $module->register();
        }
    }
}
