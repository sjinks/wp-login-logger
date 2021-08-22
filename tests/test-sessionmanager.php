<?php

use WildWolf\WordPress\LoginLogger\SessionManager;

class Test_SessionManager extends WP_UnitTestCase {
	// NOSONAR
	/** @var int */
	private $expiration_1;
	/** @var int */
	private $expiration_2;
	/** @var int */
	private $expiration_3;

	public function setUp(): void {
		parent::setUp();

		$user_id = 1;
		wp_set_current_user( $user_id );
		wp_destroy_all_sessions();

		$this->expiration_1 = time() + 3600;
		$this->expiration_2 = $this->expiration_1 + 3600;
		$this->expiration_3 = $this->expiration_2 + 3600;
		$manager            = WP_Session_Tokens::get_instance( $user_id );
		$manager->create( $this->expiration_1 );
		$manager->create( $this->expiration_2 );
		$manager->create( $this->expiration_3 );
	}

	public function test_get_all(): void {
		$user_id  = get_current_user_id();
		$sessions = SessionManager::get_all( $user_id );
		self::assertCount( 3, $sessions );
		foreach ( $sessions as $session ) {
			self::assertArrayHasKey( 'expiration', $session );
			self::assertContains( $session['expiration'], [ $this->expiration_1, $this->expiration_2, $this->expiration_3 ] );
		}
	}

	public function test_delete(): void {
		$user_id  = get_current_user_id();
		$sessions = SessionManager::get_all( $user_id );
		self::assertCount( 3, $sessions );
		$verifiers = array_keys( $sessions );
		self::assertArrayHasKey( 0, $verifiers );

		SessionManager::delete( $user_id, $verifiers[0] );
		$sessions = SessionManager::get_all( $user_id );
		self::assertCount( 2, $sessions );
		self::assertArrayNotHasKey( $verifiers[0], $sessions );
		self::assertArrayHasKey( $verifiers[1], $sessions );
		self::assertArrayHasKey( $verifiers[2], $sessions );
	}

	public function test_delete_all(): void {
		$user_id  = get_current_user_id();
		$sessions = SessionManager::get_all( $user_id );
		self::assertCount( 3, $sessions );

		SessionManager::delete_all( $user_id );
		$sessions = SessionManager::get_all( $user_id );
		self::assertEmpty( $sessions );
	}
}
