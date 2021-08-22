<?php defined( 'ABSPATH' ) || die(); ?>
<div class="wrap">
	<h1><?php echo esc_html__( 'Login History', 'login-logger' ); ?></h1>

	<form action="<?php echo esc_url( admin_url( 'users.php' ) ); ?>" method="get">
		<input type="hidden" name="page" value="login-history"/>
<?php
$table = new WildWolf\WordPress\LoginLogger\LoginTable( [
	'screen'  => 'login-history',
	'user_id' => wp_get_current_user()->ID,
] );
$table->prepare_items();
$table->display();
?>
	</form>
</div>
