<?php
/**
 * Complete-disable mode smoke check. Run after setting wpcomments_disable_comments=1.
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

$check(false === comments_open(), 'comments_open was not forced closed');
$check(false === pings_open(), 'pings_open was not forced closed');
$check(0 === (int) get_comments(array('count' => true)), 'comment count was not forced to zero');
$check(array() === get_comments(), 'comment query was not forced empty');

rest_get_server();
do_action('rest_api_init');
$routes = rest_get_server()->get_routes();
$check(!isset($routes['/wp/v2/comments']), 'REST comment collection route remains registered');
$check(!isset($routes['/wp/v2/comments/(?P<id>[\\d]+)']), 'REST comment item route remains registered');

$methods = apply_filters('xmlrpc_methods', array(
    'wp.newComment' => 'new_comment',
    'pingback.ping' => 'pingback',
    'wp.getPosts' => 'get_posts',
));
$check(!isset($methods['wp.newComment']), 'XML-RPC wp.newComment remains registered');
$check(!isset($methods['pingback.ping']), 'XML-RPC pingback.ping remains registered');
$check(isset($methods['wp.getPosts']), 'unrelated XML-RPC method was removed');

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }
    exit(1);
}

echo "PASS: complete-disable mode, REST, and XML-RPC smoke\n";
