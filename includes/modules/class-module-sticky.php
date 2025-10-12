<?php
/**
 * Sticky Comments Module
 */

namespace WPComments\Modules;

use WPComments\WPComments_Module_Interface;
use WPComments\WPComments_Core;

if (!defined('ABSPATH')) {
    exit;
}

class Sticky_Module implements WPComments_Module_Interface {
    public function id(): string { return 'sticky_comments'; }

    public function is_enabled(array $settings): bool {
        return !empty($settings['sticky_comments_enabled']);
    }

    public function register(): void {
        $core = WPComments_Core::get_instance();
        if ($core) {
            $core->register_sticky_module();
        }
    }

    public function enqueue_frontend(): void {
        $core = WPComments_Core::get_instance();
        if ($core) {
            $core->enqueue_sticky_frontend_assets();
        }
    }

    public function enqueue_admin($hook): void {
        $core = WPComments_Core::get_instance();
        if ($core) {
            $core->enqueue_sticky_admin_assets($hook);
        }
    }
}
