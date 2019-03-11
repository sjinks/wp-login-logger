<?php defined('ABSPATH') || die(); ?>
<h2 id="user-sessions"><?=__('Sessions', 'login-logger'); ?></h2>
<?php
$table = new \WildWolf\LoginLogger\SessionTable(['screen' => 'user-sessions', 'user_id' => $params['user_id']]);
$table->prepare_items();
if ($table->items) {
	$table->display();
}
?>
<p><?=sprintf(__('Last login date: %s', 'login-logger'), $params['last']); ?></p>
<?php if (current_user_can('manage_options')) : ?>
<p><a href="<?=esc_attr(admin_url('tools.php?page=login-log&user=' . $params['user_id'])); ?>"><?=__('Login History', 'login-logger'); ?></a></p>
<?php elseif ($params['user_id'] == get_current_user_id()) : ?>
<p><a href="<?=esc_attr(admin_url('users.php?page=login-history')); ?>"><?=__('Login History', 'login-logger'); ?></a></p>
<?php endif; ?>
