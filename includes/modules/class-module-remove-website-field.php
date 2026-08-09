<?php
/**
 * Legacy Remove Website Field module wrapper.
 *
 * @deprecated 2.0.1 Use \WPComments\Remove_Website_Field_Module directly.
 */

namespace WPComments\Modules;

use WPComments\WPComments_Module_Interface;

if (!defined('ABSPATH')) {
    exit;
}

class Remove_Website_Field_Module implements WPComments_Module_Interface {
    public function id(): string {
        return 'remove_website_field';
    }

    public function is_enabled(array $settings): bool {
        return !empty($settings['remove_website_field_enabled']);
    }

    public function register(): void {
        if (class_exists('WPComments\\Remove_Website_Field_Module')) {
            $module = new \WPComments\Remove_Website_Field_Module();
            $module->register();
        }
    }
}
