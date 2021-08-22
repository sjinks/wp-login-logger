<?php

use WildWolf\WordPress\LoginLogger\RESTController;
use WildWolf\WordPress\LoginLogger\SessionManager;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Test_RESTController extends WP_Test_REST_TestCase {
	// NOSONAR
	/**
	 * @var Spy_REST_Server 
	 */
	private $server;

	/** 
	 * @var int
	 * @psalm-var positive-int
	 */
	private static $user_id;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		/** @psalm-var positive-int */
		self::$user_id = $factory->user->create( [ 'role' => 'subscriber' ] );
	}

	/**
	 * @return void
	 * @global WP_REST_Server|null $wp_rest_server
	 * @psalm-suppress UnusedVariable
	 */
	public function setUp() {
		parent::setUp();

		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;

		$wp_rest_server = new Spy_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init', $wp_rest_server );

		RESTController::instance()->register_rest_routes();
	}

	/**
	 * @return void
	 * @psalm-suppress UnusedVariable
	 */
	public function tearDown() {
		global $wp_rest_server;

		$wp_rest_server = null;
		parent::tearDown();
	}

	protected function dispatchRequest( string $method, string $route, array $attributes = [] ): WP_REST_Response {
		$route = '/' . ltrim( $route, '/' );

		$request = new WP_REST_Request( $method, $route, $attributes );
		return rest_do_request( $request );
	}

	/**
	 * @dataProvider endpoint_requires_authentication_data_provider
	 */
	public function test_endpoint_requires_authentication( string $method, string $endpoint ): void {
		$this->run_endpoint_403_check( 0, $method, $endpoint );
	}

	/**
	 * @dataProvider non_privileged_user_cannot_access_others_data_data_provider
	 */
	public function test_non_privileged_user_cannot_access_others_data( string $method, string $endpoint ): void {
		$this->run_endpoint_403_check( self::$user_id, $method, $endpoint );
	}

	private function run_endpoint_403_check( int $user_id, string $method, string $endpoint ): void {
		wp_set_current_user( $user_id );
		$response = $this->dispatchRequest( $method, $endpoint );
		$this->assertErrorResponse( 'rest_operation_not_allowed', $response, rest_authorization_required_code() );
	}

	/**
	 * @psalm-return iterable<array{string, string}>
	 */
	public function endpoint_requires_authentication_data_provider(): iterable {
		return [
			[ 'GET', RESTController::NAMESPACE . '/sessions' ],    // NOSONAR
			[ 'GET', RESTController::NAMESPACE . '/1/sessions' ],  // NOSONAR
			[ 'DELETE', RESTController::NAMESPACE . '/sessions' ],
			[ 'DELETE', RESTController::NAMESPACE . '/1/sessions' ],
			[ 'DELETE', RESTController::NAMESPACE . '/sessions/0123456789abcdef0123456789abcdef' ],
			[ 'DELETE', RESTController::NAMESPACE . '/1/sessions/0123456789abcdef0123456789abcdef' ],
		];
	}

	/**
	 * @psalm-return iterable<array{string, string}>
	 */
	public function non_privileged_user_cannot_access_others_data_data_provider(): iterable {
		return [
			[ 'GET', RESTController::NAMESPACE . '/1/sessions' ],
			[ 'DELETE', RESTController::NAMESPACE . '/1/sessions' ],
			[ 'DELETE', RESTController::NAMESPACE . '/1/sessions/0123456789abcdef0123456789abcdef' ],
		];
	}

	public function test_list_current_user_sessions(): void {
		$this->check_list_sessions( self::$user_id, RESTController::NAMESPACE . '/sessions' );
	}

	public function test_list_user_sessions(): void {
		$this->check_list_sessions( self::$user_id, sprintf( '/%s/%u/sessions', RESTController::NAMESPACE, self::$user_id ) );
	}

	private function check_list_sessions( int $user_id, string $endpoint ): void {
		wp_set_current_user( $user_id );
		wp_destroy_all_sessions();

		$expiration = time() + 3600;
		$manager    = WP_Session_Tokens::get_instance( $user_id );
		$manager->create( $expiration );

		$response = $this->dispatchRequest( 'GET', $endpoint );
		self::assertFalse( $response->is_error() );

		/** @var mixed */
		$data = $response->get_data();
		self::assertIsArray( $data );
		/** @var array $data */
		self::assertCount( 1, $data );

		self::assertArrayHasKey( 0, $data );
		/** @var mixed */
		$item = $data[0];

		self::assertIsArray( $item );
		/** @var array $item */
		self::assertArrayHasKey( 'verifier', $item );
		self::assertArrayHasKey( 'login', $item );
		self::assertArrayHasKey( 'expiration', $item );
		self::assertArrayHasKey( 'ip', $item );
		self::assertArrayHasKey( 'ua', $item );
	}

	public function test_delete_current_user_session(): void {
		$this->check_delete_session( self::$user_id, sprintf( '/%s/sessions/%%s', RESTController::NAMESPACE ) );
	}

	public function test_delete_user_session(): void {
		$this->check_delete_session( self::$user_id, sprintf( '/%s/%u/sessions/%%s', RESTController::NAMESPACE, self::$user_id ) );
	}

	private function check_delete_session( int $user_id, string $endpoint ): void {
		wp_set_current_user( $user_id );
		wp_destroy_all_sessions();

		$expiration = time() + 3600;
		$manager    = WP_Session_Tokens::get_instance( $user_id );
		$manager->create( $expiration );
		$manager->create( $expiration );

		$manager = WP_Session_Tokens::get_instance( 1 );
		$manager->create( $expiration );

		$all_sessions = array_keys( SessionManager::get_all( $user_id ) );
		self::assertCount( 2, $all_sessions );
		self::assertArrayHasKey( 0, $all_sessions );
		self::assertArrayHasKey( 1, $all_sessions );
		$session_id = $all_sessions[0];

		$response = $this->dispatchRequest( 'DELETE', sprintf( $endpoint, $session_id ) );
		self::assertFalse( $response->is_error() );

		$new_sessions = array_keys( SessionManager::get_all( $user_id ) );
		self::assertCount( 1, $new_sessions );
		self::assertArrayHasKey( 0, $new_sessions );
		self::assertSame( $all_sessions[1], $new_sessions[0] );

		// Make sure our code does not delete sessions for another user
		$other_sessions = array_keys( SessionManager::get_all( 1 ) );
		self::assertCount( 1, $other_sessions );
	}

	public function test_delete_current_user_sessions(): void {
		$this->check_delete_all_sessions( self::$user_id, sprintf( '/%s/sessions', RESTController::NAMESPACE ) );
	}

	public function test_delete_user_sessions(): void {
		$this->check_delete_all_sessions( self::$user_id, sprintf( '/%s/%u/sessions', RESTController::NAMESPACE, self::$user_id ) );
	}

	private function check_delete_all_sessions( int $user_id, string $endpoint ): void {
		wp_set_current_user( $user_id );
		wp_destroy_all_sessions();

		$expiration = time() + 3600;
		$manager    = WP_Session_Tokens::get_instance( $user_id );
		$manager->create( $expiration );
		$manager->create( $expiration );

		$manager = WP_Session_Tokens::get_instance( 1 );
		$manager->create( $expiration );

		$all_sessions = array_keys( SessionManager::get_all( $user_id ) );
		self::assertCount( 2, $all_sessions );
		self::assertArrayHasKey( 0, $all_sessions );
		self::assertArrayHasKey( 1, $all_sessions );
		$session_id = $all_sessions[0];

		$response = $this->dispatchRequest( 'DELETE', sprintf( $endpoint, $session_id ) );
		self::assertFalse( $response->is_error() );

		$new_sessions = array_keys( SessionManager::get_all( $user_id ) );
		self::assertEmpty( $new_sessions );

		// Make sure our code does not delete sessions for another user
		$other_sessions = array_keys( SessionManager::get_all( 1 ) );
		self::assertCount( 1, $other_sessions );
	}
}
