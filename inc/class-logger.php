<?php

namespace WildWolf\WordPress\LoginLogger;

use WildWolf\Utils\Singleton;
use WP_User;
use wpdb;

class Logger {
	use Singleton;

	/**
	 * @param scalar $login
	 */
	public function log_login_attempt( $login ): void {
		$this->log( new LogEntry( $login, 0, LogEntry::OUTCOME_ATTEMPTED ) );
	}

	public function log_successful_login( WP_User $user ): void {
		$this->log( new LogEntry( $user->user_login, $user->ID, LogEntry::OUTCOME_SUCCEEDED ) );
	}

	/**
	 * @param scalar $login
	 */
	public function log_failed_login( $login, ?WP_User $user ): void {
		$this->log( new LogEntry( $login, $user ? $user->ID : 0, LogEntry::OUTCOME_FAILED ) );
	}

	/**
	 * @global wpdb $wpdb
	 */
	private function log( LogEntry $entry ): void {
		/** @var wpdb $wpdb */
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert( $wpdb->login_log_table, $entry->to_array() );
		do_action( 'log_login_attempt', $entry );
	}
}
