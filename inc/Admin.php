<?php
declare(strict_types=1);

namespace WildWolf\LoginLogger;

final class Admin
{
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
		$this->init();
	}

	public function init(): void
	{
		\add_action('admin_menu', [$this, 'admin_menu']);

		if (\current_user_can('manage_options')) {
			\add_action('show_user_profile', [$this, 'show_user_profile'], 0, 1);
		}

		\add_action('edit_user_profile',             [$this, 'show_user_profile'], 0, 1);
		\add_action('admin_enqueue_scripts',         [$this, 'admin_enqueue_scripts']);
		\add_action('wp_ajax_wwall-destroy-session', [$this, 'wp_ajax_wwall_destroy_session']);

		if (\current_user_can('manage_options')) {
			\add_filter('user_row_actions', [$this, 'user_row_actions'], 10, 2);
		}
	}

	public function admin_menu(): void
	{
		\add_management_page(\__('Login Log', 'login-logger'), \__('Login Log', 'login-logger'), 'manage_options', 'login-log', [$this, 'mgmt_menu_page']);
		\add_users_page(\__('Login History', 'login-logger'), \__('Login History', 'login-logger'), 'level_0', 'login-history', [$this, 'user_menu_page']);
	}

	/**
	 * @param string $hook
	 */
	public function admin_enqueue_scripts($hook): void
	{
		if ('user-edit.php' === $hook || 'profile.php' === $hook) {
			\wp_enqueue_script('wwa-login-logger-profile', \plugins_url('/assets/profile.min.js', \dirname(__DIR__) . '/plugin.php'), ['jquery'], '2019031100', true);
		}
	}

	/**
	 * @param array<string,string> $actions
	 * @param \WP_User $user
	 * @return array<string,string>
	 */
	public function user_row_actions(array $actions, \WP_User $user): array
	{
		$link = \get_edit_user_link($user->ID) . '#user-sessions';
		$actions['login-history'] = '<a href="' . $link . '">' . \__('Sessions', 'login-logger') . '</a>';
		return $actions;
	}

	/**
	 * @psalm-suppress UnusedParam
	 * @param string $view
	 * @param mixed[] $params
	 * @return void
	 */
	private static function render(string $view, array $params = []): void
	{
		/** @psalm-suppress UnresolvableInclude */
		require __DIR__ . '/../views/' . $view . '.php';
	}

	public function mgmt_menu_page(): void
	{
		if (!\current_user_can('manage_options')) {
			return;
		}

		self::render('logins');
	}

	public function user_menu_page(): void
	{
		self::render('history');
	}

	public function show_user_profile(\WP_User $user): void
	{
		$last = Plugin::getLastLoginDate($user->ID);

		if (-1 == $last) {
			$last = \__('N/A', 'login-logger');
		}
		else {
			$last = self::formatDateTime($last);
		}

		$params = [
			'user_id' => $user->ID,
			'last'    => $last,
		];

		self::render('sessions', $params);
	}

	public function wp_ajax_wwall_destroy_session(): void
	{
		$user  = \get_userdata((int)($_POST['uid'] ?? -1));
		/** @var string */
		$nonce = $_POST['nonce'] ?? '';
		/** @var string */
		$token = $_POST['token'] ?? '';
		if ($user !== false && (!\current_user_can('edit_user', $user->ID) || \wp_verify_nonce($nonce, 'destroy_session-' . $token) === false)) {
			$user = false;
		}

		if ($user === false) {
			\wp_send_json_error(['message' => \__('Could not terminate the session. Please try again.', 'login-logger')]);
		}

		/** @var \WP_User $user */
		$manager    = \WP_Session_Tokens::get_instance($user->ID);
		$reflection = new \ReflectionClass($manager);
		$method     = $reflection->getMethod('update_session');
		$method->setAccessible(true);
		$method->invoke($manager, $token, null);

		\wp_send_json_success(['message' => \__('Session has been terminated.', 'login-logger')]);
	}

	public static function formatDateTime(int $dt) : string
	{
		$date_format = (string)\get_option('date_format');
		$time_format = (string)\get_option('time_format');
		return \date_i18n($date_format . ' ' . $time_format, $dt);
	}
}
