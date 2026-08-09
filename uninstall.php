<?php
// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Preserve user data unless an administrator explicitly opted in before uninstall.
$delete_data = defined( 'WPCOMMENTS_DELETE_DATA_ON_UNINSTALL' )
    && WPCOMMENTS_DELETE_DATA_ON_UNINSTALL;
if ( is_multisite() ) {
    $delete_data = $delete_data || (bool) get_site_option( 'wpcomments_delete_data_on_uninstall', false );
} else {
    $delete_data = $delete_data || (bool) get_option( 'wpcomments_delete_data_on_uninstall', false );
}
if ( ! $delete_data ) {
    return;
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
    'wpcomments_report_logged_in_only',
    'wpcomments_report_min_interval',
    'wpcomments_report_retention_days',
    'wpcomments_report_privacy_notice',
    'wpcomments_frequently_replies',
    'wpcomments_character_limiter_options',
    'wpcomments_moderation_info_position',
    'wpcomments_moderation_info_show_author',
    'wpcomments_moderation_show_last_modified',
    'wpcomments_moderation_show_edit_author',
    'wpcomments_db_version',
    'wpcomments_delete_data_on_uninstall',

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
    'wpcomments_enable_private_comments',
    'wpcomments_enable_comment_limiter',
    'wpcomments_enable_report_comments',
    'wpcomments_report_logged_in_only',
    'wpcomments_report_min_interval',
    'wpcomments_report_retention_days',
    'wpcomments_report_privacy_notice',
    'wpcomments_enable_gongan_beian',
    'wpcomments_delete_data_on_uninstall',
);

$comment_meta_keys = array(
    'wpcomments_private',
    'wpcomments_sticky',
    'wpcomments_subscribe_to_comment',
    'comodinfo_comment_revisions',
    'yjwgaba_ipport',
);

/**
 * Drop reports table for current blog if exists
 */
function wpcomments_drop_reports_table_for_blog() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpcomments_reports';
    $wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
        foreach ( $comment_meta_keys as $key ) {
            delete_metadata( 'comment', 0, $key, '', true );
        }
        wp_clear_scheduled_hook( 'wpcomments_cleanup_reports' );
        wpcomments_drop_reports_table_for_blog();
        restore_current_blog();
    }
} else {
    // Single site: delete options and drop reports table
    foreach ( $option_keys as $key ) {
        delete_option( $key );
    }
    foreach ( $comment_meta_keys as $key ) {
        delete_metadata( 'comment', 0, $key, '', true );
    }
    wp_clear_scheduled_hook( 'wpcomments_cleanup_reports' );
    wpcomments_drop_reports_table_for_blog();
}
