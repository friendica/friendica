/**
 * @brief Javascript for the admin module
 */
$(function() {
	$('body').on('click', '.selectall', function() {
		selectall($(this).data('selectAll'));
	});
	$('body').on('click', '.selectnone', function() {
		selectnone($(this).data('selectNone'));
	});

	// Toggle checkbox status to all or none for all checkboxes of a specific
	// css class.
	$('body').on('change', 'input[type=checkbox].selecttoggle', function() {
		$this = $(this);
		if ($this.prop('checked')) {
			selectall($this.data('selectClass'));
			$this.attr('title', $this.data('selectNone'));
		} else {
			selectnone($this.data('selectClass'));
			$this.attr('title', $this.data('selectAll'));
		}
	});

	// Use AJAX calls to reorder the table (so we don't need to reload the page).
	$('body').on('click', '.table-order', function(e) {
		e.preventDefault();

		// Get the parent table element.
		var table = $(this).parents('table');
		var orderUrl = this.getAttribute("data-order-url");
		table.fadeTo("fast", 0.33);

		$("body").css("cursor", "wait");

		$.get(orderUrl, function(data) {
			// Find the table element in the html we got.
			var result = $(data).find('#' + table[0].id);
			// And add the new table html to the parent.
			$(table).parent().html(result);

			$("body").css("cursor", "auto");
		});
	});

	function selectall(cls) {
		$('.' + cls).prop('checked', true);
		return false;
	}
	function selectnone(cls) {
		$('.' + cls).prop('checked', false);
		return false;
	}


});

// Users
function confirm_delete(msg, uname){
	return confirm(msg.format(uname));
}

function details(uid) {
	$("#user-" + uid + "-detail").toggleClass("hidden");
	$("#user-" + uid).toggleClass("opened");
	return false;
}
