
<script type="text/javascript" src="{{$baseurl}}/view/theme/smoothly/js/jquery.autogrow.textarea.js"></script>
<script type="text/javascript">
$(document).ready(function() {

});
function tautogrow(id) {
	$("textarea#comment-edit-text-" + id).autogrow();
};

function insertFormatting(BBcode, id) {
	var tmpStr = $("#comment-edit-text-" + id).val();
	if (tmpStr == "") {
		$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
		$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
		openMenu("comment-edit-submit-wrapper-" + id);
	}

	textarea = document.getElementById("comment-edit-text-" + id);
	if (document.selection) {
		textarea.focus();
		selected = document.selection.createRange();
		if (BBcode == "url") {
			selected.text = "["+BBcode+"]" + "http://" + selected.text + "[/"+BBcode+"]";
		} else {
			selected.text = "["+BBcode+"]" + selected.text + "[/"+BBcode+"]";
		}
	} else if (textarea.selectionStart || textarea.selectionStart == "0") {
		var start = textarea.selectionStart;
		var end = textarea.selectionEnd;
		if (BBcode == "url") {
			textarea.value = textarea.value.substring(0, start) + "["+BBcode+"]"
				+ "http://" + textarea.value.substring(start, end)
				+ "[/"+BBcode+"]" + textarea.value.substring(end, textarea.value.length);
		} else {
			textarea.value = textarea.value.substring(0, start)
				+ "["+BBcode+"]" + textarea.value.substring(start, end) + "[/"+BBcode+"]"
				+ textarea.value.substring(end, textarea.value.length);
		}
	}

	return true;
}

function cmtBbOpen(id) {
	$(".comment-edit-bb-" + id).show();
}
function cmtBbClose(id) {
    $(".comment-edit-bb-" + id).hide();
}
</script>
