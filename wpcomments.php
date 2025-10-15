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

if (!defined("WPINC")) {
    die();
}

define("WPCOMMENTS_VERSION", "2.0.0");
define("WPCOMMENTS_PLUGIN_FILE", __FILE__);
define("WPCOMMENTS_URL", plugin_dir_url(__FILE__));
define("WPCOMMENTS_PATH", plugin_dir_path(__FILE__));

$network_activated = is_network_wide(__FILE__);

if (!defined("WPCOMMENTS_IS_NETWORK")) {
    define("WPCOMMENTS_IS_NETWORK", $network_activated);
}

add_action("plugins_loaded", __NAMESPACE__ . "\init");
add_action("plugins_loaded", __NAMESPACE__ . "\load_textdomain");
add_action("admin_init", __NAMESPACE__ . '\register_general_settings_field');
add_action("wpcomments_reinit_features", __NAMESPACE__ . '\reinit_features');

register_activation_hook(
    WPCOMMENTS_PLUGIN_FILE,
    __NAMESPACE__ . "\activate_wpcomments",
);
add_action(
    "wpcomments_cleanup_reports",
    __NAMESPACE__ . "\cleanup_old_reports",
);

require_once WPCOMMENTS_PATH . "includes/class-wpcomments-bootstrap.php";

/**
 * Bootstrap the plugin
 *
 * @since 2.0.0
 * @return void
 */
function init(): void
{
    \WPComments\WPComments_Bootstrap::get_instance()->init();
}

/**
 * Get feature settings (legacy function for backward compatibility)
 *
 * @since 2.0.0
 * @return array Feature settings array
 */
function get_feature_settings(): array
{
    return \WPComments\WPComments_Bootstrap::get_instance()->get_current_settings();
}

/**
 * Is plugin activated network wide?
 */
function is_network_wide(string $plugin_file): bool
{
    if (!is_multisite()) {
        return false;
    }

    if (!function_exists("is_plugin_active_for_network")) {
        require_once ABSPATH . "/wp-admin/includes/plugin.php";
    }

    return is_plugin_active_for_network(plugin_basename($plugin_file));
}

// 这些功能已合并到 WPComments_Core 类中

/**
 * Load plugin textdomain
 */
function load_textdomain(): void
{
    load_plugin_textdomain(
        "wpcomments",
        false,
        dirname(plugin_basename(__FILE__)) . "/languages/",
    );
}

/**
 * Register general settings field for comments
 */
function register_general_settings_field(): void
{
    \register_setting("general", "wpcomments_disable_comments", "boolval");

    \add_settings_field(
        "wpcomments-disable-comments",
        __("禁用评论", "wpcomments"),
        __NAMESPACE__ . '\render_general_settings_field',
        "general",
    );
}

/**
 * Render general settings field
 */
function render_general_settings_field(): void
{
    $network_disabled = false;
    $disabled_attr = "";
    $description = __(
        "启用后将完全禁用WordPress的评论系统，包括前台显示和后台管理。",
        "wpcomments",
    );

    if (\is_multisite()) {
        $network_disabled = \get_site_option(
            "wpcomments_disable_comments",
            false,
        );
        if ($network_disabled) {
            $disabled_attr = 'disabled="disabled"';
            $description = __(
                "评论功能已在网络级别被禁用，无法在此站点单独启用。",
                "wpcomments",
            );
        }
    }

    $value = \get_option("wpcomments_disable_comments", false);
    if ($network_disabled) {
        $value = true;
    }
    ?>
    <fieldset>
        <legend class="screen-reader-text">
            <span><?php echo esc_html__("禁用评论", "wpcomments"); ?></span>
        </legend>
        <label for="wpcomments_disable_comments">
            <input <?php checked(
                true,
                $value,
            ); ?> name="wpcomments_disable_comments" type="checkbox" id="wpcomments_disable_comments" value="1" <?php echo $disabled_attr; ?>>
            <?php echo esc_html__("完全禁用此站点的评论功能", "wpcomments"); ?>
        </label>
        <p class="description"><?php echo esc_html($description); ?></p>
        <?php if ($network_disabled): ?>
        <p class="description"><?php echo esc_html__(
            "此设置由网络管理员控制。",
            "wpcomments",
        ); ?></p>
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
    do_action("wpcomments_reinit", get_feature_settings());
}

/**
 * Activation callback: create reports table and schedule cleanup
 */
function activate_wpcomments($network_wide): void
{
    if (is_multisite() && $network_wide) {
        $sites = get_sites(["fields" => "ids"]);
        foreach ($sites as $site_id) {
            switch_to_blog((int) $site_id);
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
function wpcomments_create_reports_table(): void
{
    global $wpdb;
    $table_name = $wpdb->prefix . "wpcomments_reports";
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
    require_once ABSPATH . "wp-admin/includes/upgrade.php";
    dbDelta($sql);
}

/**
 * Schedule daily cleanup for old reports
 */
function wpcomments_schedule_cleanup(): void
{
    if (!wp_next_scheduled("wpcomments_cleanup_reports")) {
        wp_schedule_event(
            time() + DAY_IN_SECONDS,
            "daily",
            "wpcomments_cleanup_reports",
        );
    }
}

/**
 * Cleanup old reports older than retention days (default 90)
 */
function cleanup_old_reports(): void
{
    global $wpdb;
    $table_name = $wpdb->prefix . "wpcomments_reports";
    $days = (int) (\class_exists("WPComments\\WPComments_Network_Settings")
        ? \WPComments\WPComments_Network_Settings::get_effective_setting(
            "wpcomments_report_retention_days",
            \apply_filters("wpcomments_report_retention_days", 90),
        )
        : \get_option(
            "wpcomments_report_retention_days",
            \apply_filters("wpcomments_report_retention_days", 90),
        ));
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$table_name} WHERE report_time < ( NOW() - INTERVAL %d DAY )",
            $days,
        ),
    );
}
