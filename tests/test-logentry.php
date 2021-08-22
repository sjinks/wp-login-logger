<?php

use WildWolf\WordPress\LoginLogger\LogEntry;

class Test_LogEntry extends WP_UnitTestCase {
	// NOSONAR
	public function test_getters(): void {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__
		$ip       = inet_pton( (string) ( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
		$now      = time();
		$username = 'username';
		$user_id  = 1;
		$outcome  = LogEntry::OUTCOME_ATTEMPTED;

		$entry = new LogEntry( $username, $user_id, $outcome );

		self::assertSame( $username, $entry->get_username() );
		self::assertSame( $user_id, $entry->get_user_id() );
		self::assertNotNull( $entry->get_user() );
		self::assertInstanceOf( WP_User::class, $entry->get_user() );
		self::assertSame( $outcome, $entry->get_outcome() );
		self::assertSame( $ip, $entry->get_ip() );
		self::assertInstanceOf( DateTimeInterface::class, $entry->get_dt() );
		self::assertEqualsWithDelta( $now, $entry->get_dt()->format( 'U' ), 10 );
	}

	public function test_to_array(): void {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__
		$ip       = inet_pton( (string) ( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
		$now      = time();
		$username = 'username';
		$user_id  = 1;
		$outcome  = LogEntry::OUTCOME_ATTEMPTED;

		$entry    = new LogEntry( $username, $user_id, $outcome );
		$actual   = $entry->to_array();
		$expected = [
			'ip'       => $ip,
			'username' => $username,
			'user_id'  => $user_id,
			'outcome'  => $outcome,
		];

		self::assertArrayHasKey( 'dt', $actual );
		self::assertEqualsWithDelta( $now, $actual['dt'], 10 );
		unset( $actual['dt'] );

		self::assertEquals( $expected, $actual );
	}
}
