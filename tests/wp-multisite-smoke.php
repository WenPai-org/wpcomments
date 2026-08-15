<?php
/**
 * Multisite storage and effective-setting smoke check.
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

$check(is_multisite(), 'WordPress is not in multisite mode');
$site_ids = get_sites(array('fields' => 'ids'));
$check(count($site_ids) >= 2, 'no child site exists');

global $wpdb;
foreach ($site_ids as $site_id) {
    switch_to_blog((int) $site_id);
    $table = $wpdb->prefix . 'wpcomments_reports';
    $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    $check($exists === $table, "reports table missing for site {$site_id}");
    $check((bool) wp_next_scheduled('wpcomments_cleanup_reports'), "cleanup schedule missing for site {$site_id}");
    restore_current_blog();
}

update_site_option('wpcomments_network_enabled', 1);
update_site_option('wpcomments_network_enforce', 0);
update_site_option('wpcomments_network_controlled_options', array('wpcomments_disable_comments'));
update_site_option('wpcomments_disable_comments', 1);

$child_id = (int) end($site_ids);
switch_to_blog($child_id);
update_option('wpcomments_disable_comments', 0);
$effective = \WPComments\WPComments_Network_Settings::get_effective_setting('wpcomments_disable_comments', false);
restore_current_blog();
$check(true === (bool) $effective, 'network-controlled setting did not override child-site value');

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }
    exit(1);
}

echo 'PASS: multisite storage and network override smoke; sites=' . count($site_ids) . PHP_EOL;
