<?php
// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Known option keys (site-level)
$option_keys = array(
    // Core toggles
    'wpcomments_disable_comments',
    'wpcomments_enable_role_badge',
    'wpcomments_enable_delete_pending',
    'wpcomments_enable_remove_website_field',
    'wpcomments_enable_frequently_replies',
    'wpcomments_enable_validation',
    'wpcomments_enable_moderation_info',
    'wpcomments_enable_email_notification',
    'wpcomments_enable_private_comments',
    'wpcomments_enable_comment_limiter',
    'wpcomments_enable_report_comments',
    'wpcomments_enable_sticky_comments',

    // Module settings
    'wpcomments_private_comment_options',
    'wpcomments_comment_limiter_options',
    'wpcomments_report_threshold',
    'wpcomments_frequently_replies',

    // Email notification settings
    'wpcomments_email_admin_notification',
    'wpcomments_email_admin_email',
    'wpcomments_email_default_subscribe',
    'wpcomments_email_gdpr_enabled',
    'wpcomments_email_privacy_url',
    'wpcomments_email_from_email',
    'wpcomments_email_from_name',
    'wpcomments_email_subject_type',
    'wpcomments_email_custom_subject',
);

// Network-level keys (site_option)
$network_keys = array(
    'wpcomments_network_enabled',
    'wpcomments_network_enforce',
    'wpcomments_network_controlled_options',
    'wpcomments_allow_site_override',

    // Network-enforced mirrors (stored as site_option with same keys)
    'wpcomments_disable_comments',
    'wpcomments_enable_validation',
    'wpcomments_enable_remove_website_field',
    'wpcomments_enable_role_badge',
    'wpcomments_enable_delete_pending',
    'wpcomments_enable_frequently_replies',
    'wpcomments_enable_moderation_info',
    'wpcomments_enable_sticky_comments',
    'wpcomments_enable_email_notification',
);

/**
 * Drop reports table for current blog if exists
 */
function wpcomments_drop_reports_table_for_blog() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpcomments_reports';
    $wpdb->query( 'DROP TABLE IF EXISTS ' . $table_name ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

if ( is_multisite() ) {
    // Delete network-level options
    foreach ( $network_keys as $key ) {
        delete_site_option( $key );
    }

    // Iterate all sites and delete site-level options and tables
    $sites = get_sites( array( 'fields' => 'ids' ) );
    foreach ( $sites as $site_id ) {
        switch_to_blog( (int) $site_id );
        foreach ( $option_keys as $key ) {
            delete_option( $key );
        }
        wpcomments_drop_reports_table_for_blog();
        restore_current_blog();
    }
} else {
    // Single site: delete options and drop reports table
    foreach ( $option_keys as $key ) {
        delete_option( $key );
    }
    wpcomments_drop_reports_table_for_blog();
}
