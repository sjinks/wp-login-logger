<?php
/*
 * Plugin Name: Login Logger
 * Plugin URI: https://github.com/sjinks/wp-login-logger
 * Description: WordPress plugin to log login attempts
 * Version: 1.0.0
 * Author: Volodymyr Kolesnykov
 * License: MIT
 * Text Domain: login-logger
 * Domain Path: /lang
 */

namespace WildWolf;

class LoginLogger
{
    private $_record_id = null;

    public static $db_version = 1;

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
        $this->_record_id = null;

        add_action('plugins_loaded', [$this, 'plugins_loaded']);
        add_action('init',           [$this, 'init']);

        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function plugins_loaded()
    {
        if (empty($_SERVER['REMOTE_ADDR'])) {
            $_SERVER['REMOTE_ADDR'] = '0.0.0.0';
        }

        load_plugin_textdomain('login-logger', false, substr(__DIR__, strlen(\WP_PLUGIN_DIR) + 1) . '/lang/');
    }

    public function init()
    {
        add_action('wp_login',        [$this, 'wp_login'],        9999, 2);
        add_action('wp_login_failed', [$this, 'wp_login_failed'], 0, 1);
        add_filter('authenticate',    [$this, 'authenticate'],    0, 3);

        if (is_admin()) {
            add_action('admin_menu', [$this, 'admin_menu']);
        }

        $this->maybeUpdateSchema();
    }

    private static function log($login, $user_id, $outcome)
    {
        global $wpdb;
        $ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
        $wpdb->insert($wpdb->prefix . 'login_log', [
            'ip'       => $ip,
            'dt'       => time(),
            'username' => $login,
            'user_id'  => $user_id,
            'outcome'  => $outcome
        ]);
    }

    public function authenticate($result, $login, $password)
    {
        if ($login) {
            global $wpdb;
            self::log($login, 0, -1);
            $this->_record_id = $wpdb->insert_id;
        }

        return $result;
    }

    public function wp_login($login, \WP_User $user)
    {
        if ($this->_record_id > 0) {
            self::log($login, $user->ID, 1);
            $this->_record_id = 0;
        }
    }

    public function wp_login_failed($login)
    {
        if ($login) {
            self::log($login, 0, 0);
        }
    }

    public function admin_menu()
    {
        $hook = add_management_page(__('Login Log', 'login-logger'), __('Login Log', 'login-logger'), 'manage_options', 'psb-login-log', [$this, 'menu_page']);
        add_action('load-' . $hook, [$this, 'load_page_common']);
    }

    public function load_page_common()
    {
        $uri = filter_input(INPUT_SERVER, 'REQUEST_URI');
        if (isset($_GET['_wp_http_referer'])) {
            wp_redirect(
                remove_query_arg(
                    ['_wp_http_referer', '_wpnonce'],
                    wp_unslash($uri)
                )
            );

            exit();
        }
    }

    public function menu_page()
    {
        require __DIR__ . '/inc/logintable.php';
        require __DIR__ . '/views/logins.php';
    }

    private function maybeUpdateSchema()
    {
        $ver = get_option('ww_login_logger_dbver', 0);
        if ($ver < self::$db_version) {
            require_once __DIR__ . '/inc/installer.php';
            new LoginLogger\Installer();
        }
    }

    public function activate()
    {
        require_once __DIR__ . '/inc/installer.php';
        new LoginLogger\Installer();
    }
}

if (defined('ABSPATH')) {
    LoginLogger::instance();
}
