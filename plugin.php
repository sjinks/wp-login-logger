<?php
/*
 * Plugin Name: WW Login Logger
 * Plugin URI: https://github.com/sjinks/wp-login-logger
 * Description: WordPress plugin to log login attempts
 * Version: 2.1.0
 * Author: Volodymyr Kolesnykov
 * License: MIT
 * Text Domain: login-logger
 * Domain Path: /lang
 */

// @codeCoverageIgnoreStart

use Composer\Autoload\ClassLoader;
use WildWolf\WordPress\LoginLogger\Plugin;

if ( defined( 'ABSPATH' ) ) {
	if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		/** @var ClassLoader */
		$loader = require __DIR__ . '/vendor/autoload.php';
	} elseif ( file_exists( ABSPATH . 'vendor/autoload.php' ) ) {
		/** @var ClassLoader */
		$loader = require ABSPATH . 'vendor/autoload.php';
	} else {
		return;
	}

	$loader->addClassMap( [
		WP_List_Table::class => ABSPATH . 'wp-admin/includes/class-wp-list-table.php',
	] );

	Plugin::instance();
}
// @codeCoverageIgnoreEnd
