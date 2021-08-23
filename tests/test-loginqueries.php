<?php

use WildWolf\WordPress\LoginLogger\Logger;
use WildWolf\WordPress\LoginLogger\LoginQueries;

class Test_LoginQueries extends WP_UnitTestCase {
	// NOSONAR
	// NOSONAR
	/** 
	 * @var string[] 
	 * @psalm-var list<string>
	 */
	private $queries;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->queries = [];
		wp_cache_flush();
	}

	public function test_find_events_basic(): void {
		$username = 'test';

		/** @var Logger */
		$logger = Logger::instance();
		$logger->log_login_attempt( $username );

		$result = LoginQueries::find_events();
		self::assertArrayHasKey( 'total', $result );
		self::assertArrayHasKey( 'items', $result );
		self::assertSame( 1, $result['total'] );
		self::assertIsArray( $result['items'] );
		self::assertCount( $result['total'], $result['items'] );
		self::assertArrayHasKey( 0, $result['items'] );
		self::assertArrayHasKey( 'username', $result['items'][0] );
		self::assertSame( $username, $result['items'][0]['username'] );
	}

	public function test_find_events_no_conditions(): void {
		$username = 'test';

		/** @var Logger */
		$logger = Logger::instance();
		$logger->log_login_attempt( $username );

		try {
			add_filter( 'query', [ $this, 'query_filter' ] );
			LoginQueries::find_events();
		} finally {
			remove_filter( 'query', [ $this, 'query_filter' ] );
		}

		self::assertCount( 2, $this->queries );
		self::assertArrayHasKey( 0, $this->queries );
		self::assertArrayHasKey( 1, $this->queries );

		list($total_query, $items_query) = $this->queries;

		self::assertStringContainsString( 'COUNT(*)', $total_query );
		self::assertStringNotContainsString( 'INET6_NTOA(ip) = ', $total_query ); // NOSONAR
		self::assertStringNotContainsString( 'INET6_NTOA(ip) = ', $items_query );
		self::assertStringNotContainsString( 'user_id = ', $total_query );        // NOSONAR
		self::assertStringNotContainsString( 'user_id = ', $items_query );
		self::assertStringNotContainsString( 'LIMIT ', $total_query );
		self::assertStringNotContainsString( 'ORDER BY ', $total_query );
	}

	public function test_find_events_no_results(): void {
		try {
			add_filter( 'query', [ $this, 'query_filter' ] );
			$result = LoginQueries::find_events();
		} finally {
			remove_filter( 'query', [ $this, 'query_filter' ] );
		}

		self::assertArrayHasKey( 'total', $result );
		self::assertArrayHasKey( 'items', $result );
		self::assertSame( 0, $result['total'] );
		self::assertIsArray( $result['items'] );
		self::assertEmpty( $result['items'] );

		self::assertCount( 1, $this->queries );
		self::assertArrayHasKey( 0, $this->queries );
	}

	public function test_find_events_with_filters(): void {
		wp_set_current_user( 1 );
		$user = wp_get_current_user();
		$ip   = $_SERVER['REMOTE_ADDR']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__

		/** @var Logger */
		$logger = Logger::instance();
		$logger->log_successful_login( $user );

		try {
			add_filter( 'query', [ $this, 'query_filter' ] );
			$result = LoginQueries::find_events( $ip, $user->ID );
		} finally {
			remove_filter( 'query', [ $this, 'query_filter' ] );
		}

		$result = LoginQueries::find_events();
		self::assertArrayHasKey( 'total', $result );
		self::assertArrayHasKey( 'items', $result );
		self::assertSame( 1, $result['total'] );
		self::assertIsArray( $result['items'] );
		self::assertCount( $result['total'], $result['items'] );

		self::assertCount( 2, $this->queries );
		self::assertArrayHasKey( 0, $this->queries );
		self::assertArrayHasKey( 1, $this->queries );

		list($total_query, $items_query) = $this->queries;

		self::assertStringContainsString( 'INET6_NTOA(ip) = ', $total_query );
		self::assertStringContainsString( 'INET6_NTOA(ip) = ', $items_query );
		self::assertStringContainsString( 'user_id = ', $total_query );
		self::assertStringContainsString( 'user_id = ', $items_query );
	}

	public function test_find_events_caching(): void {
		wp_set_current_user( 1 );
		$user = wp_get_current_user();
		$ip   = $_SERVER['REMOTE_ADDR']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__

		/** @var Logger */
		$logger = Logger::instance();
		$logger->log_successful_login( $user );

		try {
			add_filter( 'query', [ $this, 'query_filter' ] );
			$result = LoginQueries::find_events( $ip, $user->ID );
		} finally {
			remove_filter( 'query', [ $this, 'query_filter' ] );
		}

		LoginQueries::find_events();
		self::assertCount( 2, $this->queries );
		// This call should retrieve the results from the cache
		LoginQueries::find_events();
		self::assertCount( 2, $this->queries );
	}

	public function query_filter( string $query ): string {
		$this->queries[] = $query;
		return $query;
	}
}
