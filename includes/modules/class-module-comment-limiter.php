<?php
/**
 * Comment Limiter Module
 */

namespace WPComments\Modules;

use WPComments\WPComments_Module_Interface;
use WPComments\WPComments_Core;

if (!defined('ABSPATH')) {
    exit;
}

class Comment_Limiter_Module implements WPComments_Module_Interface {
    public function id(): string { return 'comment_limiter'; }

    public function is_enabled(array $settings): bool {
        return !empty($settings['comment_limiter_enabled']);
    }

    public function register(): void {
        $core = WPComments_Core::get_instance();
        if ($core) {
            $core->register_comment_limiter_module();
        }
    }

    public function enqueue_frontend(): void {
        $core = WPComments_Core::get_instance();
        if ($core) {
            $core->enqueue_limiter_scripts();
        }
    }
}
