$(document).ready(function () {
	//jQuery UI Buttons
	var buttons = $('button, a.button, :submit:visible, :button').button();

	$(buttons).filter('[data-icon]').each(function () {
		$(this).button('option', 'icons', {primary: 'ui-icon-' + $(this).data('icon') });

		if ($(this).data('no-text')) {
			$(this).button('option', 'text', false);
		}
	});

	$('.buttonset').buttonset();
	$(buttons).filter('.disabled').button('option', 'disabled', true).click(function () { return false; });
	$(buttons).filter('.secondary').removeClass('.secondary').addClass('ui-priority-secondary');

	//Message
	$('#message').click(function () {
		$(this).slideUp('fast');
	});

	$('#message.success').delay(8000).slideUp(1000);

	//Dialogs
	$('.dialog').dialog({
		autoOpen: false
	});

	$('.open-dialog').click(function () {
		$($(this).attr('href')).dialog('open');
		return false;
	});

	$(document).delegate('.action-add-tr', 'click', function () {
		var id = 'a' + Math.floor ( Math.random ( ) * 1000 + 1 );
		var clon = $(this).parents('tr').clone();

		clon.find('input, select').each(function () {
			$(this).attr('name', $(this).attr('name').replace(/\[[a-z]?[0-9]+\]/, '['+id+']'));
		});

		$(this).parents('tbody').append(clon);

		return false;
	});

	$(document).delegate('.action-remove-tr', 'click', function () {
		if ($(this).parents('tbody').find('tr').length > 1) {
			$(this).parents('tr').remove();
		}

		return false;
	});
});

//Plugin for select rows in table
jQuery.fn.checkTable = function () {
	var $element = $(this);
	var $checkboxes = $('tbody td.actions :checkbox', $element);
	var $action_buttons = $('tfoot td.actions a.button', $element);
	var $action_checkbox = $('tfoot td.actions :checkbox', $element);

	var update = function () {
		var $checked = $checkboxes.filter(':checked', $element);

		$('tbody tr.select', $element).removeClass('select');
		
		$checked.each(function () {
			$(this).parents('tr').addClass('select');
		});

		$action_checkbox.prop('indeterminate', false);

		if ($checked.length) {
			$action_buttons.button('option', 'disabled', false);

			if ($checked.length == $checkboxes.length) {
				$action_checkbox.prop('checked', true);
			} else {
				$action_checkbox.prop('indeterminate', true);
			}
		} else {
			$action_buttons.button('option', 'disabled', true);
			$action_checkbox.prop('checked', false);
		}
	}

	update();

	$checkboxes.click(update);

	$action_checkbox.change(function () {
		if ($(this).prop('checked')) {
			$checkboxes.prop('checked', true);
		} else {
			$checkboxes.prop('checked', false);
		}

		update();
	});

	return this;
};