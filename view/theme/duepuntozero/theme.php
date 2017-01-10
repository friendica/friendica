<?php

function duepuntozero_init(App &$a) {

set_template_engine($a, 'smarty3');

    $colorset = get_pconfig( local_user(), 'duepuntozero','colorset');
    if (!$colorset)
       $colorset = get_config('duepuntozero', 'colorset');          // user setting have priority, then node settings
    if ($colorset) {
        if ($colorset == 'greenzero')
            $a->page['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/greenzero.css" type="text/css" media="screen" />'."\n";
        if ($colorset == 'purplezero')
            $a->page['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/purplezero.css" type="text/css" media="screen" />'."\n";
        if ($colorset == 'easterbunny')
            $a->page['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/easterbunny.css" type="text/css" media="screen" />'."\n";
        if ($colorset == 'darkzero')
            $a->page['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/darkzero.css" type="text/css" media="screen" />'."\n";
        if ($colorset == 'comix')
            $a->page['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/comix.css" type="text/css" media="screen" />'."\n";
        if ($colorset == 'slackr')
            $a->page['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/slackr.css" type="text/css" media="screen" />'."\n";
    }
$a->page['htmlhead'] .= <<< EOT
<script>
function insertFormatting(comment,BBcode,id) {
	
		var tmpStr = $("#comment-edit-text-" + id).val();
		if(tmpStr == comment) {
			tmpStr = "";
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			openMenu("comment-edit-submit-wrapper-" + id);
			$("#comment-edit-text-" + id).val(tmpStr);
		}

	textarea = document.getElementById("comment-edit-text-" +id);
	if (document.selection) {
		textarea.focus();
		selected = document.selection.createRange();
		if (BBcode == "url"){
			selected.text = "["+BBcode+"]" + "http://" +  selected.text + "[/"+BBcode+"]";
			} else			
		selected.text = "["+BBcode+"]" + selected.text + "[/"+BBcode+"]";
	} else if (textarea.selectionStart || textarea.selectionStart == "0") {
		var start = textarea.selectionStart;
		var end = textarea.selectionEnd;
		if (BBcode == "url"){
			textarea.value = textarea.value.substring(0, start) + "["+BBcode+"]" + "http://" + textarea.value.substring(start, end) + "[/"+BBcode+"]" + textarea.value.substring(end, textarea.value.length);
			} else
		textarea.value = textarea.value.substring(0, start) + "["+BBcode+"]" + textarea.value.substring(start, end) + "[/"+BBcode+"]" + textarea.value.substring(end, textarea.value.length);
	}
	return true;
}

function cmtBbOpen(comment, id) {
	if($(comment).hasClass('comment-edit-text-full')) {
		$(".comment-edit-bb-" + id).show();
		return true;
	}
	return false;
}
function cmtBbClose(comment, id) {
//	if($(comment).hasClass('comment-edit-text-empty')) {
//		$(".comment-edit-bb-" + id).hide();
//		return true;
//	}
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
