<?php
/**
 * Report Module
 */

namespace WPComments\Modules;

use WPComments\WPComments_Module_Interface;
use WPComments\WPComments_Core;

if (!defined('ABSPATH')) {
    exit;
}

class Report_Module implements WPComments_Module_Interface {
    public function id(): string { return 'report'; }

    public function is_enabled(array $settings): bool {
        return !empty($settings['report_comments_enabled']);
    }

    public function register(): void {
        $core = WPComments_Core::get_instance();
        if ($core) {
            $core->register_report_module();
        }
    }

    // Centralized enqueue via Module Manager
    public function enqueue_frontend(): void {
        $core = WPComments_Core::get_instance();
        if ($core) {
            $core->enqueue_report_scripts();
        }
        // 前端样式仅限举报功能相关
        wp_add_inline_style('wp-block-library', '\n            .wpcomments-report-link { margin-left: 10px; }\n            .wpcomments-report-btn { color: #666; text-decoration: none; font-size: 12px; }\n            .wpcomments-report-btn:hover { color: #d63638; text-decoration: underline; }\n            .wpcomments-already-reported { color: #999; font-size: 12px; font-style: italic; }\n        ');
    }
}
