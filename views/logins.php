<?php defined('ABSPATH') || die(); ?>
<div class="wrap">
	<h1><?=__('Login Log', 'login-logger'); ?></h1>

	<form action="<?php echo admin_url('tools.php'); ?>" method="get">
<?php
$table = new \WildWolf\LoginLogger\LoginTable(['screen' => 'psb-login-log']);
$table->prepare_items();
?>
	<table>
		<tbody>
			<tr>
				<td><?=__('Login', 'login-logger'); ?></td>
				<td>
					<?php
					wp_dropdown_users([
					    'show'            => 'user_login',
					    'selected'        => $_GET['user'] ?? '',
					    'show_option_all' => __('All', 'login-logger')
					]);
					?>
				</td>
			</tr>
			<tr>
				<td><?=__('IP Address', 'login-logger'); ?></td>
				<td><input type="text" name="ip" value="<?=esc_attr($_GET['ip'] ?? ''); ?>"/></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					<?php submit_button(__('Search', 'login-logger'), 'primary', 'submit', false); ?>
					<input type="hidden" name="page" value="psb-login-log"/>
				</td>
			</tr>
		</tbody>
	</table>

<?php
$table->display();
?>
	</form>
</div>
