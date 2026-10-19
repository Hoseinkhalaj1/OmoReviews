<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'bizrev_reviews' ); // phpcs:ignore
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'bizrev_votes' );   // phpcs:ignore
delete_option( 'bizrev_settings' );
delete_option( 'bizrev_db_version' );

// پاکسازی ترنزینت‌ها
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bizrev_%' OR option_name LIKE '_transient_timeout_bizrev_%'" ); // phpcs:ignore
