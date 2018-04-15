<?php
namespace WildWolf\LoginLogger;

final class Admin
{
	public static function instance()
	{
		static $self = null;
		if (!$self) {
			$self = new self();
		}

		return $self;
	}

	private function __construct()
	{
		$this->init();
	}

	public function init()
	{
		\load_plugin_textdomain('login-logger', /** @scrutinizer ignore-type */ false, \plugin_basename(\dirname(__DIR__)) . '/lang/');

		\add_action('admin_menu', [$this, 'admin_menu']);
	}

	public function admin_menu()
	{
		\add_management_page(\__('Login Log', 'login-logger'), \__('Login Log', 'login-logger'), 'manage_options', 'login-log', [$this, 'mgmt_menu_page']);
		\add_users_page(\__('Login History', 'login-logger'), \__('Login History', 'login-logger'), 'level_0', 'login-history', [$this, 'user_menu_page']);
	}

	public function mgmt_menu_page()
	{
		if (!\current_user_can('manage_options')) {
			return;
		}

		require __DIR__ . '/../views/logins.php';
	}

	public function user_menu_page()
	{
		require __DIR__ . '/../views/history.php';
	}
}
