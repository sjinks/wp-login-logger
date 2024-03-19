<?php defined( 'ABSPATH' ) || die(); ?>
<?php
/**
 * @var array $params
 * @psalm-var array{table: WP_List_Table} $params
 */
?>
<div class="wrap">
	<h1><?php echo esc_html__( 'Login History', 'login-logger' ); ?></h1>

	<form action="<?= esc_url( admin_url( 'users.php' ) ); ?>" method="get">
		<input type="hidden" name="page" value="login-history"/>
<?php
$table = $params['table'];
$table->prepare_items();
$table->display();
?>
	</form>
</div>
