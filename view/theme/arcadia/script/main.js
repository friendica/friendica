function toggle(id) {
	elem = $('#'+id);
	elem.slideToggle();
	if(elem.is(":visible")) {
		elem.focus();
	}
}

function hide(id) {
	elem = $('#'+id);
	elem.slideUp();
}

function set_language(value) {
	node = $('#language-selector-form-value');
	if(node.val() != value) {
		node.val(value);
		return $('#language-selector-form').submit();
	}
	return false;
}

function link_act(uri) {
	document.location.href = uri;
	return false;
}
