<?php

namespace WildWolf\WordPress\LoginLogger;

use WP_List_Table;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-import-type PreparedSessionUnformatted from AuthUtils
 */
class SessionTable extends WP_List_Table {
	/** @var int */
	private $user_id;

	/**
	 * @param mixed[] $args
	 */
	public function __construct( $args = [] ) {
		$this->user_id = (int) ( $args['user_id'] ?? get_current_user_id() );
		unset( $args['user_id'] );

		parent::__construct(
			[
				'singular' => 'session',
				'plural'   => 'sessions',
			] + $args
		);
	}

	/**
	 * @return void
	 */
	public function prepare_items() {
		$sessions = SessionManager::get_all( $this->user_id );
		/** @psalm-var PreparedSessionUnformatted[] */
		$items = array_map( [ AuthUtils::class, 'prepare_session_unformatted' ], array_keys( $sessions ), array_values( $sessions ) );

		$this->items = $items;
		$this->set_pagination_args(
			[
				'total_items' => count( $items ),
				'per_page'    => count( $items ),
			]
		);
	}

	/**
	 * @return string[]
	 */
	public function get_columns() {
		return [
			'login'      => __( 'Created', 'login-logger' ),
			'expiration' => __( 'Expires', 'login-logger' ),
			'ip'         => __( 'IP Address', 'login-logger' ),
			'ua'         => __( 'User Agent', 'login-logger' ),
			'kill'       => '',
		];
	}

	/**
	 * @psalm-suppress MoreSpecificImplementedParamType
	 * @param (string|int)[] $item
	 * @psalm-param PreparedSessionUnformatted $item
	 * @param string $column_name
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		return esc_html( (string) $item[ $column_name ] );
	}

	/**
	 * @param (string|int)[] $item
	 * @psalm-param PreparedSessionUnformatted $item
	 */
	protected function column_login( array $item ): string {
		return $this->format_time_column( $item['login'] );
	}

	/**
	 * @param (string|int)[] $item
	 * @psalm-param PreparedSessionUnformatted $item
	 */
	protected function column_expiration( array $item ): string {
		return $this->format_time_column( $item['expiration'] );
	}

	private function format_time_column( int $dt ): string {
		return sprintf(
			'<abbr title="%s">%s</abbr>',
			DateTimeUtils::format_date_time_full( $dt ),
			DateTimeUtils::format_date_time( $dt )
		);
	}

	/**
	 * @param (string|int)[] $item
	 * @psalm-param PreparedSessionUnformatted $item
	 */
	protected function column_kill( array $item ): string {
		return sprintf(
			'<button type="button" class="button hide-if-no-js destroy-session" data-token="%1$s">%2$s</button>',
			esc_attr( $item['verifier'] ),
			esc_html__( 'Log Out', 'login-logger' )
		);
	}

	/**
	 * @param string $_which
	 * @return void
	 */
	protected function display_tablenav( $_which ) {
		// Intentionally empty
	}
}
