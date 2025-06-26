<?php

use WildWolf\WordPress\LoginLogger\EventWatcher;
use WildWolf\WordPress\LoginLogger\LogEntry;

final class Test_EventWatcher extends WP_UnitTestCase /* NOSONAR */ {
	/** @var int */
	private static $user_id;

	/** 
	 * @var LogEntry[]
	 * @psalm-var list<LogEntry>
	 */
	private $log = [];

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		/** @psalm-suppress InvalidArgument - the type definitions say the second parameter is null */
		self::$user_id = $factory->user->create( [], [
			'user_login' => 'test_user',
			'user_email' => 'test@example.org',
			'user_pass'  => 'xxx',
		] );
	}

	public function setUp(): void {
		parent::setUp();
		$this->log = [];
	}

	public function test_hooks_are_set_up(): void {
		$instance = EventWatcher::instance();

		self::assertNotFalse( has_action( 'wp_login', [ $instance, 'wp_login' ] ) );
		self::assertNotFalse( has_action( 'wp_login_failed', [ $instance, 'wp_login_failed' ] ) );
		self::assertNotFalse( has_filter( 'authenticate', [ $instance, 'authenticate' ] ) );
	}

	public function test_failed_login_flow(): void {
		self::assertFalse( has_action( 'log_login_attempt', [ $this, 'log_login_attempt' ] ) );

		add_action( 'log_login_attempt', [ $this, 'log_login_attempt' ] );

		$username = 'test_user';
		wp_authenticate( $username, 'yyy' );

		self::assertCount( 2, $this->log );
		self::assertArrayHasKey( 0, $this->log );
		self::assertArrayHasKey( 1, $this->log );

		$entry_1 = $this->log[0];
		$entry_2 = $this->log[1];

		self::assertSame( LogEntry::OUTCOME_ATTEMPTED, $entry_1->get_outcome() );
		self::assertSame( LogEntry::OUTCOME_FAILED, $entry_2->get_outcome() );
		self::assertSame( $username, $entry_2->get_username() );
		self::assertSame( self::$user_id, $entry_2->get_user_id() );
	}

	public function test_failed_login_flow_invalid_user(): void {
		self::assertFalse( has_action( 'log_login_attempt', [ $this, 'log_login_attempt' ] ) );

		add_action( 'log_login_attempt', [ $this, 'log_login_attempt' ] );

		$username = 'nox_existing_user';
		wp_authenticate( $username, 'yyy' );

		self::assertCount( 2, $this->log );
		self::assertArrayHasKey( 0, $this->log );
		self::assertArrayHasKey( 1, $this->log );

		$entry_1 = $this->log[0];
		$entry_2 = $this->log[1];

		self::assertSame( LogEntry::OUTCOME_ATTEMPTED, $entry_1->get_outcome() );
		self::assertSame( LogEntry::OUTCOME_FAILED, $entry_2->get_outcome() );
		self::assertSame( $username, $entry_2->get_username() );
		self::assertSame( 0, $entry_2->get_user_id() );
		self::assertNull( $entry_2->get_user() );
	}

	public function test_successful_login_flow(): void {
		self::assertFalse( has_action( 'log_login_attempt', [ $this, 'log_login_attempt' ] ) );

		$key   = 'last_login_user_' . self::$user_id;
		$group = 'login_logger';
		wp_cache_set( $key, time() - 86400, $group, 3600 );

		add_action( 'log_login_attempt', [ $this, 'log_login_attempt' ] );

		$username = 'test_user';
		$user     = wp_authenticate( $username, 'xxx' );
		self::assertInstanceOf( WP_User::class, $user );
		do_action( 'wp_login', $username, $user );

		self::assertCount( 2, $this->log );
		self::assertArrayHasKey( 0, $this->log );
		self::assertArrayHasKey( 1, $this->log );

		$entry_1 = $this->log[0];
		$entry_2 = $this->log[1];

		self::assertSame( LogEntry::OUTCOME_ATTEMPTED, $entry_1->get_outcome() );
		self::assertSame( LogEntry::OUTCOME_SUCCEEDED, $entry_2->get_outcome() );
		self::assertSame( $username, $entry_2->get_username() );
		self::assertSame( self::$user_id, $entry_2->get_user_id() );

		/** @var scalar */
		$last_login = wp_cache_get( $key, $group );

		self::assertNotFalse( $last_login );
		self::assertIsInt( $last_login );
		self::assertEqualsWithDelta( $last_login, $entry_2->get_dt()->format( 'U' ), 10 );
	}

	public function log_login_attempt( LogEntry $entry ): void {
		$this->log[] = $entry;
	}
}
