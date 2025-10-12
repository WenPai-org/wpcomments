<?php
/**
 * Email Notification Module
 */

namespace WPComments\Modules;

use WPComments\WPComments_Module_Interface;

if (!defined('ABSPATH')) {
    exit;
}

class Email_Notification_Module implements WPComments_Module_Interface {
    public function id(): string { return 'email_notification'; }

    public function is_enabled(array $settings): bool {
        return !empty($settings['email_notification_enabled']);
    }

    public function register(): void {
        if (class_exists('WPComments\\WPComments_Email_Notification')) {
            new \WPComments\WPComments_Email_Notification();
        }
    }
}