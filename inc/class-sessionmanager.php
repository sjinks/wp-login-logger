<?php

namespace WildWolf\WordPress\LoginLogger;

use ReflectionClass;
use WP_Session_Tokens;

/**
 * @psalm-type Session = array{login: int|numeric-string, expiration: int|numeric-string, ip?: string, ua?: string}
 */
abstract class SessionManager {
	/**
	 * @psalm-return array<string,Session>
	 */
	public static function get_all( int $user_id ): array {
		$manager    = WP_Session_Tokens::get_instance( $user_id );
		$reflection = new ReflectionClass( $manager );
		$method     = $reflection->getMethod( 'get_sessions' );
		$method->setAccessible( true ); // NOSONAR
		/** @var array<string,Session> */
		return $method->invoke( $manager );
	}

	public static function delete( int $user_id, string $verifier ): void {
		$manager    = WP_Session_Tokens::get_instance( $user_id );
		$reflection = new ReflectionClass( $manager );
		$method     = $reflection->getMethod( 'update_session' );
		$method->setAccessible( true ); // NOSONAR
		$method->invoke( $manager, $verifier, null );
	}

	public static function delete_all( int $user_id ): void {
		$manager = WP_Session_Tokens::get_instance( $user_id );
		$manager->destroy_all();
	}
}
