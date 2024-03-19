<?php defined( 'ABSPATH' ) || die(); ?>
<?php
/**
 * @var array $params
 * @psalm-var array{user: string, ip: string, table: WP_List_Table} $params
 */
?>
<div class="wrap">
	<h1 id="ll-header"><?php echo esc_html__( 'Login Log', 'login-logger' ); ?></h1>

	<form action="<?php echo esc_url( admin_url( 'tools.php' ) ); ?>" method="get">
<?php
$table = $params['table'];
$table->prepare_items();
?>
	<table aria-labelledby="ll-header" class="form-table">
		<tbody>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Login', 'login-logger' ); ?></th>
				<td>
					<?php
					wp_dropdown_users(
						[
							'show'            => 'user_login',
							'selected'        => $params['user'],
							'show_option_all' => __( 'All', 'login-logger' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?= esc_html__( 'IP Address', 'login-logger' ); ?></th>
				<td><input type="text" name="ip" value="<?= esc_attr( $params['ip'] ); ?>"/></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					<?php submit_button( __( 'Search', 'login-logger' ), 'primary', 'submit', false ); ?>
					<input type="hidden" name="page" value="login-log"/>
				</td>
			</tr>
		</tbody>
	</table>

<?php
$table->display();
?>
	</form>
</div>
