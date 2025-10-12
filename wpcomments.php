<?php
/**
 * Plugin Name:  WPComments
 * Description:  WPComments everywhere in WordPress.
 * Version:      2.0.0
 * Plugin URI:   https://wenpai.org/plugins/wpcomments/
 * Author:       WPComments.com
 * Author URI:   https://wpcomments.com
 * Text Domain:  wpcomments
 * Requires PHP: 7.4
 * License:      GPL v2 or later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Network:      true
 *
 * @package WPComments
 */

namespace WPComments;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

// Define constants.
define( 'WPCOMMENTS_VERSION', '2.0.0' );
define( 'WPCOMMENTS_PLUGIN_FILE', __FILE__ );
define( 'WPCOMMENTS_URL', plugin_dir_url( __FILE__ ) );
define( 'WPCOMMENTS_PATH', plugin_dir_path( __FILE__ ) );

$network_activated = is_network_wide( __FILE__ );

if ( ! defined( 'WPCOMMENTS_IS_NETWORK' ) ) {
	define( 'WPCOMMENTS_IS_NETWORK', $network_activated );
}

// Initialize plugin.
add_action( 'plugins_loaded', __NAMESPACE__ . '\init' );
add_action( 'plugins_loaded', __NAMESPACE__ . '\load_textdomain' );
add_action( 'admin_init', __NAMESPACE__ . '\register_general_settings_field' );
add_action( 'wpcomments_reinit_features', __NAMESPACE__ . '\reinit_features' );

// Activation: create reports table and schedule cleanup
register_activation_hook( WPCOMMENTS_PLUGIN_FILE, __NAMESPACE__ . '\activate_wpcomments' );
add_action( 'wpcomments_cleanup_reports', __NAMESPACE__ . '\cleanup_old_reports' );

// Load core functionality
require_once WPCOMMENTS_PATH . 'includes/class-wpcomments-core.php';
require_once WPCOMMENTS_PATH . 'includes/class-wpcomments-settings.php';
require_once WPCOMMENTS_PATH . 'includes/class-wpcomments-network-settings.php';
require_once WPCOMMENTS_PATH . 'includes/class-wpcomments-modules.php';
require_once WPCOMMENTS_PATH . 'includes/modules/class-module-frequently-replies.php';
require_once WPCOMMENTS_PATH . 'includes/modules/class-module-report.php';
require_once WPCOMMENTS_PATH . 'includes/modules/class-module-sticky.php';
require_once WPCOMMENTS_PATH . 'includes/modules/class-module-moderation-info.php';
require_once WPCOMMENTS_PATH . 'includes/modules/class-module-email-notification.php';
// Additional feature modules
require_once WPCOMMENTS_PATH . 'includes/modules/class-module-role-badge.php';
require_once WPCOMMENTS_PATH . 'includes/modules/class-module-validation.php';
require_once WPCOMMENTS_PATH . 'includes/modules/class-module-private-comments.php';
require_once WPCOMMENTS_PATH . 'includes/modules/class-module-remove-website-field.php';
require_once WPCOMMENTS_PATH . 'includes/modules/class-module-comment-limiter.php';
require_once WPCOMMENTS_PATH . 'includes/modules/class-module-delete-pending.php';


// 保留必要的功能类
$includes = array(
	'class-wpcomments-email-notification.php',
	'class-wpcomments-frequently-replies.php',
	'class-wpcomments-moderation-info.php',
);

foreach ( $includes as $include ) {
	$file_path = WPCOMMENTS_PATH . 'includes/' . $include;
	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
}

/**
 * Get feature settings based on network or site configuration.
 *
 * @since 2.0.0
 * @return array Feature settings array.
 */
function get_feature_settings(): array {
	$defaults = array(
		'comments_disabled'             => false,
		'role_badge_enabled'            => true,
		'delete_pending_enabled'        => true,
		'remove_website_field_enabled'  => false,
		'frequently_replies_enabled'    => true,
		'validation_enabled'            => true,
		'moderation_info_enabled'       => true,
		'email_notification_enabled'    => true,
		'private_comments_enabled'      => true,
		'comment_limiter_enabled'       => true,
		'report_comments_enabled'       => false,
		'sticky_comments_enabled'       => true,
	);

if ( is_multisite() ) {
		return array(
			'comments_disabled'             => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_disable_comments', $defaults['comments_disabled'] ),
			'role_badge_enabled'            => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_role_badge', $defaults['role_badge_enabled'] ),
			'delete_pending_enabled'        => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_delete_pending', $defaults['delete_pending_enabled'] ),
			'remove_website_field_enabled'  => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_remove_website_field', $defaults['remove_website_field_enabled'] ),
			'frequently_replies_enabled'    => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_frequently_replies', $defaults['frequently_replies_enabled'] ),
			'validation_enabled'            => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_validation', $defaults['validation_enabled'] ),
			'moderation_info_enabled'       => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_moderation_info', $defaults['moderation_info_enabled'] ),
			'email_notification_enabled'    => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_email_notification', $defaults['email_notification_enabled'] ),
			'private_comments_enabled'      => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_private_comments', $defaults['private_comments_enabled'] ),
			'comment_limiter_enabled'       => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_comment_limiter', $defaults['comment_limiter_enabled'] ),
			'report_comments_enabled'       => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_report_comments', $defaults['report_comments_enabled'] ),
			'sticky_comments_enabled'       => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_sticky_comments', $defaults['sticky_comments_enabled'] ),
		);
	}

	return array(
		'comments_disabled'             => get_option( 'wpcomments_disable_comments', $defaults['comments_disabled'] ),
		'role_badge_enabled'            => get_option( 'wpcomments_enable_role_badge', $defaults['role_badge_enabled'] ),
		'delete_pending_enabled'        => get_option( 'wpcomments_enable_delete_pending', $defaults['delete_pending_enabled'] ),
		'remove_website_field_enabled'  => get_option( 'wpcomments_enable_remove_website_field', $defaults['remove_website_field_enabled'] ),
		'frequently_replies_enabled'    => get_option( 'wpcomments_enable_frequently_replies', $defaults['frequently_replies_enabled'] ),
		'validation_enabled'            => get_option( 'wpcomments_enable_validation', $defaults['validation_enabled'] ),
		'moderation_info_enabled'       => get_option( 'wpcomments_enable_moderation_info', $defaults['moderation_info_enabled'] ),
		'email_notification_enabled'    => get_option( 'wpcomments_enable_email_notification', $defaults['email_notification_enabled'] ),
		'private_comments_enabled'      => get_option( 'wpcomments_enable_private_comments', $defaults['private_comments_enabled'] ),
		'comment_limiter_enabled'       => get_option( 'wpcomments_enable_comment_limiter', $defaults['comment_limiter_enabled'] ),
		'report_comments_enabled'       => get_option( 'wpcomments_enable_report_comments', $defaults['report_comments_enabled'] ),
		'sticky_comments_enabled'       => get_option( 'wpcomments_enable_sticky_comments', $defaults['sticky_comments_enabled'] ),
	);
}

/**
 * Bootstrap the plugin.
 *
 * @since 2.0.0
 */
function init(): void {
	// 计算有效设置：用于决定当前运行模式
	$feature_settings = get_feature_settings();
	$comments_disabled = ! empty( $feature_settings['comments_disabled'] );

	if ( $comments_disabled ) {
		// 模式一：禁用模式，仅加载禁用评论逻辑
		setup();
	} else {
		// 模式二：增强模式，按需加载核心与模块
		new \WPComments\WPComments_Core();

		// 模块管理器：注册需要的模块并启动
		$manager = new \WPComments\WPComments_Module_Manager();
$manager
			->register_module( new \WPComments\Modules\Frequently_Replies_Module() )
			->register_module( new \WPComments\Modules\Report_Module() )
			->register_module( new \WPComments\Modules\Sticky_Module() )
			->register_module( new \WPComments\Modules\Moderation_Info_Module() )
			->register_module( new \WPComments\Modules\Email_Notification_Module() )
			->register_module( new \WPComments\Modules\Role_Badge_Module() )
			->register_module( new \WPComments\Modules\Validation_Module() )
			->register_module( new \WPComments\Modules\Private_Comments_Module() )
			->register_module( new \WPComments\Modules\Comment_Limiter_Module() )
			->register_module( new \WPComments\Modules\Delete_Pending_Module() )
			->register_module( new \WPComments\Modules\Remove_Website_Field_Module() );
		$manager->boot( $feature_settings );
	}

	// 管理界面
	if ( is_admin() ) {
new \WPComments\WPComments_Settings();
	}

	if ( is_multisite() && is_network_admin() ) {
new \WPComments\WPComments_Network_Settings();
	}
}

/**
 * Disable comments and trackbacks.
 *
 * @since 1.0.0
 */
function setup(): void {
	// Disable comments.
	add_filter( 'comments_open', '__return_false' );

	// And pings are a form of comments.
	add_filter( 'pings_open', '__return_false' );

	// No content has an existing comment.
	add_filter( 'get_comments_number', '__return_zero' );

	// So return an empty set or count of comments for all comment queries.
	add_filter(
		'comments_pre_query',
		__NAMESPACE__ . '\filter_comments_pre_query',
		10,
		2
	);

	// And disable the comments feed.
	add_filter( 'feed_links_show_comments_feed', '__return_false' );

	// And remove comment rewrite rules.
	add_filter( 'comments_rewrite_rules', '__return_empty_array' );

	// Then remove comment support from everything.
	add_action( 'init', __NAMESPACE__ . '\remove_comment_support', 99 );
	add_action( 'init', __NAMESPACE__ . '\remove_trackback_support', 99 );

	// Remove comment blocks from the editor. (Twice to be sure!).
	add_action(
		'enqueue_block_editor_assets',
		__NAMESPACE__ . '\unregister_comment_blocks_javascript'
	);
	add_action( 'init', __NAMESPACE__ . '\unregister_comment_blocks', 99 );

	// And disable all comment related views in the admin.
	add_filter( 'wp_count_comments', __NAMESPACE__ . '\filter_wp_count_comments' );
	add_action(
		'add_admin_bar_menus',
		__NAMESPACE__ . '\remove_admin_bar_comments_menu'
	);
	add_action(
		'admin_bar_menu',
		__NAMESPACE__ . '\remove_my_sites_comments_menu',
		21
	);
	add_action( 'admin_menu', __NAMESPACE__ . '\remove_comments_menu_page' );
	add_action(
		'load-options-discussion.php',
		__NAMESPACE__ . '\block_comments_admin_screen'
	);
	add_action(
		'load-edit-comments.php',
		__NAMESPACE__ . '\block_comments_admin_screen'
	);

	// Disable REST API comments.
	add_filter( 'rest_endpoints', __NAMESPACE__ . '\remove_rest_api_endpoints' );
	add_filter( 'rest_pre_insert_comment', __NAMESPACE__ . '\disable_rest_api_comments', 10, 2 );

	// Disable XML-RPC comments.
	add_filter( 'xmlrpc_methods', __NAMESPACE__ . '\disable_xmlrpc_comments' );
	
	// Enhanced comment disabling features from Comment-Free-Zone
	add_action( 'init', __NAMESPACE__ . '\enhanced_comment_disabling', 99 );
	add_filter( 'comments_template', __NAMESPACE__ . '\disable_comments_template', 20 );
	add_filter( 'comments_number', '__return_empty_string' );
	add_filter( 'get_default_comment_status', __NAMESPACE__ . '\force_closed_comment_status', 999 );
	add_action( 'do_feed_rss2', __NAMESPACE__ . '\disable_comment_feeds', 1 );
	add_action( 'do_feed_rss', __NAMESPACE__ . '\disable_comment_feeds', 1 );
	add_action( 'pre_ping', __NAMESPACE__ . '\disable_outgoing_pings' );
	add_filter( 'manage_pages_columns', __NAMESPACE__ . '\remove_comments_column_from_pages' );

	// Hide dashboard comment widgets.
	add_action( 'admin_head', __NAMESPACE__ . '\hide_dashboard_comment_widgets' );
	add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\remove_dashboard_comment_widgets' );
}

/**
 * Filter the comments pre query.
 *
 * @param array<int,\WP_Comment>|int|null $comments The comments to filter.
 * @param \WP_Comment_Query               $query    The query object.
 * @return array<int,\WP_Comment>|int The filtered comments.
 */
function filter_comments_pre_query( $comments, \WP_Comment_Query $query ) {
	if ( $query->query_vars['count'] ) {
		return 0;
	}

	return array();
}

/**
 * Remove comments support from all post types that have registered
 * it by priority 99 on init.
 */
function remove_comment_support(): void {
	$post_types = get_post_types_by_support( 'comments' );

	foreach ( $post_types as $post_type ) {
		remove_post_type_support( $post_type, 'comments' );
	}
}

/**
 * Remove trackbacks support from all post types that have registered
 * it by priority 99 on init.
 */
function remove_trackback_support(): void {
	$post_types = get_post_types_by_support( 'trackbacks' );

	foreach ( $post_types as $post_type ) {
		remove_post_type_support( $post_type, 'trackbacks' );
	}
}

/**
 * Enqueue a script to remove any client-side registration of WordPress
 * core comment blocks.
 *
 * @since 1.1.0
 */
function unregister_comment_blocks_javascript(): void
{
    $asset_path = WPCOMMENTS_PATH . 'build/index.asset.php';
    $script_path = WPCOMMENTS_URL . 'build/index.js';
    if (!file_exists($asset_path)) {
        return;
    }
    $asset_data = include $asset_path;
    if (!is_array($asset_data) || empty($asset_data['dependencies']) || empty($asset_data['version'])) {
        return;
    }

    wp_enqueue_script(
        'wpcomments',
        $script_path,
        $asset_data['dependencies'],
        $asset_data['version'],
        true
    );
}

/**
 * Remove any server-side registration of WordPress core comment blocks.
 *
 * @see unregister_comment_blocks_javascript() for client-side removal.
 *
 * @since 1.1.0
 */
function unregister_comment_blocks(): void
{
    // Retrieve all registered blocks.
    $registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();

    $blocks = [
        "core/comments",
        "core/comments-query-loop", // Replaced by core/comments in Gutenberg 13.7.

        "core/comment-author-avatar",
        "core/comment-author-name",
        "core/comment-content",
        "core/comment-date",
        "core/comment-edit-link",
        "core/comment-reply-link",
        "core/comment-template",

        "core/comments-pagination",
        "core/comments-pagination-next",
        "core/comments-pagination-numbers",
        "core/comments-pagination-previous",
        "core/comments-title",

        "core/latest-comments",

        "core/post-comment",
        "core/post-comments-count",
        "core/post-comments-form",
        "core/post-comments-link",
    ];

    foreach ($blocks as $block) {
        if (isset($registered_blocks[$block])) {
            unregister_block_type($block);
            // Also filter the output to be empty as a fallback
            add_filter('render_block_' . $block, '__return_empty_string');
        }
    }
    
}

/**
 * Remove the "Comments" and Settings -> Discussion menus from the
 * side menu in the dashboard.
 */
function remove_comments_menu_page(): void
{
    remove_menu_page("edit-comments.php");
    remove_submenu_page("options-general.php", "options-discussion.php");
}

/**
 * Remove the comments menu from the admin bar.
 */
function remove_admin_bar_comments_menu(): void
{
    remove_action("admin_bar_menu", "wp_admin_bar_comments_menu", 60);
}

/**
 * Remove the "Manage Comments" node from each site's menu under My Sites.
 *
 * @since 1.3.0
 */
function remove_my_sites_comments_menu(): void
{
    global $wp_admin_bar;

    // Only parse for the menu if it's going to be there, part 1.
    if (!is_multisite() || !is_user_logged_in()) {
        return;
    }

    // Only parse for the menu if it's going to be there, part 2.
    if (count($wp_admin_bar->user->blogs) < 1) {
        return;
    }

    // The plugin API is not always available on the front-end.
    if (!function_exists("is_plugin_active_for_network")) {
        require_once ABSPATH . "/wp-admin/includes/plugin.php";
    }

    // We can't accurately remove the menu item if the plugin is not network activated.
    if (!is_plugin_active_for_network(plugin_basename(__FILE__))) {
        return;
    }

    foreach ($wp_admin_bar->user->blogs as $blog) {
        $wp_admin_bar->remove_menu("blog-" . $blog->userblog_id . "-c");
    }
}

/**
 * Filter wp_count_comments() so that it always returns 0.
 *
 * This hides Recent Comments from the dashboard activity widget.
 *
 * @return \stdClass An object with expected count properties.
 */
function filter_wp_count_comments(): \stdClass
{
    return (object) [
        "approved" => 0,
        "moderated" => 0,
        "spam" => 0,
        "trash" => 0,
        "post-trashed" => 0,
        "total_comments" => 0,
        "all" => 0,
    ];
}

/**
 * Block access to the Settings -> Discussion and Edit Comments views
 * in the admin.
 */
function block_comments_admin_screen(): void
{
    wp_die(
        esc_html__(
            "This screen is disabled by the WPComments plugin.",
            "wpcomments",
        ),
    );
}



/**
 * Is plugin activated network wide?
 */
function is_network_wide(string $plugin_file): bool
{
    if (!is_multisite()) {
        return false;
    }

    if (!function_exists('is_plugin_active_for_network')) {
        require_once ABSPATH . '/wp-admin/includes/plugin.php';
    }

    return is_plugin_active_for_network(plugin_basename($plugin_file));
}

/**
 * Remove the comments endpoint for the REST API
 */
function remove_rest_api_endpoints(array $endpoints): array
{
    // Remove all comment-related endpoints
    $comment_endpoints = [
        '/wp/v2/comments',
        '/wp/v2/comments/(?P<id>[\d]+)',
        'comments'
    ];
    
    foreach ($comment_endpoints as $endpoint) {
        if (isset($endpoints[$endpoint])) {
            unset($endpoints[$endpoint]);
        }
    }
    
    return $endpoints;
}

/**
 * Disable REST API comments
 */
function disable_rest_api_comments($prepared_comment, \WP_REST_Request $request): \WP_Error
{
    return new \WP_Error(
        'rest_comments_disabled',
        esc_html__('Comments are closed.', 'wpcomments'),
        ['status' => 403]
     );
 }

/**
 * Disable XML-RPC comments and pingbacks
 */
function disable_xmlrpc_comments(array $methods): array
{
    // Remove comment-related XML-RPC methods
    unset($methods['wp.newComment']);
    unset($methods['pingback.ping']);
    
    return $methods;
}

/**
 * Hide dashboard comment widgets with CSS
 */
function hide_dashboard_comment_widgets(): void
{
    echo '<style>
        #dashboard_recent_comments,
        .postbox-container .postbox#dashboard_recent_comments,
        #welcome-panel .welcome-comments,
        .activity-block.comments-block {
            display: none !important;
        }
    </style>';
}

/**
 * Remove dashboard comment widgets
 */
function remove_dashboard_comment_widgets(): void
{
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
}

/**
 * Enhanced comment disabling features
 */
function enhanced_comment_disabling(): void
{
    // Clean up REST API schema for all post types
    $post_types = get_post_types();
    foreach ($post_types as $post_type) {
        add_filter("rest_{$post_type}_item_schema", __NAMESPACE__ . '\cleanup_rest_api_schema');
        add_filter('rest_prepare_' . $post_type, __NAMESPACE__ . '\cleanup_rest_prepare_post_type');
    }
}

/**
 * Remove comment_status and ping_status from REST API schema
 */
function cleanup_rest_api_schema($schema): array
{
    unset($schema['properties']['comment_status']);
    unset($schema['properties']['ping_status']);
    return $schema;
}

/**
 * Remove replies link from REST API response
 */
function cleanup_rest_prepare_post_type($response)
{
    $response->remove_link('replies');
    return $response;
}

/**
 * Disable comments template
 */
function disable_comments_template(): string
{
    return WPCOMMENTS_PATH . 'templates/blank-comments.php';
}

/**
 * Force closed comment status for new posts
 */
function force_closed_comment_status(): string
{
    return 'closed';
}

/**
 * Disable comment feeds
 */
function disable_comment_feeds(): void
{
    if (isset($_SERVER['REQUEST_URI']) && strpos(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])), 'comments') !== false) {
        wp_die(
            esc_html__('Comments are disabled.', 'wpcomments'),
            esc_html__('Comments Disabled', 'wpcomments'),
            ['response' => 403]
        );
    }
}

/**
 * Disable outgoing pings
 */
function disable_outgoing_pings(&$links): void
{
    $links = [];
}

/**
 * Remove comments column from pages list
 */
function remove_comments_column_from_pages($columns): array
{
    unset($columns['comments']);
    return $columns;
}

// 这些功能已合并到 WPComments_Core 类中

/**
 * Load plugin textdomain
 */
function load_textdomain(): void
{
    load_plugin_textdomain(
        'wpcomments',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}

/**
 * Register general settings field for comments
 */
function register_general_settings_field(): void
{
    register_setting('general', 'wpcomments_disable_comments', 'boolval');

    add_settings_field(
        'wpcomments-disable-comments',
        __('禁用评论', 'wpcomments'),
        __NAMESPACE__ . '\render_general_settings_field',
        'general'
    );
}

/**
 * Render general settings field
 */
function render_general_settings_field(): void
{
    $network_disabled = false;
    $disabled_attr = '';
    $description = __('启用后将完全禁用WordPress的评论系统，包括前台显示和后台管理。', 'wpcomments');
    
    if (is_multisite()) {
        $network_disabled = get_site_option('wpcomments_disable_comments', false);
        if ($network_disabled) {
            $disabled_attr = 'disabled="disabled"';
            $description = __('评论功能已在网络级别被禁用，无法在此站点单独启用。', 'wpcomments');
        }
    }
    
    $value = get_option('wpcomments_disable_comments', false);
    if ($network_disabled) {
        $value = true;
    }
    ?>
    <fieldset>
        <legend class="screen-reader-text">
            <span><?php echo esc_html__('禁用评论', 'wpcomments'); ?></span>
        </legend>
        <label for="wpcomments_disable_comments">
            <input <?php checked(true, $value); ?> name="wpcomments_disable_comments" type="checkbox" id="wpcomments_disable_comments" value="1" <?php echo $disabled_attr; ?>>
            <?php echo esc_html__('完全禁用此站点的评论功能', 'wpcomments'); ?>
        </label>
        <p class="description"><?php echo esc_html($description); ?></p>
        <?php if ($network_disabled): ?>
        <p class="description"><?php echo esc_html__('此设置由网络管理员控制。', 'wpcomments'); ?></p>
        <?php endif; ?>
    </fieldset>
    <?php
}








/**
 * Reinitialize features after settings change
 */
function reinit_features(): void
{
    // 触发可选的重新初始化钩子，避免重复挂载或全局清理
    do_action('wpcomments_reinit', get_feature_settings());
}

/**
 * Activation callback: create reports table and schedule cleanup
 */
function activate_wpcomments( $network_wide ) : void {
    if ( is_multisite() && $network_wide ) {
        $sites = get_sites( array( 'fields' => 'ids' ) );
        foreach ( $sites as $site_id ) {
            switch_to_blog( (int) $site_id );
            wpcomments_create_reports_table();
            wpcomments_schedule_cleanup();
            restore_current_blog();
        }
    } else {
        wpcomments_create_reports_table();
        wpcomments_schedule_cleanup();
    }
}

/**
 * Create reports table (hashed IP)
 */
function wpcomments_create_reports_table() : void {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpcomments_reports';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        comment_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned NULL,
        user_ip varchar(45) NOT NULL,
        report_time datetime NOT NULL,
        PRIMARY KEY (id),
        KEY comment_id (comment_id),
        KEY user_id (user_id),
        KEY user_ip (user_ip)
    ) {$charset_collate};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

/**
 * Schedule daily cleanup for old reports
 */
function wpcomments_schedule_cleanup() : void {
    if ( ! wp_next_scheduled( 'wpcomments_cleanup_reports' ) ) {
        wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'wpcomments_cleanup_reports' );
    }
}

/**
 * Cleanup old reports older than retention days (default 90)
 */
function cleanup_old_reports() : void {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpcomments_reports';
    $days = (int) (class_exists('WPComments\\WPComments_Network_Settings') ? \WPComments\WPComments_Network_Settings::get_effective_setting('wpcomments_report_retention_days', apply_filters('wpcomments_report_retention_days', 90)) : get_option('wpcomments_report_retention_days', apply_filters('wpcomments_report_retention_days', 90)));
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE report_time < ( NOW() - INTERVAL %d DAY )", $days ) );
}
