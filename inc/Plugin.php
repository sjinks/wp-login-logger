<?php
declare(strict_types=1);

namespace WildWolf\LoginLogger;

final class Plugin
{
	/**
	 * @var int
	 */
	private $_record_id = 0;

	/**
	 * @var int
	 */
	public static $db_version = 1;

	public static function instance(): self
	{
		/** @var self|null $self */
		static $self = null;
		if ($self === null) {
			$self = new self();
		}

		return $self;
	}

	private function __construct()
	{
		$basename = \plugin_basename(\dirname(__DIR__) . '/plugin.php');

		\add_action('init',                  [$this, 'init']);
		\add_action('activate_' . $basename, [$this, 'maybeUpdateSchema']);
	}

	public function init(): void
	{
		\load_plugin_textdomain('login-logger', /** @scrutinizer ignore-type */ false, \plugin_basename(\dirname(__DIR__)) . '/lang/');

		\add_action('wp_login',          [$this, 'wp_login'],        9999, 2);
		\add_action('wp_login_failed',   [$this, 'wp_login_failed'], 0, 1);
		\add_filter('authenticate',      [$this, 'authenticate'],    0, 3);
		\add_action('admin_bar_menu',    [$this, 'admin_bar_menu']);
		\add_action('login_form_logout', [$this, 'login_form_logout']);

		if (\is_admin()) {
			Admin::instance();
		}

		$this->maybeUpdateSchema();
	}

	private function log(string $login, int $user_id, int $outcome): void
	{
		/** @var \wpdb $wpdb */
		global $wpdb;

		/** @var string */
		$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

		$wpdb->insert($wpdb->prefix . 'login_log', [
			'ip'       => \inet_pton($ip),
			'dt'       => \time(),
			'username' => $login,
			'user_id'  => $user_id,
			'outcome'  => $outcome
		]);

		$this->_record_id = $wpdb->insert_id;
	}

	/**
	 * @param mixed $result
	 * @param string $login
	 * @param string $password
	 * @return mixed
	 */
	public function authenticate($result, $login, /** @scrutinizer ignore-unused */ $password)
	{
		if (!empty($login)) {
			/** @psalm-suppress RedundantCastGivenDocblockType */
			$this->log((string)$login, 0, -1);
		}

		return $result;
	}

	/**
	 * @param string $login
	 * @param \WP_User $user
	 * @return void
	 */
	public function wp_login($login, \WP_User $user): void
	{
		if ($this->_record_id > 0) {
			/** @psalm-suppress RedundantCastGivenDocblockType */
			$this->log((string)$login, $user->ID, 1);
			$this->_record_id = 0;
		}
	}

	/**
	 * @param string $login
	 * @return void
	 */
	public function wp_login_failed($login): void
	{
		if (!empty($login)) {
			/** @psalm-suppress RedundantCastGivenDocblockType */
			$this->log((string)$login, 0, 0);
			$this->_record_id = 0;
		}
	}

	public function admin_bar_menu(\WP_Admin_Bar $wp_admin_bar): void
	{
		$wp_admin_bar->add_menu([
			'parent' => 'user-actions',
			'id'     => 'logout-everywhere',
			'title'  => \__('Log Out Everywhere', 'login-logger'),
			'href'   => \add_query_arg(['everywhere' => 1], \wp_logout_url()),
		]);
	}

	public function login_form_logout(): void
	{
		if (!empty($_GET['everywhere'])) {
			\check_admin_referer('log-out');
			\wp_destroy_other_sessions();
		}
	}

	public function maybeUpdateSchema(): void
	{
		$ver = (int)\get_option('ww_login_logger_dbver', 0);
		if ($ver < self::$db_version) {
			new Installer();
		}
	}

	public static function getLastLoginDate(int $user): int
	{
		/** @var \wpdb $wpdb */
		global $wpdb;

		/** @var string */
		$query = $wpdb->prepare("SELECT dt FROM {$wpdb->prefix}login_log WHERE user_id = %d AND outcome = 1 ORDER BY dt DESC LIMIT 1", $user);
		$dt = $wpdb->get_var($query);
		return $dt !== null ? (int)$dt : -1;
	}
}
