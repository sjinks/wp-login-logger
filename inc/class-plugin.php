<?php

namespace WildWolf\WordPress\LoginLogger;

use WP_Admin_Bar;
use wpdb;

/**
 * @psalm-import-type Outcome from LogEntry
 */
final class Plugin {
	use Singleton;

	/**
	 * @codeCoverageIgnore Class instantiation happens during bootstrapping; `init` happens before tests are run
	 */
	private function __construct() {
		$basename = plugin_basename( dirname( __DIR__ ) . '/plugin.php' );

		add_action( 'init', [ $this, 'init' ] );
		add_action( 'activate_' . $basename, [ $this, 'maybe_update_schema' ] );
		add_action( 'plugins_loaded', [ $this, 'maybe_update_schema' ] );
	}

	/**
	 * @codeCoverageIgnore `init` happens before tests are run
	 */
	public function init(): void {
		load_plugin_textdomain( 'login-logger', false, plugin_basename( dirname( __DIR__ ) ) . '/lang/' );

		EventWatcher::instance();

		add_action( 'admin_bar_menu', [ $this, 'admin_bar_menu' ] );
		add_action( 'login_form_logout', [ $this, 'login_form_logout' ] );
		add_action( 'rest_api_init', [ RESTController::class, 'instance' ] );

		if ( is_admin() ) {
			Admin::instance();
		}

		$this->maybe_update_schema();
	}

	/**
	 * @codeCoverageIgnore Not interesting method
	 */
	public function admin_bar_menu( WP_Admin_Bar $wp_admin_bar ): void {
		$wp_admin_bar->add_menu(
			[
				'parent' => 'user-actions',
				'id'     => 'logout-everywhere',
				'title'  => __( 'Log Out Everywhere', 'login-logger' ),
				'href'   => add_query_arg( [ 'everywhere' => 1 ], wp_logout_url() ),
			]
		);
	}

	public function login_form_logout(): void {
		if ( ! empty( $_GET['everywhere'] ) ) {
			check_admin_referer( 'log-out' );
			wp_destroy_other_sessions();
		}
	}

	/**
	 * @codeCoverageIgnore `plugins_loaded` happens before tests are run
	 */
	public function maybe_update_schema(): void {
		$schema = Schema::instance();
		if ( $schema->is_update_needed() ) {
			$schema->update();
		}
	}

	/**
	 * @global wpdb $wpdb
	 */
	public static function get_last_login_date( int $user ): int {
		/** @var wpdb $wpdb */
		global $wpdb;

		$key   = 'last_login_user_' . $user;
		$group = 'login_logger';

		/** @var false|scalar */
		$dt = wp_cache_get( $key, $group );
		if ( false === $dt ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$dt = $wpdb->get_var( $wpdb->prepare( "SELECT dt FROM {$wpdb->login_log_table} WHERE user_id = %d AND outcome = 1 ORDER BY dt DESC LIMIT 1", $user ) );
			$dt = null !== $dt ? (int) $dt : -1;
			wp_cache_set( $key, $dt, $group, 30 );
		} else {
			$dt = (int) $dt;
		}

		return $dt;
	}
}
