<?php
/**
 * Moderation Info Module
 */

namespace WPComments\Modules;

use WPComments\WPComments_Module_Interface;

if (!defined('ABSPATH')) {
    exit;
}

class Moderation_Info_Module implements WPComments_Module_Interface {
    public function id(): string { return 'moderation_info'; }

    public function is_enabled(array $settings): bool {
        return !empty($settings['moderation_info_enabled']);
    }

    public function register(): void {
        if (class_exists('WPComments\\WPComments_Moderation_Info')) {
            \WPComments\WPComments_Moderation_Info::get_instance();
        }
    }
}