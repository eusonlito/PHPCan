$(document).ready(function () {

	$('select#menu-gettext').selectmenu({
		select: function (event, ui) {
			document.location = $(this).data('url') + ui.value;
		}
	});

	//Details
	$('.details').each(function () {
		var $summary = $(this).find('.summary');
		var $content = $(this).children().not('.summary');

		$content.hide();
		$summary.click(function () {
			$content.slideToggle('fast');
		});
	});

	//Copy strings
	$('tbody label').dblclick(function () {
		var $this = $(this);
		$('#' + $this.attr('for')).val($this.text()).focus();
	});

	//Save as
	$('fieldset.footer .save-as').click(function () {
		$('#language_to_save').val($(this).data('language'));
	})
});