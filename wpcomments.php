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

// Load includes.
require_once WPCOMMENTS_PATH . 'includes/class-comment-author-role-badge.php';

$includes = array(
	'class-delete-pending-comments.php',
	'class-comments-sticky-moderate.php',
	'class-wpcomments-settings.php',
	'class-wpcomments-email-notification.php',
	'class-wpcomments-network-settings.php',
	'class-wpcomments-remove-feed-link.php',
	'class-wpcomments-remove-website-field.php',
	'class-wpcomments-frequently-replies.php',
	'class-wpcomments-validation.php',
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
		'herpderp_enabled'              => false,
		'role_badge_enabled'            => true,
		'delete_pending_enabled'        => true,
		'sticky_moderate_enabled'       => true,
		'remove_feed_link_enabled'      => false,
		'remove_website_field_enabled'  => false,
		'frequently_replies_enabled'    => true,
		'validation_enabled'            => true,
		'moderation_info_enabled'       => true,
		'email_notification_enabled'    => true,
	);

	if ( WPCOMMENTS_IS_NETWORK && is_multisite() && class_exists( '\WPComments\WPComments_Network_Settings' ) ) {
		return array(
			'comments_disabled'             => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_network_disable_comments', $defaults['comments_disabled'] ),
			'herpderp_enabled'              => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_herpderp', $defaults['herpderp_enabled'] ),
			'role_badge_enabled'            => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_role_badge', $defaults['role_badge_enabled'] ),
			'delete_pending_enabled'        => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_delete_pending', $defaults['delete_pending_enabled'] ),
			'sticky_moderate_enabled'       => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_sticky_moderate', $defaults['sticky_moderate_enabled'] ),
			'remove_feed_link_enabled'      => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_remove_feed_link', $defaults['remove_feed_link_enabled'] ),
			'remove_website_field_enabled'  => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_remove_website_field', $defaults['remove_website_field_enabled'] ),
			'frequently_replies_enabled'    => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_frequently_replies', $defaults['frequently_replies_enabled'] ),
			'validation_enabled'            => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_validation', $defaults['validation_enabled'] ),
			'moderation_info_enabled'       => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_moderation_info', $defaults['moderation_info_enabled'] ),
			'email_notification_enabled'    => \WPComments\WPComments_Network_Settings::get_effective_setting( 'wpcomments_enable_email_notification', $defaults['email_notification_enabled'] ),
		);
	}

	return array(
		'comments_disabled'             => get_option( 'wpcomments_disable_comments', $defaults['comments_disabled'] ),
		'herpderp_enabled'              => get_option( 'wpcomments_enable_herpderp', $defaults['herpderp_enabled'] ),
		'role_badge_enabled'            => get_option( 'wpcomments_enable_role_badge', $defaults['role_badge_enabled'] ),
		'delete_pending_enabled'        => get_option( 'wpcomments_enable_delete_pending', $defaults['delete_pending_enabled'] ),
		'sticky_moderate_enabled'       => get_option( 'wpcomments_enable_sticky_moderate', $defaults['sticky_moderate_enabled'] ),
		'remove_feed_link_enabled'      => get_option( 'wpcomments_enable_remove_feed_link', $defaults['remove_feed_link_enabled'] ),
		'remove_website_field_enabled'  => get_option( 'wpcomments_enable_remove_website_field', $defaults['remove_website_field_enabled'] ),
		'frequently_replies_enabled'    => get_option( 'wpcomments_enable_frequently_replies', $defaults['frequently_replies_enabled'] ),
		'validation_enabled'            => get_option( 'wpcomments_enable_validation', $defaults['validation_enabled'] ),
		'moderation_info_enabled'       => get_option( 'wpcomments_enable_moderation_info', $defaults['moderation_info_enabled'] ),
		'email_notification_enabled'    => get_option( 'wpcomments_enable_email_notification', $defaults['email_notification_enabled'] ),
	);
}

/**
 * Bootstrap the plugin.
 *
 * @since 2.0.0
 */
function init(): void {
	$network_disable_comments = false;
	if ( is_multisite() ) {
		$network_disable_comments = get_site_option( 'wpcomments_network_disable_comments', false );
	}

	$site_disable_comments = get_option( 'wpcomments_disable_comments', false );

	if ( $network_disable_comments || $site_disable_comments ) {
		setup();
	} else {
		// Get feature settings.
		$feature_settings = get_feature_settings();

		$comments_disabled = apply_filters( 'wpcomments_disable_comments', $feature_settings['comments_disabled'] );
		$herpderp_status   = apply_filters( 'wpcomments_enable_herpderp', $feature_settings['herpderp_enabled'] );
		$role_badge_status = apply_filters( 'wpcomments_enable_role_badge', $feature_settings['role_badge_enabled'] );

		if ( $comments_disabled ) {
			setup();
		} else {
			if ( $herpderp_status ) {
				setup_herpderp();
			}
			if ( $role_badge_status ) {
				setup_role_badge();
			}

			if ( $feature_settings['delete_pending_enabled'] ) {
				setup_delete_pending();
			}

			if ( $feature_settings['sticky_moderate_enabled'] ) {
				setup_sticky_moderate();
			}

			// Initialize new feature modules.
			if ( $feature_settings['remove_feed_link_enabled'] ) {
				new \WPComments\WPComments_Remove_Feed_Link();
			}

			if ( $feature_settings['remove_website_field_enabled'] ) {
				new \WPComments\WPComments_Remove_Website_Field();
			}

			if ( $feature_settings['frequently_replies_enabled'] ) {
				new \WPComments\WPComments_Frequently_Replies();
			}

			if ( $feature_settings['validation_enabled'] ) {
				\WPComments\WPComments_Validation::get_instance();
			}

			if ( $feature_settings['moderation_info_enabled'] ) {
				\WPComments\WPComments_Moderation_Info::get_instance();
			}

			if ( $feature_settings['email_notification_enabled'] ) {
				new \WPComments\WPComments_Email_Notification();
			}
		}
	}

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
    $asset_data = include_once __DIR__ . "/build/index.asset.php";

    wp_enqueue_script(
        "wpcomments",
        plugin_dir_url(__FILE__) . "/build/index.js",
        $asset_data["dependencies"],
        $asset_data["version"],
        true,
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
    unset($endpoints['comments']);
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
 * Disable XML-RPC comments
 */
function disable_xmlrpc_comments(array $methods): array
{
    unset($methods['wp.newComment']);
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
 * Setup Herp Derp functionality
 */
function setup_herpderp(): void
{
    add_action('wp_enqueue_scripts', __NAMESPACE__ . '\herpderp_init');
    add_action('wp_head', __NAMESPACE__ . '\herpderp_head');
    add_filter('comment_text', __NAMESPACE__ . '\herpderp_comment_text', 40);
    add_action('admin_init', __NAMESPACE__ . '\herpderp_admin_init');
}

/**
 * Setup Role Badge functionality
 */
function setup_role_badge(): void
{
    new Comment_Author_Role_Badge();
}

/**
 * Setup Delete Pending Comments functionality
 */
function setup_delete_pending(): void
{
    if (class_exists(__NAMESPACE__ . '\Delete_Pending_Comments')) {
        new Delete_Pending_Comments();
    }
}

/**
  * Setup Sticky Moderate functionality
  */
 function setup_sticky_moderate(): void
 {
     if (class_exists(__NAMESPACE__ . '\Comments_Sticky_Moderate')) {
         new Comments_Sticky_Moderate();
     }
 }

function herpderp_init(): void
{
    if (!is_singular() || is_feed() || !get_comments_number()) return;

    $options = get_option('wpcomments_herpderp_settings') ?? null;
    $def = $options ? $options['herp'] : false;

    wp_register_script('wpcomments-herpderp',
                      WPCOMMENTS_URL . 'herpderp.js',
                      array(), WPCOMMENTS_VERSION, true);
    wp_localize_script('wpcomments-herpderp', 'Derpfault', array('herp' => $def));
    wp_enqueue_script('wpcomments-herpderp');
}

function herpderp_head(): void
{
    if (!is_singular() || is_feed() || !get_comments_number()) return;
    ?>
    <style type="text/css">
     .herpderp { float: right; text-transform: uppercase;
                 font-size: 7pt; font-weight: bold; }
    </style>
    <?php
}

function herpderp_comment_text($text)
{
    if (!is_singular() || is_feed() || !get_comments_number())
        return $text;
    return "<span class='herpc'>$text</span>";
}

function herpderp_admin_init(): void
{
    register_setting('discussion', 'wpcomments_herpderp_settings', 'array');
    add_settings_field('wpcomments-herpderp-default',
                      'Herp Derp Default',
                      __NAMESPACE__ . '\herpderp_setting_string',
                      'discussion',
                      'default');
}

function herpderp_setting_string(): void
{
    $options = get_option('wpcomments_herpderp_settings');
    $def_toggle = $options ? $options['herp'] : false;

    echo "<input id='wpcomments-herpderp-default'
             name='wpcomments_herpderp_settings[herp]'
             type='checkbox' value='herp' " . ($def_toggle ? ' checked' : '') .
       ' /> ' . __('阿巴阿巴 on by default', 'wpcomments');
}

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
        '禁用评论',
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
    $description = '启用后将完全禁用WordPress的评论系统，包括前台显示和后台管理。';
    
    if (is_multisite()) {
        $network_disabled = get_site_option('wpcomments_network_disable_comments', false);
        if ($network_disabled) {
            $disabled_attr = 'disabled="disabled"';
            $description = '评论功能已在网络级别被禁用，无法在此站点单独启用。';
        }
    }
    
    $value = get_option('wpcomments_disable_comments', false);
    if ($network_disabled) {
        $value = true;
    }
    ?>
    <fieldset>
        <legend class="screen-reader-text">
            <span>禁用评论</span>
        </legend>
        <label for="wpcomments_disable_comments">
            <input <?php checked(true, $value); ?> name="wpcomments_disable_comments" type="checkbox" id="wpcomments_disable_comments" value="1" <?php echo $disabled_attr; ?>>
            完全禁用此站点的评论功能
        </label>
        <p class="description"><?php echo $description; ?></p>
        <?php if ($network_disabled): ?>
        <p class="description">此设置由网络管理员控制。</p>
        <?php endif; ?>
    </fieldset>
    <?php
}
