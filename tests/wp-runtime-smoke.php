<?php
/**
 * WordPress runtime smoke checks. Run with: wp eval-file tests/wp-runtime-smoke.php
 */

if (!defined('ABSPATH')) {
    fwrite(STDERR, "FAIL: WordPress was not loaded\n");
    exit(1);
}

$failures = array();
$check = static function ($condition, $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$check(defined('WPCOMMENTS_VERSION'), 'plugin constants were not loaded');
$check(class_exists('WPComments\\WPComments_Bootstrap'), 'bootstrap class was not loaded');

global $wpdb;
$table = $wpdb->prefix . 'wpcomments_reports';
$columns = $wpdb->get_col("SHOW COLUMNS FROM {$table}", 0);
foreach (array('id', 'comment_id', 'user_id', 'user_ip', 'user_ip_hash', 'reason', 'report_time', 'report_date') as $column) {
    $check(in_array($column, $columns, true), "reports table is missing {$column}");
}
$indexes = $wpdb->get_col("SHOW INDEX FROM {$table}", 2);
foreach (array('comment_ip', 'ip_date') as $index) {
    $check(in_array($index, $indexes, true), "reports table is missing index {$index}");
}
$check((string) get_option('wpcomments_db_version') === (string) WPCOMMENTS_DB_VERSION, 'database version is stale');
$check((bool) wp_next_scheduled('wpcomments_cleanup_reports'), 'cleanup schedule is missing');

if (class_exists('WPComments\\WPComments_Module_Manager')) {
    $loaded = array_keys(\WPComments\WPComments_Module_Manager::get_instance()->get_loaded_modules());
    sort($loaded);
    $expected_map = array(
        'delete_pending' => 'wpcomments_enable_delete_pending',
        'remove_website_field' => 'wpcomments_enable_remove_website_field',
        'validation' => 'wpcomments_enable_validation',
        'private_comments' => 'wpcomments_enable_private_comments',
        'character_limiter' => 'wpcomments_enable_comment_limiter',
        'comment_reporting' => 'wpcomments_enable_report_comments',
        'sticky_comments' => 'wpcomments_enable_sticky_comments',
        'role_badge' => 'wpcomments_enable_role_badge',
        'gongan_beian' => 'wpcomments_enable_gongan_beian',
    );
    $expected = array();
    foreach ($expected_map as $module => $option) {
        if (\WPComments\WPComments_Network_Settings::get_effective_setting($option, false)) {
            $expected[] = $module;
        }
    }
    sort($expected);
    $check($loaded === $expected, 'loaded modules do not match effective options: loaded=' . implode(',', $loaded) . ' expected=' . implode(',', $expected));
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }
    exit(1);
}

echo 'PASS: WordPress runtime smoke; WordPress=' . get_bloginfo('version') . '; PHP=' . PHP_VERSION . "\n";
