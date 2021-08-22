<?php

// @codeCoverageIgnoreStart
if ( defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	delete_option( 'ww_login_logger_dbver' );

	/** @psalm-suppress InvalidGlobal */
	global $wpdb;
	/** @var wpdb $wpdb */
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}login_log" );
}
// @codeCoverageIgnoreEnd
