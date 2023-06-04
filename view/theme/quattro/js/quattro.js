// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later
$(document).ready(function(){
	$('nav').bind('nav-update', function(e,data){
		var notifCount = $(data).find('notif').attr('count');
		var intro = parseInt($(data).find('intro').text());
		var mail = parseInt($(data).find('mail').text());

		$(".tool .notify").removeClass("on");
		$(data).find("circle").each(function() {
			var gid = this.id;
			var gcount = this.innerHTML;
			$(".circle-"+gid+" .notify").addClass("on").text(gcount);
		});

		$(".group-widget-entry .notify").removeClass("on");
		$(data).find("group").each(function() {
			var fid = this.id;
			var fcount = this.innerHTML;
			$(".group-"+fid+" .notify").addClass("on").text(fcount);
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

	$('.circle-edit-icon').hover(
		function() {
			$(this).addClass('icon'); $(this).removeClass('iconspacer');},
		function() {
			$(this).removeClass('icon'); $(this).addClass('iconspacer');}
		);

	$('.sidebar-circle-element').hover(
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

// @license-end
