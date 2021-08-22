<?php

namespace WildWolf\WordPress\LoginLogger;

use DateTime;
use WP_User;

/**
 * @psalm-import-type Session from SessionManager
 * @psalm-type PreparedSession = array{verifier: string, login: string, expiration: string, ip: string, ua: string}
 * @psalm-type PreparedSessionUnformatted = array{verifier: string, login: int, expiration: int, ip: string, ua: string}
 */
abstract class AuthUtils {
	public static function get_user_by_login_or_email( string $login ): ?WP_User {
		$user = get_user_by( 'login', $login );
		if ( false === $user && false !== strpos( $login, '@', 1 ) ) {
			$user = get_user_by( 'email', $login );
		}

		return $user ?: null;
	}

	/**
	 * @psalm-param Session $session
	 * @psalm-return PreparedSession
	 */
	public static function prepare_session( string $verifier, array $session ): array {
		/** @var DateTime */
		$login = DateTime::createFromFormat( 'U', (string) $session['login'] );
		/** @var DateTime */
		$expiration           = DateTime::createFromFormat( 'U', (string) $session['expiration'] );
		$result               = self::prepare_session_unformatted( $verifier, $session );
		$result['login']      = $login->format( DateTime::RFC3339 );
		$result['expiration'] = $expiration->format( DateTime::RFC3339 );
		return $result;
	}

	/**
	 * @psalm-param Session $session
	 * @psalm-return PreparedSessionUnformatted
	 */
	public static function prepare_session_unformatted( string $verifier, array $session ): array {
		return [
			'verifier'   => $verifier,
			'login'      => $session['login'],
			'expiration' => $session['expiration'],
			'ip'         => $session['ip'] ?? '',
			'ua'         => $session['ua'] ?? '',
		];
	}
}
