<?php
/**
 * Remove Website Field Module
 */

namespace WPComments\Modules;

use WPComments\WPComments_Module_Interface;
use WPComments\WPComments_Core;

if (!defined('ABSPATH')) {
    exit;
}

class Remove_Website_Field_Module implements WPComments_Module_Interface {
    public function id(): string { return 'remove_website_field'; }

    public function is_enabled(array $settings): bool {
        return !empty($settings['remove_website_field_enabled']);
    }

    public function register(): void {
        $core = WPComments_Core::get_instance();
        if ($core) {
            $core->register_remove_website_field_module();
        }
    }
}