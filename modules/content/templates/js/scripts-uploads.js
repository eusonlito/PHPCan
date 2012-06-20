$(document).ready(function () {
	$('li.file input').click(function () {
		$(this).focus().select();
	});
});