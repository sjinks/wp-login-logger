<?php

namespace WildWolf\WordPress\LoginLogger;

use WP_User;

final class Admin {
	use Singleton;

	private function __construct() {
		$this->init();
	}

	private function init(): void {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );

		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'show_user_profile', [ $this, 'show_user_profile' ], 0, 1 );
			add_filter( 'user_row_actions', [ $this, 'user_row_actions' ], 10, 2 );
		}

		add_action( 'edit_user_profile', [ $this, 'show_user_profile' ], 0, 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
	}

	public function admin_menu(): void {
		$hook = add_management_page( __( 'Login Log', 'login-logger' ), __( 'Login Log', 'login-logger' ), 'manage_options', 'login-log', [ $this, 'mgmt_menu_page' ] );
		$hook && add_action( 'load-' . $hook, [ $this, 'remove_extra_args' ] );
		$hook = add_users_page( __( 'Login History', 'login-logger' ), __( 'Login History', 'login-logger' ), 'level_0', 'login-history', [ $this, 'user_menu_page' ] );
		$hook && add_action( 'load-' . $hook, [ $this, 'remove_extra_args' ] );
	}

	/**
	 * @param string $hook
	 */
	public function admin_enqueue_scripts( $hook ): void {
		if ( 'user-edit.php' === $hook || 'profile.php' === $hook ) {
			wp_enqueue_script( 'wwa-login-logger-profile', plugins_url( '/assets/profile.min.js', dirname( __DIR__ ) . '/plugin.php' ), [ 'jquery', 'wp-api-request' ], '2021082202', true );
		}
	}

	/**
	 * @param string[] $actions
	 * @return string[]
	 */
	public function user_row_actions( array $actions, WP_User $user ): array {
		$link                     = get_edit_user_link( $user->ID ) . '#user-sessions';
		$actions['login-history'] = '<a href="' . $link . '">' . __( 'Sessions', 'login-logger' ) . '</a>';
		return $actions;
	}

	/**
	 * @psalm-suppress UnusedParam
	 * @param string $view
	 * @param mixed[] $params
	 * @return void
	 */
	private static function render( string $view, array $params = [] ): void {
		/** @psalm-suppress UnresolvableInclude */
		require __DIR__ . '/../views/' . $view . '.php';
	}

	public function mgmt_menu_page(): void {
		/** @var string */
		$user = filter_input( INPUT_GET, 'user', FILTER_DEFAULT, [ 'options' => [ 'default' => '' ] ] ); // phpcs:disable WordPressVIPMinimum.Security.PHPFilterFunctions.RestrictedFilter
		/** @var string */
		$ip = filter_input( INPUT_GET, 'ip', FILTER_VALIDATE_IP, [ 'options' => [ 'default' => '' ] ] );
		self::render( 'logins', [
			'user' => $user,
			'ip'   => $ip,
		] );
	}

	public function user_menu_page(): void {
		self::render( 'history' );
	}

	public function remove_extra_args(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['_wp_http_referer'] ) ) {
			/** @var string $url */
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$url = empty( $_SERVER['REQUEST_URI'] ) ? admin_url() : wp_unslash( (string) $_SERVER['REQUEST_URI'] );
			// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			wp_redirect( remove_query_arg( [ '_wp_http_referer', '_wpnonce' ], $url ) );
			exit();
		}
	}

	public function show_user_profile( WP_User $user ): void {
		$last = Plugin::get_last_login_date( $user->ID );

		if ( -1 === $last ) {
			$last = __( 'N/A', 'login-logger' );
		} else {
			$last = DateTimeUtils::format_date_time( $last );
		}

		$params = [
			'user_id' => $user->ID,
			'last'    => $last,
		];

		self::render( 'sessions', $params );
	}
}
