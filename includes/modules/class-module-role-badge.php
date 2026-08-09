<?php
/**
 * Legacy Role Badge module wrapper.
 *
 * @deprecated 2.0.1 Use \WPComments\Role_Badge_Module directly.
 */

namespace WPComments\Modules;

use WPComments\WPComments_Module_Interface;

if (!defined('ABSPATH')) {
    exit;
}

class Role_Badge_Module implements WPComments_Module_Interface {
    public function id(): string {
        return 'role_badge';
    }

    public function is_enabled(array $settings): bool {
        return !empty($settings['role_badge_enabled']);
    }

    public function register(): void {
        if (class_exists('WPComments\\Role_Badge_Module')) {
            $module = new \WPComments\Role_Badge_Module();
            $module->register();
        }
    }
}
