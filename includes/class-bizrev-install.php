<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles activation, table creation and default options.
 */
class BizRev_Install {

	const DB_VERSION = '1.0.0';

	public static function activate() {
		self::create_tables();
		self::set_defaults();
		update_option( 'bizrev_db_version', self::DB_VERSION );
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Custom table for reviews. We keep reviews separate from WP comments
	 * so multi-criteria ratings, pros/cons and verification scale cleanly.
	 */
	public static function create_tables() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$table   = $wpdb->prefix . 'bizrev_reviews';

		$sql = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT(20) UNSIGNED NOT NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			author_name VARCHAR(190) NOT NULL DEFAULT '',
			author_email VARCHAR(190) NOT NULL DEFAULT '',
			title VARCHAR(255) NOT NULL DEFAULT '',
			content LONGTEXT NOT NULL,
			rating_overall DECIMAL(3,2) NOT NULL DEFAULT 0,
			criteria_ratings LONGTEXT NULL,
			pros LONGTEXT NULL,
			cons LONGTEXT NULL,
			recommended TINYINT(1) NOT NULL DEFAULT 0,
			verified TINYINT(1) NOT NULL DEFAULT 0,
			helpful_yes INT UNSIGNED NOT NULL DEFAULT 0,
			helpful_no INT UNSIGNED NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			ip VARCHAR(100) NOT NULL DEFAULT '',
			parent_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY post_id (post_id),
			KEY status (status),
			KEY user_id (user_id),
			KEY parent_id (parent_id)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$votes = $wpdb->prefix . 'bizrev_votes';
		$sql2  = "CREATE TABLE {$votes} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			review_id BIGINT(20) UNSIGNED NOT NULL,
			voter_hash VARCHAR(64) NOT NULL,
			vote TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY review_voter (review_id, voter_hash)
		) {$charset};";
		dbDelta( $sql2 );
	}

	public static function set_defaults() {
		if ( false === get_option( 'bizrev_settings' ) ) {
			add_option( 'bizrev_settings', BizRev_Settings::defaults() );
		}
	}
}
