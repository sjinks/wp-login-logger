<?php

namespace WildWolf\LoginLogger;

require_once ABSPATH . '/wp-admin/includes/class-wp-list-table.php';

class LoginTable extends \WP_List_Table
{
    public function __construct($args = array())
    {
        parent::__construct([
            'singular' => __('Record', 'login-logger'),
            'plural'   => __('Records', 'login-logger'),
            'screen'   => $args['screen'] ?? null
        ]);
    }

    public function ajax_user_can()
    {
        return current_user_can('manage_options');
    }

    private function buildSearchQuery()
    {
        global $wpdb;
        $query    = "SELECT username, user_id, INET6_NTOA(ip) AS ip, dt, outcome FROM {$wpdb->prefix}login_log";
        $total    = "SELECT COUNT(*) FROM {$wpdb->prefix}login_log";
        $where    = ['1=1'];

        $ip = filter_input(INPUT_GET, 'ip');
        if ($ip) {
            $where[] = sprintf("INET6_NTOA(ip) = '%s'", $ip);
        }

        $user = filter_input(INPUT_GET, 'user');
        if ($user) {
            $where[] = sprintf("user_id = '%u'", $user);
        }

        $where  = join(' AND ', $where);

        $query .= ' WHERE ' . $where . ' ORDER BY id DESC';
        $total .= ' WHERE ' . $where;

        return [$total, $query];
    }

    public function prepare_items()
    {
        global $wpdb;
        list($total, $query) = $this->buildSearchQuery();

        $paged       = $this->get_pagenum();
        $per_page    = $this->get_items_per_page('psb_login_log');
        $query      .= ' LIMIT ' . ($paged - 1) * $per_page . ', ' . $per_page;

        $total_items = $wpdb->get_var($total);
        $this->items = $total_items ? $wpdb->get_results($query) : [];

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);
    }

    public function get_columns()
    {
        return array(
            'username' => __('Login', 'login-logger'),
            'ip'       => __('IP Address', 'login-logger'),
            'dt'       => __('Time', 'login-logger'),
            'outcome'  => __('Outcome', 'login-logger'),
        );
    }

    public function single_row($item)
    {
        $s = '<tr>';

        $actions = [
            'view' => sprintf(__('<a href="%s">Profile</a>'), admin_url('user-edit.php?user_id=' . $item->user_id))
        ];

        list($columns, $hidden, $sortable, $primary) = $this->get_column_info();

        foreach ($columns as $column_name => $column_display_name) {
            $classes = "{$column_name} column-{$column_name}";
            if ($primary === $column_name) {
                $classes .= ' has-row-actions column-primary';
            }

            if (in_array($column_name, $hidden)) {
                $classes .= ' hidden';
            }

            $attributes = "class='$classes'";
            $s .= "<td {$attributes}>";

            switch ($column_name) {
                case 'username':
                    $s .= esc_html($item->$column_name);
                    if ($item->user_id) {
                        $s .= $this->row_actions($actions, false);
                    }

                    break;

                case 'dt':
                    $s .= date('d.m.Y H:i:s', $item->$column_name);
                    break;

                case 'outcome':
                    if (-1 == $item->$column_name) {
                        $s .= __('Login attempt', 'login-logger');
                    }
                    else if (1 == $item->$column_name) {
                        $s .= __('Login OK', 'login-logger');
                    }
                    else {
                        $s .= __('Login failed', 'login-logger');
                    }

                    break;

                default:
                    $s .= esc_html($item->$column_name);
                    break;
            }

            $s .= "</td>";
        }

        $s .= '</tr>';
        echo $s;
    }
}
