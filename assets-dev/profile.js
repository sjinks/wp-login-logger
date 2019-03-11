/**
 * global jQuery, wp
 */
jQuery(function($) {
	var user_id = $('#user_id').val();
	$('button[class~="destroy-session"][data-token]').click(function(e) {
		var button = $(e.target);
		var token  = button.data('token');
		var nonce  = button.data('nonce');
		var row    = button.closest('tr');
		var table  = row.closest('table');

		wp.ajax.post('wwall-destroy-session', {
			token: token,
			nonce: nonce,
			uid:   user_id
		}).done(function(response) {
			row.remove();
			table.siblings('.notice').remove();
			table.before('<div class="notice notice-success inline"><p>' + response.message + '</p></div>');
		}).fail(function(response) {
			table.siblings('.notice').remove();
			table.before('<div class="notice notice-error inline"><p>' + response.message + '</p></div>');
		});
	});
});
