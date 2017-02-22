$(document).ready(function() {

	/* enable editor on focus and click */
	$("#profile-jot-text").focus(enableOnUser);
	$("#profile-jot-text").click(enableOnUser);

	$('#event-share-checkbox').change(function() {
		if ($('#event-share-checkbox').is(':checked')) {
			$('#acl-wrapper').show();
		}
		else {
			$('#acl-wrapper').hide();
		}
	}).trigger('change');

	$(".popupbox").click(function () {
		var parent = $( $(this).attr('href') ).parent();
		if (parent.css('display') == 'none') {
			parent.show();
		} else {
			parent.hide();
		}
		return false;
	});



	if (typeof window.AjaxUpload != "undefined") {
		var uploader = new window.AjaxUpload(
			window.imageUploadButton,
			{ action: 'wall_upload/' + window.nickname,
				name: 'userfile',
				onSubmit: function(file,ext) { $('#profile-rotator').show(); },
				onComplete: function(file,response) {
					addeditortext(window.jotId, response);
					$('#profile-rotator').hide();
				}
			}
		);

		if ($('#wall-file-upload').length) {
			var file_uploader = new window.AjaxUpload(
				'wall-file-upload',
				{ action: 'wall_attach/' + window.nickname,
					name: 'userfile',
					onSubmit: function(file,ext) { $('#profile-rotator').show(); },
					onComplete: function(file,response) {
						addeditortext(window.jotId, response);
						$('#profile-rotator').hide();
					}
				}
			);
		}
	}


	if (typeof window.aclInit !="undefined" && typeof acl=="undefined") {
		acl = new ACL(
			baseurl+"/acl",
			[ window.allowCID,window.allowGID,window.denyCID,window.denyGID ]
		);
	}

	switch(window.autocompleteType) {
		case 'msg-header':
			$("#recip").name_autocomplete(baseurl + '/acl', '', false, function(data) {
					$("#recip-complete").val(data.id);
			});
			break;
		case 'contacts-head':
			$("#contacts-search").contact_autocomplete(baseurl + '/acl', 'a', true);


			$("#contacts-search").keyup(function(event) {
				if (event.keyCode == 13) {
					$("#contacts-search").click();
				}
			});
			$(".autocomplete-w1 .selected").keyup(function(event) {
				if (event.keyCode == 13) {
					$("#contacts-search").click();
				}
			});
			break;
		case 'display-head':
			$(".comment-wwedit-wrapper textarea").editor_autocomplete(baseurl+"/acl");
			break;
		default:
			break;
	}

	if (window.aclType == "settings-head" || window.aclType == "photos_head" || window.aclType == "event_head") {
		$('#contact_allow, #contact_deny, #group_allow, #group_deny').change(function() {
			var selstr;
			$('#contact_allow option:selected, #contact_deny option:selected, #group_allow option:selected, #group_deny option:selected').each( function() {
				selstr = $(this).text();
				$('#jot-perms-icon').removeClass('unlock').addClass('lock');
				$('#jot-public').hide();
			});
			if (selstr == null) {
				$('#jot-perms-icon').removeClass('lock').addClass('unlock');
				$('#jot-public').show();
			}

		}).trigger('change');
	}

	if (window.aclType == "event_head") {
		$('#events-calendar').fullCalendar({
			events: baseurl + window.eventModuleUrl +'/json/',
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'month,agendaWeek,agendaDay'
			},
			timeFormat: 'H(:mm)',
			eventClick: function(calEvent, jsEvent, view) {
				showEvent(calEvent.id);
			},

			eventRender: function(event, element, view) {
				//console.log(view.name);
				if (event.item['author-name']==null) return;
				switch(view.name) {
					case "month":
					element.find(".fc-title").html(
						"<img src='{0}' style='height:10px;width:10px'>{1} : {2}".format(
							event.item['author-avatar'],
							event.item['author-name'],
							event.title
					));
					break;
					case "agendaWeek":
					element.find(".fc-title").html(
						"<img src='{0}' style='height:12px; width:12px'>{1}<p>{2}</p><p>{3}</p>".format(
							event.item['author-avatar'],
							event.item['author-name'],
							event.item.desc,
							event.item.location
					));
					break;
					case "agendaDay":
					element.find(".fc-title").html(
						"<img src='{0}' style='height:24px;width:24px'>{1}<p>{2}</p><p>{3}</p>".format(
							event.item['author-avatar'],
							event.item['author-name'],
							event.item.desc,
							event.item.location
					));
					break;
				}
			}

		});

		// center on date
		var args=location.href.replace(baseurl,"").split("/");
		if (args.length>=5 && window.eventModeParams == 2) {
			$("#events-calendar").fullCalendar('gotoDate',args[3] , args[4]-1);
		} else if (args.length>=4 && window.eventModeParams == 1) {
			$("#events-calendar").fullCalendar('gotoDate',args[2] , args[3]-1);
		}

		// show event popup
		var hash = location.hash.split("-")
		if (hash.length==2 && hash[0]=="#link") showEvent(hash[1]);
	}

});

// update pending count //
$(function() {

	$("nav").bind('nav-update',  function(e,data) {
		var elm = $('#pending-update');
		var register = $(data).find('register').text();
		if (register=="0") { register=""; elm.hide();} else { elm.show(); }
		elm.html(register);
	});
});



function homeRedirect() {
	$('html').fadeOut('slow', function() {
		window.location = baseurl + "/login";
	});
}


function initCrop() {
	function onEndCrop( coords, dimensions ) {
		$PR( 'x1' ).value = coords.x1;
		$PR( 'y1' ).value = coords.y1;
		$PR( 'x2' ).value = coords.x2;
		$PR( 'y2' ).value = coords.y2;
		$PR( 'width' ).value = dimensions.width;
		$PR( 'height' ).value = dimensions.height;
	}

	Event.observe( window, 'load', function() {
		new Cropper.ImgWithPreview(
		'croppa',
		{
			previewWrap: 'previewWrap',
			minWidth: 175,
			minHeight: 175,
			maxWidth: 640,
			maxHeight: 640,
			ratioDim: { x: 100, y:100 },
			displayOnInit: true,
			onEndCrop: onEndCrop
		});
	});
}


function showEvent(eventid) {
}

/*
 * Editor
 */
var editor = false;
var textlen = 0;

function initEditor(callback){
	if (editor == false) {
		$("#profile-jot-text").css({ 'height': 200, 'color': '#000' });
		$("#profile-jot-text").editor_autocomplete(baseurl+"/acl");
		$("a#jot-perms-icon, a#settings-default-perms-menu").click(function () {
			var parent = $("#profile-jot-acl-wrapper").parent();
			if (parent.css('display') == 'none') {
				parent.show();
			} else {
				parent.hide();
			}
			return false;
		});
		$(".jothidden").show();

		editor = true;
	}
	if (typeof callback != "undefined") {
		callback();
	}
}

function enableOnUser(){
	if (editor) {
		return;
	}
	$(this).val("");
	initEditor();
}

/*
 * Jot
 */

function addeditortext(textElem, data) {
	var currentText = $(textElem).val();
	$(textElem).val(currentText + data);
}

function jotVideoURL() {
	reply = prompt(window.vidURL);
	if (reply && reply.length) {
		addeditortext("#profile-jot-text", '[video]' + reply + '[/video]');
	}
}

function jotAudioURL() {
	reply = prompt(window.audURL);
	if (reply && reply.length) {
		addeditortext("#profile-jot-text", '[audio]' + reply + '[/audio]');
	}
}


function jotGetLocation() {
	reply = prompt(window.whereAreU, $('#jot-location').val());
	if (reply && reply.length) {
		$('#jot-location').val(reply);
	}
}

function jotShare(id) {
	if ($('#jot-popup').length != 0) $('#jot-popup').show();

	$('#like-rotator-' + id).show();
	$.get('share/' + id, function(data) {
		if (!editor) $("#profile-jot-text").val("");
		initEditor(function() {
			addeditortext("#profile-jot-text", data);
			$('#like-rotator-' + id).hide();
			$(window).scrollTop(0);
		});

	});
}

function jotGetLink() {
	reply = prompt(window.linkURL);
	if (reply && reply.length) {
		reply = bin2hex(reply);
		$('#profile-rotator').show();
		$.get('parse_url?binurl=' + reply, function(data) {
			addeditortext(window.jotId, data);
			$('#profile-rotator').hide();
		});
	}
}

function jotClearLocation() {
	$('#jot-coord').val('');
	$('#profile-nolocation-wrapper').hide();
}

if (typeof window.geoTag === 'function') window.geoTag();



/*
 * Items
 */

function confirmDelete() { return confirm(window.delItem); }

function itemTag(id) {
	reply = prompt(window.term);
	if (reply && reply.length) {
		reply = reply.replace('#','');
		if (reply.length) {

			commentBusy = true;
			$('body').css('cursor', 'wait');

			$.get('tagger/' + id + '?term=' + reply, NavUpdate);
			/*if (timer) clearTimeout(timer);
			timer = setTimeout(NavUpdate,3000);*/
			liking = 1;
		}
	}
}

function itemFiler(id) {
	$.get('filer/', function(data) {

		var promptText = $('#id_term_label', data).text();

		reply = prompt(promptText);
		if (reply && reply.length) {
			commentBusy = true;
			$('body').css('cursor', 'wait');
			$.get('filer/' + id + '?term=' + reply, NavUpdate);
			liking = 1;
		}
	});
}

/*
 * Comments
 */
function commentOpen(obj,id) {
	if (obj.value == "") {
		$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
		$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
		$("#mod-cmnt-wrap-" + id).show();
		openMenu("comment-edit-submit-wrapper-" + id);
	}
}
function commentClose(obj,id) {
	if (obj.value == "") {
		$("#comment-edit-text-" + id).removeClass("comment-edit-text-full");
		$("#comment-edit-text-" + id).addClass("comment-edit-text-empty");
		$("#mod-cmnt-wrap-" + id).hide();
		closeMenu("comment-edit-submit-wrapper-" + id);
	}
}


function commentInsert(obj,id) {
	var tmpStr = $("#comment-edit-text-" + id).val();
	if (tmpStr == "") {
		$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
		$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
		openMenu("comment-edit-submit-wrapper-" + id);
	}
	var ins = $(obj).html();
	ins = ins.replace("&lt;","<");
	ins = ins.replace("&gt;",">");
	ins = ins.replace("&amp;","&");
	ins = ins.replace("&quot;",'"');
	$("#comment-edit-text-" + id).val(tmpStr + ins);
}

function qCommentInsert(obj,id) {
	var tmpStr = $("#comment-edit-text-" + id).val();
	if (tmpStr == "") {
		$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
		$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
		openMenu("comment-edit-submit-wrapper-" + id);
	}
	var ins = $(obj).val();
	ins = ins.replace("&lt;","<");
	ins = ins.replace("&gt;",">");
	ins = ins.replace("&amp;","&");
	ins = ins.replace("&quot;",'"');
	$("#comment-edit-text-" + id).val(tmpStr + ins);
	$(obj).val("");
}

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
			selected.text = "["+BBcode+"=http://]" +  selected.text + "[/"+BBcode+"]";
		} else {
			selected.text = "["+BBcode+"]" + selected.text + "[/"+BBcode+"]";
		}
	} else if (textarea.selectionStart || textarea.selectionStart == "0") {
		var start = textarea.selectionStart;
		var end = textarea.selectionEnd;
		if (BBcode == "url") {
			textarea.value = textarea.value.substring(0, start) + "["+BBcode+"=http://]" + textarea.value.substring(start, end) + "[/"+BBcode+"]" + textarea.value.substring(end, textarea.value.length);
		} else {
			textarea.value = textarea.value.substring(0, start) + "["+BBcode+"]" + textarea.value.substring(start, end) + "[/"+BBcode+"]" + textarea.value.substring(end, textarea.value.length);
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
