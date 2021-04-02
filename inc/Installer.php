<?php
declare(strict_types=1);

namespace WildWolf\LoginLogger;

class Installer
{
	public function __construct()
	{
		$this->install();
	}

	private function install(): void
	{
		/** @var \wpdb $wpdb */
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}login_log (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		ip varbinary(16) NOT NULL,
		dt int(11) NOT NULL,
		username varchar(255) NOT NULL,
		user_id bigint(20) unsigned NOT NULL,
		outcome tinyint(4) NOT NULL,
		PRIMARY KEY  (id)
		) {$charset_collate};";

		/** @psalm-suppress UndefinedConstant, UnresolvableInclude */
		require_once ABSPATH . '/wp-admin/includes/upgrade.php';
		\dbDelta($sql);

		\update_option('ww_login_logger_dbver', 1);
    }
}
