<?php

/*
 * Plugin Name: WW Login Logger
 * Plugin URI: https://github.com/sjinks/wp-login-logger
 * Description: WordPress plugin to log login attempts
 * Version: 1.0.0
 * Author: Volodymyr Kolesnykov
 * License: MIT
 * Text Domain: login-logger
 * Domain Path: /lang
 */

if (defined('ABSPATH')) {
    require __DIR__ . '/inc/login-logger.php';
    $instance = WildWolf\LoginLogger::instance();
    register_activation_hook(__FILE__, [$instance, 'activate']);
    unset($instance);
}
