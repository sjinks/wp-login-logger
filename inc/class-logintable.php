<?php

namespace WildWolf\WordPress\LoginLogger;

use WP_List_Table;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class LoginTable extends WP_List_Table {

	/**
	 * @param mixed[] $args
	 */
	public function __construct( $args = [] ) {
		parent::__construct(
			[
				'singular' => __( 'Record', 'login-logger' ),
				'plural'   => __( 'Records', 'login-logger' ),
				'screen'   => $args['screen'] ?? null,
			]
		);
	}

	/**
	 * @return string[]
	 * @global \wpdb $wpdb
	 */
	private function buildSearchQuery(): array {
		/** @var \wpdb $wpdb */
		global $wpdb;
		$query = "SELECT username, user_id, INET6_NTOA(ip) AS ip, dt, outcome FROM {$wpdb->prefix}login_log";
		$total = "SELECT COUNT(*) FROM {$wpdb->prefix}login_log";
		$where = [ '1=1' ];

		/** @var false|string */
		$ip = filter_input( INPUT_GET, 'ip', FILTER_VALIDATE_IP );
		if ( ! empty( $ip ) ) {
			$where[] = $wpdb->prepare( 'INET6_NTOA(ip) = %s', $ip );
		}

		/** @var int */
		$user = filter_input( INPUT_GET, 'user', FILTER_VALIDATE_INT, [
			'options' => [
				'min_range' => 1,
				'default'   => 0,
			],
		] );
		if ( ! empty( $user ) ) {
			$where[] = $wpdb->prepare( 'user_id = %d', $user );
		}

		$where  = join( ' AND ', $where );
		$query .= ' WHERE ' . $where . ' ORDER BY id DESC';
		$total .= ' WHERE ' . $where;

		return [ $total, $query ];
	}

	/**
	 * @return void
	 * @global \wpdb $wpdb
	 */
	public function prepare_items() {
		/** @var \wpdb $wpdb */
		global $wpdb;
		list($total, $query) = $this->buildSearchQuery();

		$paged    = $this->get_pagenum();
		$per_page = $this->get_items_per_page( 'psb_login_log' );
		$query   .= ' LIMIT ' . ( $paged - 1 ) * $per_page . ', ' . $per_page;

		$total_items = (int) $wpdb->get_var( $total ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		/** @var string[] */
		$items       = 0 !== $total_items ? $wpdb->get_results( $query, ARRAY_A ) : []; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->items = $items;

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
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
	 * @param string $column_name
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		return esc_html( $item[ $column_name ] );
	}

	/**
	 * @param string[] $item
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
	 * @return string
	 */
	protected function column_dt( array $item ): string {
		return DateTimeUtils::format_date_time( (int) $item['dt'] );
	}

	/**
	 * @param string[] $item
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
