<?php

use WildWolf\WordPress\LoginLogger\AuthUtils;

final class Test_AuthUtils extends WP_UnitTestCase /* NOSONAR */ {
	/** @var int */
	private static $user_id_1;

	/** @var int */
	private static $user_id_2;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		/** @psalm-suppress InvalidArgument - the type definitions say the second parameter is null */
		self::$user_id_1 = $factory->user->create( [], [
			'user_login' => 'test_user_1',
			'user_email' => 'test@example.org',
			'user_pass'  => 'xxx',
		] );

		/** @psalm-suppress InvalidArgument - the type definitions say the second parameter is null */
		self::$user_id_2 = $factory->user->create( [], [
			'user_login' => 'test_user_2',
			'user_email' => 'another@example.org',
			'user_pass'  => 'xxx',
		] );
	}

	public function test_get_by_login(): void {
		$user = AuthUtils::get_user_by_login_or_email( 'test_user_1' );
		self::assertInstanceOf( WP_User::class, $user );
		self::assertSame( self::$user_id_1, $user->ID );
	}

	public function test_get_by_email(): void {
		$user = AuthUtils::get_user_by_login_or_email( 'another@example.org' );
		self::assertInstanceOf( WP_User::class, $user );
		self::assertSame( self::$user_id_2, $user->ID );
	}

	public function test_non_existent_user(): void {
		$user = AuthUtils::get_user_by_login_or_email( 'cogito-ergo-sum' );
		self::assertNull( $user );
	}

	public function test_prepare_session_unformatted(): void {
		$verifier = '0123456789abcdef0123456789abcdef';
		$session  = [
			'login'      => 0,
			'expiration' => 0,
			'ip'         => '127.0.0.1',
			'ua'         => 'UA',
		];

		$expected = [ 'verifier' => $verifier ] + $session;
		$actual   = AuthUtils::prepare_session_unformatted( $verifier, $session );
		self::assertSame( $expected, $actual );
	}

	public function test_prepare_session(): void {
		$verifier = '0123456789abcdef0123456789abcdef';
		$session  = [
			'login'      => 0,
			'expiration' => 0,
		];

		$expected = [
			'verifier'   => $verifier,
			'login'      => '1970-01-01T00:00:00+00:00',
			'expiration' => '1970-01-01T00:00:00+00:00',
			'ip'         => '',
			'ua'         => '',
		];

		$actual = AuthUtils::prepare_session( $verifier, $session );
		self::assertSame( $expected, $actual );
	}
}
