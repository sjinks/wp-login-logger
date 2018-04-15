<?php defined('ABSPATH') || die(); ?>
<div class="wrap">
	<h1><?=__('Login History', 'login-logger'); ?></h1>

	<form action="<?php echo admin_url('users.php'); ?>" method="get">
		<input type="hidden" name="page" value="login-history"/>
<?php
$_GET['user'] = wp_get_current_user()->ID;
$table = new \WildWolf\LoginLogger\LoginTable(['screen' => 'psb-login-log']);
$table->prepare_items();
$table->display();
?>
	</form>
</div>
