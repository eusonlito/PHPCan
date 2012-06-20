$(document).ready(function () {
	$('table.list').checkTable().find('tfoot td.actions a.button').click(function () {
		var href = $(this).attr('href');
		var ids = [];

		$('table.list tbody td.actions :checked').each(function () {
			ids.push($(this).attr('name'));
		});

		href = href.replace('|id|', ids.join(','));

		$(this).attr('href', href);

		return true;
	});
});