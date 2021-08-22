<?php

namespace WildWolf\WordPress\LoginLogger;

use wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery

/**
 * @psalm-type LoginLogEntry = array{username: string, user_id: numeric-string, ip: string, dt: numeric-string, outcome: numeric-string}
 * @psalm-type FoundEvents = array{total: int, items: list<LoginLogEntry>}
 */
final class LoginQueries {
	/**
	 * @global wpdb $wpdb
	 * @psalm-return FoundEvents
	 */
	public static function find_events( ?string $ip = null, ?int $user = null, int $offset = 0, int $limit = 10 ): array {
		/** @var wpdb $wpdb */
		global $wpdb;

		$key   = sprintf( 'events:ip=%s|uid=%u|o=%u|l=%u', $ip ?? '', $user ?? 0, $offset, $limit );
		$group = 'login-logger';

		/** @psalm-var FoundEvents|false */
		$result = wp_cache_get( $key, $group );
		if ( ! is_array( $result ) ) {
			$where = self::build_where_clause( $ip, $user );
			$limit = self::build_limit_clause( $offset, $limit );

			$total_query = "SELECT COUNT(*) FROM {$wpdb->login_log_table} {$where}";
			$items_query = "SELECT username, user_id, INET6_NTOA(ip) AS ip, dt, outcome FROM {$wpdb->login_log_table} {$where} ORDER BY id DESC {$limit}";

			$total_items = (int) $wpdb->get_var( $total_query );                               // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			/** @psalm-var list<LoginLogEntry> */
			$items  = 0 !== $total_items ? $wpdb->get_results( $items_query, ARRAY_A ) : [];   // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$result = [
				'total' => $total_items,
				'items' => $items,
			];

			wp_cache_set( $key, $result, $group );
		}

		return $result;
	}

	/**
	 * @global wpdb $wpdb
	 */
	private static function build_where_clause( ?string $ip, ?int $user ): string {
		/** @var wpdb $wpdb */
		global $wpdb;

		$where = [ '1=1' ];

		if ( ! empty( $ip ) ) {
			$where[] = $wpdb->prepare( 'INET6_NTOA(ip) = %s', $ip );
		}

		if ( ! empty( $user ) ) {
			$where[] = $wpdb->prepare( 'user_id = %d', $user );
		}

		return 'WHERE ' . join( ' AND ', $where );
	}

	/**
	 * @global wpdb $wpdb
	 */
	private static function build_limit_clause( int $offset, int $limit ): string {
		/** @var wpdb $wpdb */
		global $wpdb;

		/** @var string */
		return $wpdb->prepare( 'LIMIT %d, %d', $offset, $limit );
	}
}
