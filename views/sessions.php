<?php defined( 'ABSPATH' ) || die(); ?>
<?php
/** @psalm-var array{user_id: int, last: string} $params */
?>
<h2 id="user-sessions"><?php echo esc_html__( 'Sessions', 'login-logger' ); ?></h2>
<?php
$table = new WildWolf\WordPress\LoginLogger\SessionTable( [
	'screen'  => 'user-sessions',
	'user_id' => $params['user_id'],
] );
$table->prepare_items();
if ( $table->items ) {
	$table->display();
}
?>
<p><?php /* translators: %s is login date in the current locale format or 'N/A' */ echo esc_html( sprintf( __( 'Last login date: %s', 'login-logger' ), $params['last'] ) ); ?></p>
<?php if ( current_user_can( 'manage_options' ) ) : ?>
<p><a href="<?php echo esc_url( admin_url( 'tools.php?page=login-log&user=' . $params['user_id'] ) ); ?>"><?php echo esc_html__( 'Login History', 'login-logger' ); ?></a></p>
<?php elseif ( get_current_user_id() === $params['user_id'] ) : ?>
<p><a href="<?php echo esc_url( admin_url( 'users.php?page=login-history' ) ); ?>"><?php echo esc_html__( 'Login History', 'login-logger' ); ?></a></p>
<?php endif; ?>
