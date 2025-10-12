<?php
/**
 * Frequently Replies Module (wrapper)
 */

namespace WPComments\Modules;

use WPComments\WPComments_Module_Interface;

if (!defined('ABSPATH')) {
    exit;
}

class Frequently_Replies_Module implements WPComments_Module_Interface {
    public function id(): string { return 'frequently_replies'; }

    public function is_enabled(array $settings): bool {
        return !empty($settings['frequently_replies_enabled']);
    }

    public function register(): void {
        if (class_exists('WPComments\\WPComments_Frequently_Replies')) {
            // Instantiate the existing feature class
            new \WPComments\WPComments_Frequently_Replies();
        }
    }
}
