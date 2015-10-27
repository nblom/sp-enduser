$(document).ready(function() {
	$('#check-all').click(function() {
			$('input.recipient').prop('checked', true);
			return false;
	});
	$('#add-access').click(function() {
		$("#extra-accesses").prepend("<div class='checkbox'><input type='text' class='form-control recipient' placeholder='Email or domain'></div>");
		return false;
	});
	$(".toggle").click(function() {
		$(".hidden-" + $(this).data("toggle")).toggle();
		var icon = $(this).find(".expand-icon");
		if (icon.hasClass('glyphicon-expand'))
			icon.addClass('glyphicon-collapse-down').removeClass('glyphicon-expand');
		else
			icon.addClass('glyphicon-expand').removeClass('glyphicon-collapse-down');
	});
	$('#bwlist_add').submit(function() {
		$.post("?page=bwlist&list=add", {
			"value": $("#value").val(),
			"type": $("#type").val(),
			"access": $('#bwlist_add input[type="checkbox"].recipient:checked, #bwlist_add input[type="text"].recipient').map(function(){ return $(this).val(); }).get()
		}, function(data) {
			if (data.error) {
				if (data.error == 'syntax')
					alert('Syntax error on field ' + data.field + ': ' + data.reason);
				if (data.error == 'permission')
					alert('No permission for ' + data.value);
				return;
			}
			window.location.reload();
			return;
		}).fail(function(jqXHR, textStatus, errorThrown) {
			alert('Error: ' + errorThrown);
		})
		return false;
	});
	$('#spam_add').submit(function() {
		$.post("?page=spam&list=" + $("#action").val(), {
			"level": $("#level").val(),
			"access": $('#spam_add input[type="checkbox"].recipient:checked, #spam_add input[type="text"].recipient').map(function(){ return $(this).val(); }).get()
		}, function(data) {
			if (data.error) {
				if (data.error == 'syntax')
					alert('Syntax error on field ' + data.field + ': ' + data.reason);
				if (data.error == 'permission')
					alert('No permission for ' + data.value);
				return;
			}
			window.location.reload();
			return;
		}).fail(function(jqXHR, textStatus, errorThrown) {
			alert('Error: ' + errorThrown);
		})
		return false;
	});
});
