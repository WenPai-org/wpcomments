<?php
/**
 * Static-analysis constants normally defined by WordPress and the plugin entrypoint.
 *
 * @package WPComments
 */

if (!defined('WPCOMMENTS_PATH')) {
    define('WPCOMMENTS_PATH', dirname(__DIR__) . '/');
}
if (!defined('WPCOMMENTS_URL')) {
    define('WPCOMMENTS_URL', 'https://example.test/wp-content/plugins/wpcomments/');
}
if (!defined('WPCOMMENTS_VERSION')) {
    define('WPCOMMENTS_VERSION', '2.0.0');
}
if (!defined('WPCOMMENTS_IS_NETWORK')) {
    define('WPCOMMENTS_IS_NETWORK', (bool) getenv('WPCOMMENTS_STATIC_NETWORK'));
}
if (!defined('WPCOMMENTS_DB_VERSION')) {
    define('WPCOMMENTS_DB_VERSION', 2);
}
if (!defined('NONCE_SALT')) {
    define('NONCE_SALT', 'static-analysis-only');
}
