$(document).ready(function() {
	$.fancybox.defaults.smallBtn = "true";
	$.fancybox.defaults.loop = "true";
	$.fancybox.defaults.afterLoad = function(instance, current) {

		if (typeof $image !== 'undefined') {
			current.$image.attr('alt', current.opts.$orig.find('img').attr('alt') );
			current.$image.attr('title', current.opts.$orig.find('img').attr('title') );
		}
	};
  $.fancybox.defaults.caption = function (instance, slide, caption) {
		if (typeof $thumb !== 'undefined') {
			return slide.$thumb.attr('alt');
		}
	};
});
