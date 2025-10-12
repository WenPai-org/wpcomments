<?php
/**
 * Validation Module
 */

namespace WPComments\Modules;

use WPComments\WPComments_Module_Interface;
use WPComments\WPComments_Core;

if (!defined('ABSPATH')) {
    exit;
}

class Validation_Module implements WPComments_Module_Interface {
    public function id(): string { return 'validation'; }

    public function is_enabled(array $settings): bool {
        return !empty($settings['validation_enabled']);
    }

    public function register(): void {
        $core = WPComments_Core::get_instance();
        if ($core) {
            $core->register_validation_module();
        }
    }

    public function enqueue_frontend(): void {
        $core = WPComments_Core::get_instance();
        if ($core) {
            $core->enqueue_validation_scripts();
        }
    }
}
