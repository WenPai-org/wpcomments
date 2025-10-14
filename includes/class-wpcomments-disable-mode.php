<?php
/**
 * WPComments Disable Mode Class
 *
 * @package WPComments
 * @since   2.0.0
 */

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Disable Mode Class
 * 
 * Handles complete disabling of WordPress comment system
 * This is the simple mode for compliance with Chinese regulations
 */
class WPComments_Disable_Mode {

    /**
     * Setup comment disabling functionality
     *
     * @return void
     */
    public function setup(): void {
        add_filter('comments_open', '__return_false');
        add_filter('pings_open', '__return_false');
        add_filter('get_comments_number', '__return_zero');
        add_filter('comments_pre_query', [$this, 'filter_comments_pre_query'], 10, 2);
        add_filter('feed_links_show_comments_feed', '__return_false');
        add_filter('comments_rewrite_rules', '__return_empty_array');

        add_action('init', [$this, 'remove_comment_support'], 99);
        add_action('init', [$this, 'remove_trackback_support'], 99);
        add_action('enqueue_block_editor_assets', [$this, 'unregister_comment_blocks_javascript']);
        add_action('init', [$this, 'unregister_comment_blocks'], 99);

        add_filter('wp_count_comments', [$this, 'filter_wp_count_comments']);
        add_action('add_admin_bar_menus', [$this, 'remove_admin_bar_comments_menu']);
        add_action('admin_bar_menu', [$this, 'remove_my_sites_comments_menu'], 21);
        add_action('admin_menu', [$this, 'remove_comments_menu_page']);
        add_action('load-options-discussion.php', [$this, 'block_comments_admin_screen']);
        add_action('load-edit-comments.php', [$this, 'block_comments_admin_screen']);

        add_filter('rest_endpoints', [$this, 'remove_rest_api_endpoints']);
        add_filter('rest_pre_insert_comment', [$this, 'disable_rest_api_comments'], 10, 2);
        add_filter('xmlrpc_methods', [$this, 'disable_xmlrpc_comments']);
        
        add_action('init', [$this, 'enhanced_comment_disabling'], 99);
        add_filter('comments_template', [$this, 'disable_comments_template'], 20);
        add_filter('comments_number', '__return_empty_string');
        add_filter('get_default_comment_status', [$this, 'force_closed_comment_status'], 999);
        add_action('do_feed_rss2', [$this, 'disable_comment_feeds'], 1);
        add_action('do_feed_rss', [$this, 'disable_comment_feeds'], 1);
        add_action('pre_ping', [$this, 'disable_outgoing_pings']);
        add_filter('manage_pages_columns', [$this, 'remove_comments_column_from_pages']);

        add_action('admin_head', [$this, 'hide_dashboard_comment_widgets']);
        add_action('wp_dashboard_setup', [$this, 'remove_dashboard_comment_widgets']);
    }

    /**
     * Filter the comments pre query
     *
     * @param array<int,\WP_Comment>|int|null $comments The comments to filter
     * @param \WP_Comment_Query               $query    The query object
     * @return array<int,\WP_Comment>|int The filtered comments
     */
    public function filter_comments_pre_query($comments, \WP_Comment_Query $query) {
        if ($query->query_vars['count']) {
            return 0;
        }
        return [];
    }

    /**
     * Remove comments support from all post types
     *
     * @return void
     */
    public function remove_comment_support(): void {
        $post_types = get_post_types_by_support('comments');
        foreach ($post_types as $post_type) {
            remove_post_type_support($post_type, 'comments');
        }
    }

    /**
     * Remove trackbacks support from all post types
     *
     * @return void
     */
    public function remove_trackback_support(): void {
        $post_types = get_post_types_by_support('trackbacks');
        foreach ($post_types as $post_type) {
            remove_post_type_support($post_type, 'trackbacks');
        }
    }

    /**
     * Enqueue script to remove comment blocks
     *
     * @return void
     */
    public function unregister_comment_blocks_javascript(): void {
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
     * Remove comment blocks server-side
     *
     * @return void
     */
    public function unregister_comment_blocks(): void {
        $registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();

        $blocks = [
            "core/comments",
            "core/comments-query-loop",
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
                add_filter('render_block_' . $block, '__return_empty_string');
            }
        }
    }

    /**
     * Remove comments menu pages
     *
     * @return void
     */
    public function remove_comments_menu_page(): void {
        remove_menu_page("edit-comments.php");
        remove_submenu_page("options-general.php", "options-discussion.php");
    }

    /**
     * Remove admin bar comments menu
     *
     * @return void
     */
    public function remove_admin_bar_comments_menu(): void {
        remove_action("admin_bar_menu", "wp_admin_bar_comments_menu", 60);
    }

    /**
     * Remove My Sites comments menu
     *
     * @return void
     */
    public function remove_my_sites_comments_menu(): void {
        global $wp_admin_bar;

        if (!is_multisite() || !is_user_logged_in()) {
            return;
        }

        if (count($wp_admin_bar->user->blogs) < 1) {
            return;
        }

        if (!function_exists("is_plugin_active_for_network")) {
            require_once ABSPATH . "/wp-admin/includes/plugin.php";
        }

        if (!is_plugin_active_for_network(plugin_basename(WPCOMMENTS_PLUGIN_FILE))) {
            return;
        }

        foreach ($wp_admin_bar->user->blogs as $blog) {
            $wp_admin_bar->remove_menu("blog-" . $blog->userblog_id . "-c");
        }
    }

    /**
     * Filter wp_count_comments to return zero
     *
     * @return \stdClass
     */
    public function filter_wp_count_comments(): \stdClass {
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
     * Block access to comment admin screens
     *
     * @return void
     */
    public function block_comments_admin_screen(): void {
        wp_die(
            esc_html__("This screen is disabled by the WPComments plugin.", "wpcomments")
        );
    }

    /**
     * Remove REST API comment endpoints
     *
     * @param array $endpoints Existing endpoints
     * @return array Modified endpoints
     */
    public function remove_rest_api_endpoints(array $endpoints): array {
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
     *
     * @param mixed $prepared_comment Prepared comment
     * @param \WP_REST_Request $request Request object
     * @return \WP_Error
     */
    public function disable_rest_api_comments($prepared_comment, \WP_REST_Request $request): \WP_Error {
        return new \WP_Error(
            'rest_comments_disabled',
            esc_html__('Comments are closed.', 'wpcomments'),
            ['status' => 403]
        );
    }

    /**
     * Disable XML-RPC comments
     *
     * @param array $methods XML-RPC methods
     * @return array Modified methods
     */
    public function disable_xmlrpc_comments(array $methods): array {
        unset($methods['wp.newComment']);
        unset($methods['pingback.ping']);
        return $methods;
    }

    /**
     * Enhanced comment disabling features
     *
     * @return void
     */
    public function enhanced_comment_disabling(): void {
        $post_types = get_post_types();
        foreach ($post_types as $post_type) {
            add_filter("rest_{$post_type}_item_schema", [$this, 'cleanup_rest_api_schema']);
            add_filter('rest_prepare_' . $post_type, [$this, 'cleanup_rest_prepare_post_type']);
        }
    }

    /**
     * Remove comment fields from REST API schema
     *
     * @param array $schema REST API schema
     * @return array Modified schema
     */
    public function cleanup_rest_api_schema($schema): array {
        unset($schema['properties']['comment_status']);
        unset($schema['properties']['ping_status']);
        return $schema;
    }

    /**
     * Remove replies link from REST API response
     *
     * @param \WP_REST_Response $response REST response
     * @return \WP_REST_Response Modified response
     */
    public function cleanup_rest_prepare_post_type($response) {
        $response->remove_link('replies');
        return $response;
    }

    /**
     * Disable comments template
     *
     * @return string Empty template path
     */
    public function disable_comments_template(): string {
        return WPCOMMENTS_PATH . 'templates/blank-comments.php';
    }

    /**
     * Force closed comment status
     *
     * @return string
     */
    public function force_closed_comment_status(): string {
        return 'closed';
    }

    /**
     * Disable comment feeds
     *
     * @return void
     */
    public function disable_comment_feeds(): void {
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
     *
     * @param array $links Links array
     * @return void
     */
    public function disable_outgoing_pings(&$links): void {
        $links = [];
    }

    /**
     * Remove comments column from pages
     *
     * @param array $columns Page columns
     * @return array Modified columns
     */
    public function remove_comments_column_from_pages($columns): array {
        unset($columns['comments']);
        return $columns;
    }

    /**
     * Hide dashboard comment widgets with CSS
     *
     * @return void
     */
    public function hide_dashboard_comment_widgets(): void {
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
     *
     * @return void
     */
    public function remove_dashboard_comment_widgets(): void {
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    }
}