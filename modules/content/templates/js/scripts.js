$(document).ready(function () {

	//Colorbox for iframes
	$('a.iframe').each(function () {
		var url = $(this).attr('href');

		url += (url.match(/\?/)) ? '&' : '?';
		url += 'phpcan_exit_mode=iframe';

		$(this).colorbox({
			href: url,
			iframe: true,
			width: '90%',
			height: '90%',
			opacity: 0.2,
			overlayClose: false,
			fixed: true,
			onOpen: function() {
				$('body').css('overflow', 'hidden');
			},
			onClosed: function() {
				$('body').css('overflow', 'auto');
			}
		});
	});

	//Colorbox for images
	$('a[href$=".jpg"],a[href$=".jpeg"],a[href$=".gif"],a[href$=".png"]').filter(':not(.no-colorbox)').colorbox({
		opacity: 0.2,
		width: '70%',
		height: '70%',
		fixed: true,
		title: function () {
			var url = $(this).attr('href');
			return '<a href="'+url+'" onclick="window.open(this.href); return false;">Open In New Window</a>';
		}
	});

	//Input type=text class="datetime"
	$('input[type=text].datetime').each(function () {
		$(this).datetimepicker({
			dateFormat: 'dd-mm-yy',
			timeFormat: 'HH:mm:ss'
		});
	});
	
	//Input type=text class="date"
	$('input[type=text].date').each(function () {
		$(this).datepicker({
			dateFormat: 'dd-mm-yy'
		});
	});

	//Select menu
	$('select#menu-tables').selectmenu({
		change: function (event, ui) {
			document.location = ui.value;
		}
	});

	$('select#menu-languages').selectmenu({
		change: function (event, ui) {
			var url = $(this).data('url');

			url += (url.match(/\?/)) ? '&' : '?';

			document.location = url + 'phpcan_action[language]=' + ui.value;
		}
	});

	$('[data-confirm-delete]').on('click', function (e) {
		var $this = $(this),
			button = ($this.is('button') || $this.is('input')),
			message = $this.data('confirm-delete'),
			string = '<?php __e('DELETE'); ?>';

		if ((message === 'true') || (typeof message === 'boolean') || !message) {
			message = '<?php __e('Do you realy want to delete this?'); ?>';
		}

		if (!confirm(message)) {
			return false;
		}

		if (prompt('<?php __e('Please, write "DELETE" word here to confirm'); ?>') !== string) {
			alert('<?php __e('Please write exact "DELETE" if you want to delete this content'); ?>');
			return false;
		}

		if (button) {
			$this.closest('form').append('<input type="hidden" name="confirm" value="' + string + '" />');
		} else {
			var href = $this.attr('href');

			if (href.indexOf('?') !== -1) {
				$this.attr('href', href + '&confirm=' + string);
			} else {
				$this.attr('href', '?confirm=' + string);
			}
		}

		if (typeof $.colorbox === 'function') {
			$.colorbox.remove()
		}

		return true;
	});
});