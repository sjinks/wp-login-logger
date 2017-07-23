<?php defined('ABSPATH') || die(); ?>
<div class="wrap">
	<h3><?=esc_html__('Login Log', 'login-logger');?></h3>

	<form action="<?php echo admin_url('tools.php'); ?>" method="get">
<?php
$table = new \WildWolf\LoginLogger\LoginTable(['screen' => 'psb-login-log']);
$table->prepare_items();
?>
	<table>
		<tbody>
			<tr>
				<td><?=esc_html__('Login', 'login-logger');?></td>
				<td>
					<?php
					wp_dropdown_users([
					    'show'            => 'user_login',
					    'selected'        => filter_input(INPUT_GET, 'user'),
					    'show_option_all' => esc_html__('All', 'login-logger')]
					);
					?>
				</td>
			</tr>
			<tr>
				<td><?=esc_html__('IP Address', 'login-logger');?></td>
				<td><input type="text" name="ip" value="<?=filter_input(INPUT_GET, 'ip'); ?>"/></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					<input type="submit" id="search-submit" value="<?=esc_attr__('Search', 'login-logger');?>" class="button"/>
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
