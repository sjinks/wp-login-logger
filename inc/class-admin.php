<?php

namespace WildWolf\WordPress\LoginLogger;

use WildWolf\Utils\Singleton;
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
			add_action( 'personal_options_update', [ $this, 'edit_user_profile_update' ] );
			add_filter( 'user_row_actions', [ $this, 'user_row_actions' ], 10, 2 );
		}

		add_action( 'edit_user_profile', [ $this, 'show_user_profile' ], 0, 1 );
		add_action( 'edit_user_profile_update', [ $this, 'edit_user_profile_update' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

		add_filter( 'set_screen_option_tools_page_login_log_per_page', [ $this, 'save_per_page_option' ], 10, 3 );
		add_filter( 'set_screen_option_users_page_login_history_per_page', [ $this, 'save_per_page_option' ], 10, 3 );
	}

	public function admin_menu(): void {
		$hook = (string) add_management_page( __( 'Login Log', 'login-logger' ), __( 'Login Log', 'login-logger' ), 'manage_options', 'login-log', [ $this, 'mgmt_menu_page' ] );
		if ( $hook ) {
			add_action( 'load-' . $hook, [ $this, 'remove_extra_args' ] );
			add_action( 'load-' . $hook, [ $this, 'load_login_log_page' ] );
		}

		$hook = (string) add_users_page( __( 'Login History', 'login-logger' ), __( 'Login History', 'login-logger' ), 'read', 'login-history', [ $this, 'user_menu_page' ] );
		if ( $hook ) {
			add_action( 'load-' . $hook, [ $this, 'remove_extra_args' ] );
			add_action( 'load-' . $hook, [ $this, 'load_login_history_page' ] );
		}
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
	private static function render( string $view, array $params = [] ): void /* NOSONAR */ { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		/** @psalm-suppress UnresolvableInclude */
		require __DIR__ . '/../views/' . $view . '.php'; // NOSONAR
	}

	public function remove_extra_args(): void {
		/** @psalm-suppress RiskyTruthyFalsyComparison */
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['_wp_http_referer'] ) ) {
			/** @psalm-suppress RedundantCondition, TypeDoesNotContainType, RiskyTruthyFalsyComparison */
			$url = ! empty( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] ) ? wp_sanitize_redirect( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : admin_url();
			wp_safe_redirect( remove_query_arg( [ '_wp_http_referer', '_wpnonce' ], $url ) );
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

		$user = (string) filter_input( INPUT_GET, 'user', FILTER_DEFAULT ); // phpcs:disable WordPressVIPMinimum.Security.PHPFilterFunctions.RestrictedFilter
		$ip   = filter_input( INPUT_GET, 'ip', FILTER_VALIDATE_IP, [ 'options' => [ 'default' => '' ] ] );
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
	 * @param int $user_id
	 */
	public function edit_user_profile_update( $user_id ): void {
		check_admin_referer( 'update-user_' . $user_id );
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to edit this user.' ) );
		}

		/** @psalm-suppress RiskyTruthyFalsyComparison */
		if ( ! empty( $_POST['loginlogger'] ) && is_array( $_POST['loginlogger'] ) ) {
			$options = [
				'default'   => 0,
				'min_range' => 0,
				'max_range' => 1,
			];

			$sln = filter_var( $_POST['loginlogger']['sln'] ?? 0, FILTER_VALIDATE_INT, [ 'options' => $options ] );
			$uln = filter_var( $_POST['loginlogger']['uln'] ?? 0, FILTER_VALIDATE_INT, [ 'options' => $options ] );

			update_user_meta( $user_id, 'successful_login_notification', $sln );
			update_user_meta( $user_id, 'unsuccessful_login_notification', $uln );
		}
	}

	/**
	 * @param mixed $_screen_option
	 * @param string $_option
	 * @param scalar $value
	 */
	public function save_per_page_option( $_screen_option, $_option, $value ): int {
		$v = (int) $value;
		if ( $v < 1 ) {
			$v = 10;
		} elseif ( $v > 100 ) {
			$v = 100;
		}

		return $v;
	}
}
