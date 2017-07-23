<?php
defined('WP_UNINSTALL_PLUGIN') || die();

delete_option('ww_login_logger_dbver');

global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}login_log");
