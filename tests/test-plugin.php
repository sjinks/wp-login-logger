<?php

use WildWolf\WordPress\LoginLogger\Plugin;

class Test_Plugin extends WP_UnitTestCase {
	// NOSONAR
	/** @var int */
	private static $user_id;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$user_id = $factory->user->create( [], [
			'user_login' => 'test_user',
			'user_email' => 'test@example.org',
			'user_pass'  => 'xxx',
		] );
	}

	public function test_get_last_login_date_cached(): void {
		$key   = 'last_login_user_' . self::$user_id;
		$group = 'login_logger';
		wp_cache_set( $key, time() - 86400, $group, 3600 );

		$this->login();

		$expected = wp_cache_get( $key, $group );
		$actual   = Plugin::get_last_login_date( self::$user_id );

		self::assertNotFalse( $expected );
		self::assertSame( $expected, $actual );
	}

	public function test_get_last_login_not_cached(): void {
		$key   = 'last_login_user_' . self::$user_id;
		$group = 'login_logger';
		wp_cache_delete( $key, $group );

		$this->login();

		$cached   = wp_cache_get( $key, $group );
		$expected = time();
		$actual   = Plugin::get_last_login_date( self::$user_id );

		self::assertFalse( $cached );
		self::assertEqualsWithDelta( $expected, $actual, 10 );
	}

	public function test_login_form_logout(): void {
		try {
			$_GET['everywhere'] = 1;

			wp_set_current_user( self::$user_id );
			wp_destroy_all_sessions();
			$sessions = wp_get_all_sessions();
			self::assertEmpty( $sessions );

			$expiration_1 = time() + 3600;
			$expiration_2 = time() + 7200;

			// Create the first session
			$manager  = WP_Session_Tokens::get_instance( self::$user_id );
			$token    = $manager->create( $expiration_1 );
			$sessions = wp_get_all_sessions();
			self::assertCount( 1, $sessions );

			// Pretend the user has logged in: we need this to be able to create the nonce
			// and get the current session token
			// phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
			$_COOKIE[ LOGGED_IN_COOKIE ] = "1|2|{$token}|4";
			$_REQUEST['_wpnonce']        = wp_create_nonce( 'log-out' );

			// Create the second session
			$manager->create( $expiration_2 );
			$sessions = wp_get_all_sessions();
			self::assertCount( 2, $sessions );

			// Simulate (partial) logout: `login_form_logout()` should destroy all sessions except for the current one
			// The current session is intended be destroyed by `wp_logout()`
			Plugin::instance()->login_form_logout();
			$sessions = wp_get_all_sessions();
			self::assertCount( 1, $sessions );

			self::assertArrayHasKey( 0, $sessions );
			$session = $sessions[0];
			self::assertArrayHasKey( 'expiration', $session );
			self::assertSame( $expiration_1, $session['expiration'] );
		} finally {
			// phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE, WordPress.Security.NonceVerification.Recommended
			unset( $_GET['everywhere'], $_REQUEST['_wpnonce'], $_COOKIE[ LOGGED_IN_COOKIE ] );
		}
	}

	private function login(): void {
		$username = 'test_user';
		$user     = wp_authenticate( $username, 'xxx' );
		self::assertInstanceOf( WP_User::class, $user );
		do_action( 'wp_login', $username, $user );
	}
}
