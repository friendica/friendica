<?php
/**
 * Name: Vier-mobil
 * Version: 0.9
 * Author: Fabio <http://kirgroup.com/profile/fabrixxm>
 * Author: Ike <http://pirati.ca/profile/heluecht>
 * Maintainer: Ike <http://pirati.ca/profile/heluecht>
 * Description: "Vier" uses the font awesome font library: http://fortawesome.github.com/Font-Awesome/
 */

function vier_mobil_init(&$a) {
set_template_engine($a, 'smarty3');

$baseurl = $a->get_baseurl();

$a->theme_info = array(
    'extends'=>'vier',
);

$style = get_pconfig(local_user(), 'vier-mobil', 'style');
if ($style == "flat")
	$a->page['htmlhead'] .= '<link rel="stylesheet" href="view/theme/vier-mobil/flat.css" type="text/css" media="screen"/>'."\n";
else if ($style == "netcolour")
	$a->page['htmlhead'] .= '<link rel="stylesheet" href="view/theme/vier-mobil/netcolour.css" type="text/css" media="screen"/>'."\n";

$a->page['htmlhead'] .= <<< EOT
<script type="text/javascript" src="$baseurl/view/theme/vier/js/jquery.divgrow-1.3.1.f1.min.js"></script>
<script>

function collapseHeight(elems) {
	var elemName = '.wall-item-body:not(.divmore)';
	if(typeof elems != 'undefined') {
		elemName = elems + ' ' + elemName;
	}
	$(elemName).each(function() {
		if($(this).height() > 450) {
			$('html').height($('html').height());
			$(this).divgrow({ initialHeight: 400, showBrackets: false, speed: 0 });
			$(this).addClass('divmore');
			$('html').height('auto');
		}
	});
}

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


function showThread(id) {
	$("#collapsed-comments-" + id).show()
	$("#collapsed-comments-" + id + " .collapsed-comments").show()
}
function hideThread(id) {
	$("#collapsed-comments-" + id).hide()
	$("#collapsed-comments-" + id + " .collapsed-comments").hide()
}


function cmtBbOpen(id) {
	$("#comment-edit-bb-" + id).show();
}
function cmtBbClose(id) {
	$("#comment-edit-bb-" + id).hide();
}
</script>
EOT;
}

