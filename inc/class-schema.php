<?php

namespace WildWolf\WordPress\LoginLogger;

use WildWolf\Utils\Singleton;
use wpdb;

final class Schema {
	use Singleton;

	const VERSION_KEY    = 'ww_login_logger_dbver';
	const LATEST_VERSION = 1;

	/**
	 * @global wpdb $wpdb
	 * @codeCoverageIgnore Class instantiation happens during bootstrapping; `init` happens before tests are run
	 */
	private function __construct() {
		/** @var wpdb $wpdb */
		global $wpdb;

		$wpdb->login_log_table = $wpdb->prefix . 'login_log';
	}

	public function is_installed(): bool {
		$current_version = (int) get_site_option( self::VERSION_KEY, 0 );
		return $current_version > 0;
	}

	public function is_update_needed(): bool {
		$current_version = (int) get_site_option( self::VERSION_KEY, 0 );
		return $current_version < self::LATEST_VERSION;
	}

	public function update(): void {
		set_time_limit( -1 );
		ignore_user_abort( true );

		$current_version = (int) get_site_option( self::VERSION_KEY, 0 );
		$success         = true;

		if ( self::LATEST_VERSION !== $current_version ) {
			$success = $this->update_schema();
		}

		if ( $success ) {
			update_site_option( self::VERSION_KEY, self::LATEST_VERSION );
		}
	}

	/**
	 * @global wpdb $wpdb
	 */
	private function update_schema(): bool {
		/** @var wpdb $wpdb */
		global $wpdb;

		if ( ! function_exists( 'dbDelta' ) ) {
			// @codeCoverageIgnoreStart
			/** @psalm-suppress MissingFile */
			require_once ABSPATH . '/wp-admin/includes/upgrade.php';
			// @codeCoverageIgnoreEnd
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->login_log_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			ip varbinary(16) NOT NULL,
			dt int(11) NOT NULL,
			username varchar(255) NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			outcome tinyint(4) NOT NULL,
			PRIMARY KEY  (id)
		) {$charset_collate};";

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.dbDelta_dbdelta
		dbDelta( $sql );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_count = count( $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->login_log_table ) ) );
		return 1 === $table_count;
	}
}
