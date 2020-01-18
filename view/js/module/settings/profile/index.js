$(function () {
	$("#profile-custom-fields").sortable({
		containerSelector: 'div#profile-custom-fields',
		handle: 'legend',
		itemSelector: 'fieldset',
		placeholder: '<div class="placeholder"></div>',
		onDrag: function($item, position, _super, event) {
			delete position['left'];
			$item.css(position);
			event.preventDefault();
		}
	});
});
