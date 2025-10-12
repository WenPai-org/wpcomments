<?php
/**
 * Delete Pending Comments Module
 */

namespace WPComments\Modules;

use WPComments\WPComments_Module_Interface;
use WPComments\WPComments_Core;

if (!defined('ABSPATH')) {
    exit;
}

class Delete_Pending_Module implements WPComments_Module_Interface {
    public function id(): string { return 'delete_pending'; }

    public function is_enabled(array $settings): bool {
        return !empty($settings['delete_pending_enabled']);
    }

    public function register(): void {
        $core = WPComments_Core::get_instance();
        if ($core) {
            $core->register_delete_pending_module();
        }
    }
}
