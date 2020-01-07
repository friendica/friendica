<?php

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Core\Renderer;
use Friendica\Registry\App as A;

function duepuntozero_init(App $a) {

Renderer::setActiveTemplateEngine('smarty3');

    $colorset = PConfig::get( local_user(), 'duepuntozero','colorset');
    if (!$colorset)
       $colorset = Config::get('duepuntozero', 'colorset');          // user setting have priority, then node settings
    if ($colorset) {
        if ($colorset == 'greenzero')
            A::page()['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/greenzero.css" type="text/css" media="screen" />' . "\n";
        if ($colorset == 'purplezero')
	        A::page()['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/purplezero.css" type="text/css" media="screen" />' . "\n";
        if ($colorset == 'easterbunny')
	        A::page()['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/easterbunny.css" type="text/css" media="screen" />' . "\n";
        if ($colorset == 'darkzero')
	        A::page()['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/darkzero.css" type="text/css" media="screen" />' . "\n";
        if ($colorset == 'comix')
	        A::page()['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/comix.css" type="text/css" media="screen" />' . "\n";
        if ($colorset == 'slackr')
	        A::page()['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/slackr.css" type="text/css" media="screen" />' . "\n";
    }
A::page()['htmlhead'] .= <<< EOT
<script>
function cmtBbOpen(comment, id) {
	if ($(comment).hasClass('comment-edit-text-full')) {
		$(".comment-edit-bb-" + id).show();
		return true;
	}
	return false;
}
function cmtBbClose(comment, id) {
	return false;
}
$(document).ready(function() {

	$('html').click(function() { $("#nav-notifications-menu" ).hide(); });

	$('.group-edit-icon').hover(
		function() {
			$(this).addClass('icon'); $(this).removeClass('iconspacer');},
		function() {
			$(this).removeClass('icon'); $(this).addClass('iconspacer');}
	);

	$('.sidebar-group-element').hover(
		function() {
			id = $(this).attr('id');
			$('#edit-' + id).addClass('icon'); $('#edit-' + id).removeClass('iconspacer');},

		function() {
			id = $(this).attr('id');
			$('#edit-' + id).removeClass('icon');$('#edit-' + id).addClass('iconspacer');}
	);


	$('.savedsearchdrop').hover(
		function() {
			$(this).addClass('drop'); $(this).addClass('icon'); $(this).removeClass('iconspacer');},
		function() {
			$(this).removeClass('drop'); $(this).removeClass('icon'); $(this).addClass('iconspacer');}
	);

	$('.savedsearchterm').hover(
		function() {
			id = $(this).attr('id');
			$('#drop-' + id).addClass('icon'); 	$('#drop-' + id).addClass('drophide'); $('#drop-' + id).removeClass('iconspacer');},

		function() {
			id = $(this).attr('id');
			$('#drop-' + id).removeClass('icon');$('#drop-' + id).removeClass('drophide'); $('#drop-' + id).addClass('iconspacer');}
	);
});
</script>
EOT;
}
