<?php

namespace WildWolf\LoginLogger;

require_once ABSPATH . '/wp-admin/includes/upgrade.php';

class Installer
{
    public function __construct()
    {
        $ver = get_option('ww_login_logger_dbver', 0);

        if ($ver < 1) {
            $this->install();
        }
    }

    private function install()
    {
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

        dbDelta($sql);

        update_option('ww_login_logger_dbver', 1);
    }
}
