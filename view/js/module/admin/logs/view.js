function log_show_details(id) {
	document
		.querySelectorAll('[data-id="' + id + '"]')
		.forEach(elm => {
			elm.classList.toggle('hidden')
		});
}
