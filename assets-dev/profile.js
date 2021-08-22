/**
 * global jQuery, wp
 */
jQuery(function($) {
	var user_id = $('#user_id').val();
	$('button[class~="destroy-session"][data-token]').click(function(e) {
		var button = $(e.target);
		var token  = button.data('token');
		var row    = button.closest('tr');
		var table  = row.closest('table');

		wp.apiRequest({ path: 'login-logger/v1/' + user_id + '/sessions/' + token, method: 'DELETE' })
			.done(function(response) {
				row.remove();
				table.siblings('.notice').remove();
				table.before('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
			}).fail(function(response, status, err) {
				var msg = ('responseJSON' in response) ? response.responseJSON.message : err;
				table.siblings('.notice').remove();
				table.before('<div class="notice notice-error inline"><p>' + msg + '</p></div>');
			});
	});
});
