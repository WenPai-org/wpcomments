<?php
/**
 * Email integration smoke check. Run after enabling the email module.
 */

if (!defined('ABSPATH')) {
    fwrite(STDERR, "FAIL: WordPress was not loaded\n");
    exit(1);
}

update_option('wpcomments_email_admin_notification', 1);
update_option('wpcomments_email_admin_email', 'audit-admin@example.invalid');

$mail = array();
add_filter('pre_wp_mail', static function ($return, $atts) use (&$mail) {
    $mail[] = $atts;
    return true;
}, 10, 2);

$post_id = wp_insert_post(array(
    'post_title' => 'Email smoke test',
    'post_content' => 'Email smoke test',
    'post_status' => 'publish',
    'comment_status' => 'open',
));
$pending_id = wp_insert_comment(array(
    'comment_post_ID' => $post_id,
    'comment_author' => 'Email Tester',
    'comment_author_email' => 'tester@example.invalid',
    'comment_content' => 'Pending email integration smoke comment',
    'comment_approved' => 0,
));

$admin_messages = array();
foreach ($mail as $message) {
    $recipients = (array) ($message['to'] ?? array());
    if (in_array('audit-admin@example.invalid', $recipients, true)) {
        $admin_messages[] = $message;
    }
}

if (!$pending_id || count($admin_messages) !== 1) {
    fwrite(STDERR, 'FAIL: pending comment did not produce exactly one administrator notification; admin=' . count($admin_messages) . '; all=' . count($mail) . PHP_EOL);
    exit(1);
}

$pending_body = (string) ($admin_messages[0]['message'] ?? '');
if (strpos($pending_body, 'action=approvecomment') === false || strpos($pending_body, '_wpnonce=') === false || strpos($pending_body, 'href="#"') !== false) {
    fwrite(STDERR, 'FAIL: pending administrator notification has invalid moderation links' . PHP_EOL);
    exit(1);
}

wp_set_comment_status($pending_id, 'approve');

$approved_id = wp_insert_comment(array(
    'comment_post_ID' => $post_id,
    'comment_author' => 'Approved Email Tester',
    'comment_author_email' => 'approved@example.invalid',
    'comment_content' => 'Approved email integration smoke comment',
    'comment_approved' => 1,
));

$admin_messages = array_values(array_filter($mail, static function ($message) {
    return in_array('audit-admin@example.invalid', (array) ($message['to'] ?? array()), true);
}));

if (!$approved_id || count($admin_messages) !== 2) {
    fwrite(STDERR, 'FAIL: approval generated a duplicate mail or approved comment mail was missing' . PHP_EOL);
    exit(1);
}

$approved_body = (string) ($admin_messages[1]['message'] ?? '');
if (strpos($approved_body, 'action=approvecomment') !== false || strpos($approved_body, 'action=spamcomment') === false) {
    fwrite(STDERR, 'FAIL: approved administrator notification has status-inappropriate actions' . PHP_EOL);
    exit(1);
}

echo 'PASS: pending and approved administrator notifications intercepted; messages=' . count($admin_messages) . PHP_EOL;
