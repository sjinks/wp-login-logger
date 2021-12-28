<?php

namespace WildWolf\WordPress\LoginLogger;

use WildWolf\Utils\Singleton;
use WP_Error;
use WP_User;

final class EventWatcher {
	use Singleton;

	/**
	 * Constructed during `init`
	 * 
	 * @codeCoverageIgnore Class instantiation happens during bootstrapping; `init` happens before tests are run
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * @codeCoverageIgnore `init` happens before tests are run
	 */
	private function init(): void {
		add_action( 'wp_login', [ $this, 'wp_login' ], 9999, 2 );
		add_action( 'wp_login_failed', [ $this, 'wp_login_failed' ], 0, 1 );
		add_filter( 'authenticate', [ $this, 'authenticate' ], 9999, 2 );
	}

	/**
	 * @param null|WP_User|WP_Error $result
	 * @param string|scalar $login
	 * @return null|WP_User|WP_Error
	 */
	public function authenticate( $result, $login ) {
		if ( $login ) {
			Logger::instance()->log_login_attempt( $login );
		}

		return $result;
	}

	/**
	 * @param string|scalar $login
	 */
	public function wp_login( $login, WP_User $user ): void {
		Logger::instance()->log_successful_login( $user );

		$key   = 'last_login_user_' . $user->ID;
		$group = 'login_logger';

		wp_cache_replace( $key, time(), $group, 3600 );
	}

	/**
	 * @param string|scalar $login
	 */
	public function wp_login_failed( $login ): void {
		if ( $login ) {
			$user = AuthUtils::get_user_by_login_or_email( (string) $login );
			Logger::instance()->log_failed_login( $login, $user );
		}
	}
}
