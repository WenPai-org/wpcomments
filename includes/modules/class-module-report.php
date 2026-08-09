<?php
/**
 * Legacy Report module wrapper.
 *
 * @deprecated 2.0.1 Use \WPComments\Comment_Reporting_Module directly.
 */

namespace WPComments\Modules;

use WPComments\WPComments_Module_Interface;

if (!defined('ABSPATH')) {
    exit;
}

class Report_Module implements WPComments_Module_Interface {
    public function id(): string {
        return 'report';
    }

    public function is_enabled(array $settings): bool {
        return !empty($settings['report_comments_enabled']);
    }

    public function register(): void {
        if (class_exists('WPComments\\Comment_Reporting_Module')) {
            $module = new \WPComments\Comment_Reporting_Module();
            $module->register();
        }
    }
}
