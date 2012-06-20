$(document).ready(function () {
	$('table.list').checkTable();
	$('table.list tfoot td.actions a.button').click(function () {
		var ids = [];
		var form = $($(this).attr('href'));

		$('table.list tbody td.actions :checked').each(function () {
			ids.push($(this).attr('name'));
		});

		form.find(':hidden[name="id"]').val(ids.join(','));
		form.submit();

		return false;
	});
});