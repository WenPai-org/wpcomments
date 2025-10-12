<?php
/**
 * Private Comments Module
 */

namespace WPComments\Modules;

use WPComments\WPComments_Module_Interface;
use WPComments\WPComments_Core;

if (!defined('ABSPATH')) {
    exit;
}

class Private_Comments_Module implements WPComments_Module_Interface {
    public function id(): string { return 'private_comments'; }

    public function is_enabled(array $settings): bool {
        return !empty($settings['private_comments_enabled']);
    }

    public function register(): void {
        $core = WPComments_Core::get_instance();
        if ($core) {
            $core->register_private_comments_module();
        }
    }

    // 前端样式由模块负责
    public function enqueue_frontend(): void {
        wp_add_inline_style('wp-block-library', '\n            .wpcomments-private-notice { color: #d63638; font-weight: bold; }\n            .wpcomments-private-content { border-left: 3px solid #d63638; padding-left: 10px; }\n            .comment-form-private-comment { margin-top: 10px; }\n            .comment-form-private-comment input[type=\"checkbox\"] { margin-right: 5px; }\n            .wpcomments-disabled-action { color: #999; cursor: not-allowed; text-decoration: none; }\n        ');
    }
}
