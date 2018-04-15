<?php
/*
 * Plugin Name: WW Login Logger
 * Plugin URI: https://github.com/sjinks/wp-login-logger
 * Description: WordPress plugin to log login attempts
 * Version: 1.1.1
 * Author: Volodymyr Kolesnykov
 * License: MIT
 * Text Domain: login-logger
 * Domain Path: /lang
 */

defined('ABSPATH') || die();

if (defined('VENDOR_PATH')) {
	require VENDOR_PATH . '/vendor/autoload.php';
}
elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require __DIR__ . '/vendor/autoload.php';
}
elseif (file_exists(ABSPATH . 'vendor/autoload.php')) {
	require ABSPATH . 'vendor/autoload.php';
}

WildWolf\LoginLogger\Plugin::instance();
