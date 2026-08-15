<?php
/**
 * Legacy Validation module wrapper.
 *
 * @deprecated 2.0.1 Use \WPComments\Validation_Module directly.
 */

namespace WPComments\Modules;

use WPComments\WPComments_Module_Interface;

if (!defined('ABSPATH')) {
    exit;
}

class Validation_Module implements WPComments_Module_Interface {
    public function id(): string {
        return 'validation';
    }

    public function is_enabled(array $settings): bool {
        return !empty($settings['validation_enabled']);
    }

    public function register(): void {
        if (class_exists('WPComments\\Validation_Module')) {
            $module = new \WPComments\Validation_Module();
            $module->register();
        }
    }
}
