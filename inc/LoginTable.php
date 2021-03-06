<?php
declare(strict_types=1);

namespace WildWolf\LoginLogger;

class LoginTable extends \WP_List_Table
{
	/**
	 * @param mixed[] $args
	 */
	public function __construct($args = array())
	{
		parent::__construct([
			'singular' => \__('Record', 'login-logger'),
			'plural'   => \__('Records', 'login-logger'),
			'screen'   => $args['screen'] ?? null
		]);
	}

	/**
	 * @return array{string,string}
	 */
	private function buildSearchQuery(): array
	{
		/** @var \wpdb $wpdb */
		global $wpdb;
		$query = "SELECT username, user_id, INET6_NTOA(ip) AS ip, dt, outcome FROM {$wpdb->prefix}login_log";
		$total = "SELECT COUNT(*) FROM {$wpdb->prefix}login_log";
		$where = ['1=1'];

		$ip = $_GET['ip'] ?? '';
		if ($ip) {
			$where[] = $wpdb->prepare("INET6_NTOA(ip) = %s", $ip);
		}

		$user = $_GET['user'] ?? '';
		if ($user) {
			$where[] = $wpdb->prepare("user_id = %u", $user);
		}

		$where  = \join(' AND ', $where);
		$query .= ' WHERE ' . $where . ' ORDER BY id DESC';
		$total .= ' WHERE ' . $where;

		return [$total, $query];
	}

	/**
	 * @return void
	 */
	public function prepare_items()
	{
		/** @var \wpdb $wpdb */
		global $wpdb;
		list($total, $query) = $this->buildSearchQuery();

		$paged       = $this->get_pagenum();
		$per_page    = $this->get_items_per_page('psb_login_log');
		$query      .= ' LIMIT ' . ($paged - 1) * $per_page . ', ' . $per_page;

		$total_items = (int)$wpdb->get_var($total);
		/** @var string[] */
		$items = $total_items !== 0 ? $wpdb->get_results($query, \ARRAY_A) : [];
		$this->items = $items;

		$this->set_pagination_args([
			'total_items' => $total_items,
			'per_page'    => $per_page
		]);
	}

	/**
	 * @return array<string,string>
	 */
	public function get_columns()
	{
		return [
			'username' => \__('Login', 'login-logger'),
			'ip'       => \__('IP Address', 'login-logger'),
			'dt'       => \__('Time', 'login-logger'),
			'outcome'  => \__('Outcome', 'login-logger'),
		];
	}

	/**
	 * @psalm-suppress MoreSpecificImplementedParamType
	 * @param string[] $item
	 * @param string $column_name
	 * @return string
	 */
	protected function column_default($item, $column_name)
	{
		return \esc_html($item[$column_name]);
	}

	/**
	 * @param string[] $item
	 * @return string
	 */
	protected function column_username(array $item): string
	{
		$actions = [
			'view' => \sprintf(\__('<a href="%s">Profile</a>'), \get_edit_user_link((int)$item['user_id']))
		];

		$s = \esc_html($item['username']);
		if (!empty($item['user_id'])) {
			$s .= $this->row_actions($actions, false);
		}

		return $s;
	}

	/**
	 * @param string[] $item
	 * @return string
	 */
	protected function column_dt(array $item): string
	{
		return Admin::formatDateTime((int)$item['dt']);
	}

	/**
	 * @param string[] $item
	 * @return string
	 */
	protected function column_outcome(array $item): string
	{
		$lut = [
			-1 => \__('Login attempt', 'login-logger'),
			 0 => \__('Login failed', 'login-logger'),
			 1 => \__('Login OK', 'login-logger'),
		];

		return $lut[(int)$item['outcome']] ?? $item['outcome'];
	}
}
