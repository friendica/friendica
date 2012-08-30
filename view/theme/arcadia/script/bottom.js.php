<?php
/*
 * Get all javascript at once to minimize number of requests
 */
header('Content-type: application/javascript; charset=utf-8');
 
$topdir = '../../../../';
chdir($topdir);

echo file_get_contents('./js/jquery.js');
//echo file_get_contents('./js/jquery.textinputs.js');
//echo file_get_contents('./js/fk.autocomplete.js');
//echo file_get_contents('./library/fancybox/jquery.fancybox-1.3.4.pack.js');
//echo file_get_contents('./library/tiptip/jquery.tipTip.minified.js');
//echo file_get_contents('./library/jgrowl/jquery.jgrowl_minimized.js');
//echo file_get_contents('./library/tinymce/jscripts/tiny_mce/tiny_mce_src.js');
//echo file_get_contents('./js/acl.js');

/*$(document).ready(function() {
	if(typeof acl=="undefined"){
		acl = new ACL(
			baseurl+"/acl",
			window.acl_setup
		);
	}
});*/

//echo file_get_contents('./js/webtoolkit.base64.js');
//echo file_get_contents('./js/main.js');
echo file_get_contents('./view/theme/arcadia/script/main.js');
/*function confirmDelete() { return confirm(window.delItem); }
function commentOpen(obj,id) {
	if(obj.value == window.commentEmptyText) {
		obj.value = '';
		$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
		$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
		$("#mod-cmnt-wrap-" + id).show();
		openMenu("comment-edit-submit-wrapper-" + id);
		return true;
	}
	return false;
}
function commentClose(obj,id) {
	if(obj.value == '') {
		obj.value = window.commentEmptyText;
		$("#comment-edit-text-" + id).removeClass("comment-edit-text-full");
		$("#comment-edit-text-" + id).addClass("comment-edit-text-empty");
		$("#mod-cmnt-wrap-" + id).hide();
		closeMenu("comment-edit-submit-wrapper-" + id);
		return true;
	}
	return false;
}


function commentInsert(obj,id) {
	var tmpStr = $("#comment-edit-text-" + id).val();
	if(tmpStr == window.commentEmptyText) {
		tmpStr = '';
		$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
		$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
		openMenu("comment-edit-submit-wrapper-" + id);
	}
	var ins = $(obj).html();
	ins = ins.replace('&lt;','<');
	ins = ins.replace('&gt;','>');
	ins = ins.replace('&amp;','&');
	ins = ins.replace('&quot;','"');
	$("#comment-edit-text-" + id).val(tmpStr + ins);
}

function qCommentInsert(obj,id) {
	var tmpStr = $("#comment-edit-text-" + id).val();
	if(tmpStr == window.commentEmptyText) {
		tmpStr = '';
		$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
		$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
		openMenu("comment-edit-submit-wrapper-" + id);
	}
	var ins = $(obj).val();
	ins = ins.replace('&lt;','<');
	ins = ins.replace('&gt;','>');
	ins = ins.replace('&amp;','&');
	ins = ins.replace('&quot;','"');
	$("#comment-edit-text-" + id).val(tmpStr + ins);
	$(obj).val('');
}

function showHideComments(id) {
	if( $('#collapsed-comments-' + id).is(':visible')) {
		$('#collapsed-comments-' + id).hide();
		$('#hide-comments-' + id).html(window.showMore);
	}
	else {
		$('#collapsed-comments-' + id).show();
		$('#hide-comments-' + id).html(window.showFewer);
	}
}

function showHideCommentBox(id) {
	if( $('#comment-edit-form-' + id).is(':visible')) {
		$('#comment-edit-form-' + id).hide();
	}
	else {
		$('#comment-edit-form-' + id).show();
	}
}*/

/*echo file_get_contents('./view/theme/arcadia/script/jot.js');
echo file_get_contents('./js/ajaxupload.js');
echo file_get_contents('./view/theme/arcadia/script/jot_b.js');
echo file_get_contents('./view/theme/arcadia/script/settings.js');
echo file_get_contents('./view/theme/arcadia/script/photo.js');*/
?>
