<?php
declare(strict_types=1);

namespace WildWolf\LoginLogger;

class SessionTable extends \WP_List_Table
{
	/**
	 * @var int
	 */
	private $user_id;

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

		$this->user_id = $args['user_id'] ?? \get_current_user_id();
	}

	/**
	 * @return void
	 */
	public function prepare_items()
	{
		$manager    = \WP_Session_Tokens::get_instance($this->user_id);
		$reflection = new \ReflectionClass($manager);
		$method     = $reflection->getMethod('get_sessions');
		$method->setAccessible(true);
		$sessions   = $method->invoke($manager);

		$items    = [];
		foreach ($sessions as $verifier => $session) {
			$items[] = [
				'verifier'   => $verifier,
				'login'      => $session['login'],
				'expiration' => $session['expiration'],
				'ip'         => $session['ip'] ?? '',
				'ua'         => $session['ua'] ?? '',
			];
		}

		$this->items = $items;
		$this->set_pagination_args([
			'total_items' => \count($items),
			'per_page'    => \count($items),
		]);
	}

	/**
	 * @return array<string,string>
	 */
	public function get_columns()
	{
		return [
			'login'      => \__('Created', 'login-logger'),
			'expiration' => \__('Expires', 'login-logger'),
			'ip'         => \__('IP Address', 'login-logger'),
			'ua'         => \__('User Agent', 'login-logger'),
			'kill'       => '',
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
	protected function column_login(array $item): string
	{
		return \esc_html($item['login']);
	}

	/**
	 * @param string[] $item
	 * @return string
	 */
	protected function column_expiration(array $item): string
	{
		return Admin::formatDateTime((int)$item['expiration']);
	}

	/**
	 * @param string[] $item
	 * @return string
	 */
	protected function column_kill(array $item): string
	{
		return sprintf(
			'<button type="button" class="button hide-if-no-js destroy-session" data-token="%1$s" data-nonce="%2$s">%3$s</button>',
			\esc_attr($item['verifier']),
			\wp_create_nonce('destroy_session-' . $item['verifier']),
			\__('Log Out', 'login-logger')
		);
	}

	/**
	 * @param string $which
	 * @return void
	 */
	protected function display_tablenav(/** @scrutinizer ignore-unused */ $which)
	{
		// Intentionally empty
	}
}
