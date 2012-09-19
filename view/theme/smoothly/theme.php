<?php

/*
 * Name: Smoothly
 * Description: Like coffee with milk. Theme works fine with iPad[2].
 * Version: 0.9.19-1
 * Author: Alex <https://friendica.pixelbits.de/profile/alex>
 * Maintainer: Alex <https://friendica.pixelbits.de/profile/alex>
 * Screenshot: <a href="screenshot.png">Screenshot</a>
 */

$a->theme_info = array();

function smoothly_init(&$a) {
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

function cmtBbOpen(id) {
	$(".comment-edit-bb-" + id).show();
}
function cmtBbClose(comment, id) {
	$(".comment-edit-bb-" + id).hide();
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

    // custom css
    if (!is_null($cssFile)) {
        $a->page['htmlhead'] .= sprintf('<link rel="stylesheet" type="text/css" href="%s" />', $cssFile);
    }

_js_in_foot();

}

if(! function_exists('_js_in_foot')) {
	function _js_in_foot() {
		/** @purpose insert stuff in bottom of page
		*/
		$a = get_app();
		$baseurl = $a->get_baseurl($ssl_state);
		$bottom['$baseurl'] = $baseurl;
		$tpl = file_get_contents(dirname(__file__) . '/bottom.tpl');

		return $a->page['bottom'] = replace_macros($tpl, $bottom);
	}
}
