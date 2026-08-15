<?php
/**
 * Destructive report-table migration integration test.
 *
 * This file refuses to run unless explicitly enabled inside a disposable test
 * database whose name begins with wpcomments_test_.
 */

if (!defined('ABSPATH')) {
    fwrite(STDERR, "FAIL: WordPress was not loaded\n");
    exit(1);
}

$allowed = '1' === getenv('WPCOMMENTS_DESTRUCTIVE_TESTS')
    && 0 === strpos((string) DB_NAME, 'wpcomments_test_');
if (!$allowed) {
    fwrite(STDERR, "SKIP: destructive migration fixture requires an explicit disposable test database\n");
    exit(2);
}

function wpcomments_test_fail($message) {
    fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
    exit(1);
}

function wpcomments_test_seed_legacy_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'wpcomments_reports';
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
    $created = $wpdb->query(
        "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comment_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user_ip varchar(64) NOT NULL DEFAULT '',
            report_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY comment_id (comment_id),
            KEY user_id (user_id)
        ) " . $wpdb->get_charset_collate()
    );
    if (false === $created) {
        wpcomments_test_fail('could not create legacy table: ' . $wpdb->last_error);
    }
    $wpdb->insert($table, array(
        'comment_id' => 11,
        'user_id' => 0,
        'user_ip' => 'legacy-hash',
        'report_time' => '2025-01-02 03:04:05',
    ));
    $wpdb->insert($table, array(
        'comment_id' => 12,
        'user_id' => 0,
        'user_ip' => '',
        'report_time' => '0000-00-00 00:00:00',
    ));
    add_comment_meta(12, 'wpcomments_subscribe_to_comment', 'off');
    delete_option('wpcomments_db_version');
    return $table;
}

function wpcomments_test_assert_migrated($table) {
    global $wpdb;
    $columns = $wpdb->get_col("SHOW COLUMNS FROM {$table}", 0);
    $indexes = $wpdb->get_col("SHOW INDEX FROM {$table}", 2);
    foreach (array('user_ip_hash', 'reason', 'report_date') as $column) {
        if (!in_array($column, $columns, true)) {
            wpcomments_test_fail('missing migrated column ' . $column);
        }
    }
    foreach (array('user_ip_hash', 'report_date', 'comment_ip', 'ip_date') as $index) {
        if (!in_array($index, $indexes, true)) {
            wpcomments_test_fail('missing migrated index ' . $index);
        }
    }
    $date = $wpdb->get_var($wpdb->prepare("SELECT report_date FROM {$table} WHERE comment_id = %d", 11));
    if ('2025-01-02 03:04:05' !== $date) {
        wpcomments_test_fail('legacy report_time was not migrated');
    }
    if (WPCOMMENTS_DB_VERSION !== (string) get_option('wpcomments_db_version')) {
        wpcomments_test_fail('database version was not advanced after verified migration');
    }
    if (metadata_exists('comment', 12, 'wpcomments_subscribe_to_comment')) {
        wpcomments_test_fail('legacy negative subscription metadata was not removed');
    }
}

function wpcomments_test_run_current_site() {
    global $wpdb;
    $table = wpcomments_test_seed_legacy_table();
    if (!\WPComments\wpcomments_create_reports_table()) {
        wpcomments_test_fail('legacy migration returned failure');
    }
    wpcomments_test_assert_migrated($table);

    $before_schema = $wpdb->get_var("SHOW CREATE TABLE {$table}", 1);
    $before_rows = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    if (!\WPComments\wpcomments_create_reports_table()) {
        wpcomments_test_fail('idempotent migration returned failure');
    }
    $after_schema = $wpdb->get_var("SHOW CREATE TABLE {$table}", 1);
    $after_rows = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    if ($before_schema !== $after_schema || $before_rows !== $after_rows) {
        wpcomments_test_fail('repeated migration changed schema or row count');
    }

    update_option('wpcomments_db_version', '1', false);
    $force_failure = static function () { return false; };
    add_filter('wpcomments_database_schema_valid', $force_failure);
    $result = \WPComments\wpcomments_create_reports_table();
    remove_filter('wpcomments_database_schema_valid', $force_failure);
    if (false !== $result || '1' !== (string) get_option('wpcomments_db_version')) {
        wpcomments_test_fail('failed migration advanced the database version');
    }
    if (!\WPComments\wpcomments_create_reports_table()) {
        wpcomments_test_fail('migration was not retryable after a guarded failure');
    }

    return array('table' => $table, 'rows' => $after_rows);
}

if (is_multisite()) {
    $site_ids = get_sites(array('fields' => 'ids', 'number' => 2));
    if (count($site_ids) < 2) {
        wpcomments_test_fail('multisite fixture requires two sites');
    }
    $results = array();
    foreach ($site_ids as $site_id) {
        switch_to_blog((int) $site_id);
        $results[] = wpcomments_test_run_current_site();
        restore_current_blog();
    }
    echo 'PASS: multisite legacy migration, idempotency, guarded failure, and retry; sites=' . count($results) . PHP_EOL;
    exit(0);
}

$result = wpcomments_test_run_current_site();
echo 'PASS: single-site legacy migration, idempotency, guarded failure, and retry; rows=' . $result['rows'] . PHP_EOL;
