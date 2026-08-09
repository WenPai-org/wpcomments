<?php
/**
 * Legacy Private Comments module wrapper.
 *
 * @deprecated 2.0.1 Use \WPComments\Private_Comments_Module directly.
 */

namespace WPComments\Modules;

use WPComments\WPComments_Module_Interface;

if (!defined('ABSPATH')) {
    exit;
}

class Private_Comments_Module implements WPComments_Module_Interface {
    public function id(): string {
        return 'private_comments';
    }

    public function is_enabled(array $settings): bool {
        return !empty($settings['private_comments_enabled']);
    }

    public function register(): void {
        if (class_exists('WPComments\\Private_Comments_Module')) {
            $module = new \WPComments\Private_Comments_Module();
            $module->register();
        }
    }
}
