$(document).ready(function(){
	$('nav').bind('nav-update', function(e,data){
		var notifCount = $(data).find('notif').attr('count');
		var intro = $(data).find('intro').text();
		var mail = $(data).find('mail').text();

		$(".tool .notify").removeClass("on");
		$(data).find("group").each(function() {
			var gid = this.id;
			var gcount = this.innerHTML;
			$(".group-"+gid+" .notify").addClass("on").text(gcount);
		});

		$(".forum-widget-entry .notify").removeClass("on");
		$(data).find("forum").each(function() {
			var fid = this.id;
			var fcount = this.innerHTML;
			$(".forum-"+fid+" .notify").addClass("on").text(fcount);
		});

		console.log(intro,mail);

		if (notifCount > 0 ) {
			Tinycon.setBubble(notifCount);
		} else {
			Tinycon.setBubble('');
		}

		if (intro>0){
			$("#nav-introductions-link").addClass("on");
		} else {
			$("#nav-introductions-link").removeClass("on");
		}

		if (mail>0){
			$("#nav-messages-link").addClass("on");
		} else {
			$("#nav-messages-link").removeClass("on");
		}

	});

	/*
	 * show and hide contact action buttons in
	 * contacts page on contacts' checkbox selection
	 */
	$('.contact-select').bind('click', function(e) {
		var y = e.clientY;
		var elm = $("#contacts-actions");
		y=y-40;
		if (y<0) y=0;
		if (y+elm.height() > $("html").height()) y=$("html").height()-elm.height();
		elm.css('top', y+"px");
		if ($(".contact-select:checked").length > 0) {
			elm.show();
		} else {
			elm.hide();
		}
	});
});

function insertFormatting(BBcode, id) {
	var tmpStr = $("#comment-edit-text-" + id).val();
	if (tmpStr == "") {
		$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
		$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
		openMenu("comment-edit-submit-wrapper-" + id);
	}

	textarea = document.getElementById("comment-edit-text-" +id);
	if (document.selection) {
		textarea.focus();
		selected = document.selection.createRange();
		if (BBcode == "url") {
			selected.text = "["+BBcode+"]" + "http://" +  selected.text + "[/"+BBcode+"]";
		} else {
			selected.text = "["+BBcode+"]" + selected.text + "[/"+BBcode+"]";
		}
	} else if (textarea.selectionStart || textarea.selectionStart == "0") {
		var start = textarea.selectionStart;
		var end = textarea.selectionEnd;
		if (BBcode == "url") {
			textarea.value = textarea.value.substring(0, start) + "["+BBcode+"]" + "http://" + textarea.value.substring(start, end) + "[/"+BBcode+"]" + textarea.value.substring(end, textarea.value.length);
		} else {
			textarea.value = textarea.value.substring(0, start) + "["+BBcode+"]" + textarea.value.substring(start, end) + "[/"+BBcode+"]" + textarea.value.substring(end, textarea.value.length);
		}
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


