$(document).ready(function () {

	//Tab in textarea.code
	$('textarea.code').keydown(function (e) {
		if (e.keyCode == 9) {
			var myValue = "\t";
			var startPos = this.selectionStart;
			var endPos = this.selectionEnd;
			var scrollTop = this.scrollTop;

			this.value = this.value.substring(0, startPos) + myValue + this.value.substring(endPos,this.value.length);
			this.focus();
			this.selectionStart = startPos + myValue.length;
			this.selectionEnd = startPos + myValue.length;
			this.scrollTop = scrollTop;

			e.preventDefault();
		}
	});

	//Preview html code
	$('.preview').click(function () {
		var html = $('#' + $(this).attr('rel')).val();
		$.colorbox({
			html: '<div class="text">' + html + '</div>',
			title: $(this).text(),
			opacity: 0.2,
			maxWidth: '90%',
			maxHeight: '90%'
		});
	});

	//show/hide fields
	var fields = $.parseJSON($.cookie('show_hide_fields')) || {};
	var getSource = function (obj) {
		var output = [], temp;

		for (var i in obj) {
			if (obj.hasOwnProperty(i)) {
				temp = '"' + i + '"' + ":";

				switch (typeof obj[i]) {
					case "object" :
					temp += getSource(obj[i]);
					break;

					case "string" :
					temp += "\"" + obj[i] + "\"";    // add in some code to escape quotes
					break;

					default :
					temp += obj[i];
				}
				output.push(temp);
			}
		}
		return "{" + output.join() + "}";
	}

	$.each(fields, function (key, value) {
		if (value) {
			var data = key.split('__');
			var $element = $('label.field > strong[data-table="' + data[0] + '"][data-name="' + data[1] + '"][data-language="' + data[2] + '"]');

			$element.next('p').hide();
			$element.parents('label').next('div').hide();
		}
	});

	$('label.field > strong').dblclick(function () {
		var $element = $(this);
		var $description = $element.next('p');
		var name = $element.data('table') + '__' + $element.data('name') + '__' + $element.data('language');

		$description.slideToggle('fast');

		$element.parents('label').next('div').toggle('fast', function () {
			fields[name] = $(this).is(':hidden');

			$.cookie('show_hide_fields', getSource(fields), {path: paths.base});
		});

		return false;
	});
});

//Remove id function (for duplicate action)
function removeId () {
	$("input[type='hidden'][name$='[action]']").val('insert');
	$("input[type='hidden'][name*='[id]']").remove();
	return true;
}

//Change input type
jQuery.fn.changeInputType = function (new_type) {
	var oldObject = $(this).get(0);
	var newObject = $('<input />', {
		type: new_type
	}).get(0);

	if (oldObject.size) newObject.size = oldObject.size;
	if (oldObject.value) newObject.value = oldObject.value;
	if (oldObject.name) newObject.name = oldObject.name;
	if (oldObject.id) newObject.id = oldObject.id;
	if (oldObject.className) newObject.className = oldObject.className;

	oldObject.parentNode.replaceChild(newObject,oldObject);

	return $(newObject);
}