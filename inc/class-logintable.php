<?php

namespace WildWolf\WordPress\LoginLogger;

use WP_List_Table;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-import-type LoginLogEntry from LoginQueries
 */
class LoginTable extends WP_List_Table {
	/** @var int|null */
	private $user_id;

	/**
	 * @param mixed[] $args
	 */
	public function __construct( $args = [] ) {
		$this->user_id = isset( $args['user_id'] ) ? (int) $args['user_id'] : null;
		unset( $args['user_id'] );

		parent::__construct(
			[
				'singular' => 'login-entry',
				'plural'   => 'login-entries',
			] + $args
		);
	}

	/**
	 * @return void
	 */
	public function prepare_items() {
		$screen   = $this->screen;
		$paged    = $this->get_pagenum();
		$per_page = $this->get_items_per_page( str_replace( '-', '_', $screen->id . '_per_page' ), 20 );
		$offset   = ( $paged - 1 ) * $per_page;

		/** @var string|null */
		$ip = filter_input( INPUT_GET, 'ip', FILTER_VALIDATE_IP, [ 'flags' => FILTER_NULL_ON_FAILURE ] );
		/** @var int */
		$user = $this->user_id ?? filter_input( INPUT_GET, 'user', FILTER_VALIDATE_INT, [
			'options' => [
				'min_range' => 1,
				'default'   => 0,
			],
		] );

		$result      = LoginQueries::find_events( $ip, $user, $offset, $per_page );
		$this->items = $result['items'];

		$this->set_pagination_args(
			[
				'total_items' => $result['total'],
				'per_page'    => $per_page,
			]
		);
	}

	/**
	 * @return string[]
	 */
	public function get_columns() {
		return [
			'username' => __( 'Login', 'login-logger' ),
			'ip'       => __( 'IP Address', 'login-logger' ),
			'dt'       => __( 'Time', 'login-logger' ),
			'outcome'  => __( 'Outcome', 'login-logger' ),
		];
	}

	/**
	 * @psalm-suppress MoreSpecificImplementedParamType
	 * @param string[] $item
	 * @psalm-param LoginLogEntry $item
	 * @param string $column_name
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		return esc_html( $item[ $column_name ] );
	}

	/**
	 * @param string[] $item
	 * @psalm-param LoginLogEntry $item
	 * @return string
	 */
	protected function column_username( array $item ): string {
		$actions = [
			// translators: %s is the link to the user profile
			'view' => sprintf( __( '<a href="%s">Profile</a>' ), get_edit_user_link( (int) $item['user_id'] ) ),
		];

		$s = esc_html( $item['username'] );
		if ( ! empty( $item['user_id'] ) ) {
			$s .= $this->row_actions( $actions, false );
		}

		return $s;
	}

	/**
	 * @param string[] $item
	 * @psalm-param LoginLogEntry $item
	 * @return string
	 */
	protected function column_dt( array $item ): string {
		return DateTimeUtils::format_date_time( (int) $item['dt'] );
	}

	/**
	 * @param string[] $item
	 * @psalm-param LoginLogEntry $item
	 * @return string
	 */
	protected function column_outcome( array $item ): string {
		$lut = [
			LogEntry::OUTCOME_ATTEMPTED => __( 'Login attempt', 'login-logger' ),
			LogEntry::OUTCOME_FAILED    => __( 'Login failed', 'login-logger' ),
			LogEntry::OUTCOME_SUCCEEDED => __( 'Login OK', 'login-logger' ),
		];

		return $lut[ (int) $item['outcome'] ] ?? $item['outcome'];
	}
}
