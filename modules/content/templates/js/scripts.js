$(document).ready(function () {

	//Colorbox for iframes
	$('a.iframe').each(function () {
		var url = $(this).attr('href');

		if (url.match(/\?/)) {
			url += '&phpcan_exit_mode=iframe';
		} else {
			url += '?phpcan_exit_mode=iframe';
		}

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
			timeFormat: 'hh:mm:ss'
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
			document.location = $(this).data('url') + '?phpcan_action[language]=' + ui.value;
		}
	});
});