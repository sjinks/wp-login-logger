<?php

namespace WildWolf\WordPress\LoginLogger;

use WP_List_Table;
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

		add_filter( 'set_screen_option_tools_page_login_log_per_page', [ $this, 'save_per_page_option' ], 10, 3 );
		add_filter( 'set_screen_option_users_page_login_history_per_page', [ $this, 'save_per_page_option' ], 10, 3 );
	}

	public function admin_menu(): void {
		$hook = add_management_page( __( 'Login Log', 'login-logger' ), __( 'Login Log', 'login-logger' ), 'manage_options', 'login-log', [ $this, 'mgmt_menu_page' ] );
		$hook && add_action( 'load-' . $hook, [ $this, 'remove_extra_args' ] );
		$hook && add_action( 'load-' . $hook, [ $this, 'load_login_log_page' ] );
		$hook = add_users_page( __( 'Login History', 'login-logger' ), __( 'Login History', 'login-logger' ), 'level_0', 'login-history', [ $this, 'user_menu_page' ] );
		$hook && add_action( 'load-' . $hook, [ $this, 'remove_extra_args' ] );
		$hook && add_action( 'load-' . $hook, [ $this, 'load_login_history_page' ] );
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
	private static function render( string $view, array $params = [] ): void /* NOSONAR */ {
		/** @psalm-suppress UnresolvableInclude */
		require __DIR__ . '/../views/' . $view . '.php';
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

	/**
	 * @global WP_List_Rable $wp_list_table
	 */
	public function load_login_log_page(): void {
		global $wp_list_table;

		add_screen_option( 'per_page' );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_list_table = new LoginTable();
	}

	/**
	 * @global WP_List_Table $wp_list_table
	 */
	public function load_login_history_page(): void {
		global $wp_list_table;

		add_screen_option( 'per_page' );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_list_table = new LoginTable( [ 'user_id' => wp_get_current_user()->ID ] );
	}

	/**
	 * @global WP_List_Table $wp_list_table
	 */
	public function mgmt_menu_page(): void {
		global $wp_list_table;

		/** @var string */
		$user = filter_input( INPUT_GET, 'user', FILTER_DEFAULT, [ 'options' => [ 'default' => '' ] ] ); // phpcs:disable WordPressVIPMinimum.Security.PHPFilterFunctions.RestrictedFilter
		/** @var string */
		$ip = filter_input( INPUT_GET, 'ip', FILTER_VALIDATE_IP, [ 'options' => [ 'default' => '' ] ] );
		self::render( 'logins', [
			'user'  => $user,
			'ip'    => $ip,
			'table' => $wp_list_table,
		] );
	}

	/**
	 * @global WP_List_Table $wp_list_table
	 */
	public function user_menu_page(): void {
		global $wp_list_table;

		self::render( 'history', [ 'table' => $wp_list_table ] );
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

	/**
	 * @param mixed $screen_option
	 * @param string $option
	 * @param scalar $value
	 */
	public function save_per_page_option( $screen_option, $option, $value ): int {
		$v = (int) $value;
		if ( $v < 1 ) {
			$v = 10;
		} elseif ( $v > 100 ) {
			$v = 100;
		}

		return $v;
	}
}
