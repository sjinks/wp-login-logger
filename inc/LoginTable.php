<?php
namespace WildWolf\LoginLogger;

class LoginTable extends \WP_List_Table
{
	public function __construct($args = array())
	{
		parent::__construct([
			'singular' => \__('Record', 'login-logger'),
			'plural'   => \__('Records', 'login-logger'),
			'screen'   => $args['screen'] ?? null
		]);
	}

	private function buildSearchQuery() : array
	{
		global $wpdb;
		$query    = "SELECT username, user_id, INET6_NTOA(ip) AS ip, dt, outcome FROM {$wpdb->prefix}login_log";
		$total    = "SELECT COUNT(*) FROM {$wpdb->prefix}login_log";
		$where    = ['1=1'];

		$ip = $_GET['ip'] ?? '';
		if ($ip) {
			$where[] = \sprintf("INET6_NTOA(ip) = '%s'", $ip);
		}

		$user = $_GET['user'] ?? '';
		if ($user) {
			$where[] = \sprintf("user_id = '%u'", $user);
		}

		$where  = \join(' AND ', $where);
		$query .= ' WHERE ' . $where . ' ORDER BY id DESC';
		$total .= ' WHERE ' . $where;

		return [$total, $query];
	}

	public function prepare_items()
	{
		global $wpdb;
		list($total, $query) = $this->buildSearchQuery();

		$paged       = $this->get_pagenum();
		$per_page    = $this->get_items_per_page('psb_login_log');
		$query      .= ' LIMIT ' . ($paged - 1) * $per_page . ', ' . $per_page;

		$total_items = $wpdb->get_var($total);
		$this->items = $total_items ? $wpdb->get_results($query) : [];

		$this->set_pagination_args([
			'total_items' => $total_items,
			'per_page'    => $per_page
		]);
	}

	public function get_columns() : array
	{
		return [
			'username' => \__('Login', 'login-logger'),
			'ip'       => \__('IP Address', 'login-logger'),
			'dt'       => \__('Time', 'login-logger'),
			'outcome'  => \__('Outcome', 'login-logger'),
		];
	}

	protected function column_default($item, $column_name)
	{
		return \esc_html($item->$column_name);
	}

	/**
	 * @param object $item
	 * @return string
	 */
	protected function column_username($item) : string
	{
		$actions = [
			'view' => \sprintf(\__('<a href="%s">Profile</a>'), \get_edit_user_link($item->user_id))
		];

		$s = \esc_html($item->username);
		if ($item->user_id) {
			$s .= $this->row_actions($actions, false);
		}

		return $s;
	}

	/**
	 * @param object $item
	 * @return string
	 */
	protected function column_dt($item) : string
	{
		return Admin::formatDateTime($item->dt);
	}

	/**
	 * @param object $item
	 * @return string
	 */
	protected function column_outcome($item) : string
	{
		$lut = [
			-1 => \__('Login attempt', 'login-logger'),
			 0 => \__('Login failed', 'login-logger'),
			 1 => \__('Login OK', 'login-logger'),
		];

		$s = $lut[$item->outcome] ?? $item->outcome;
		return $s;
	}
}
