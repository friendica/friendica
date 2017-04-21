$(document).ready(function() {

	window.navMenuTimeout = {
		'#network-menu-list-timeout': null,
		'#contacts-menu-list-timeout': null,
		'#system-menu-list-timeout': null,
		'#network-menu-list-opening': false,
		'#contacts-menu-list-opening': false,
		'#system-menu-list-opening': false,
		'#network-menu-list-closing': false,
		'#contacts-menu-list-closing': false,
		'#system-menu-list-closing': false
	};

	/* enable editor on focus and click */
	$("#profile-jot-text").focus(enableOnUser);
	$("#profile-jot-text").click(enableOnUser);

	$('.nav-menu-list, .nav-menu-icon').hover(function() {
		showNavMenu($(this).attr('point'));
	}, function() {
		hideNavMenu($(this).attr('point'));
	});

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

	$('#id_share').change(function() {

		if ($('#id_share').is(':checked')) {
			$('#acl-wrapper').show();
		} else {
			$('#acl-wrapper').hide();
		}
	}).trigger('change');

	if (typeof window.AjaxUpload != "undefined") {
		var uploader = new window.AjaxUpload(
			window.imageUploadButton,
			{ action: 'wall_upload/'+window.nickname,
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
				{ action: 'wall_attach/'+window.nickname,
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
			loading: function(isLoading, view) {
				if (!isLoading) {
					$('td.fc-day').dblclick(function() { window.location.href='/events/new?start='+$(this).data('date'); });
				}
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

	// Add Colorbox for viewing Network page images
	//var cBoxClasses = new Array();
	$(".wall-item-body a img").each(function() {
		var aElem = $(this).parent();
		var imgHref = aElem.attr("href");

		// We need to make sure we only put a Colorbox on links to Friendica images
		// We'll try to do this by looking for links of the form
		// .../photo/ab803d8eg08daf85023adfec08 (with nothing more following), in hopes
		// that that will be unique enough
		if (imgHref.match(/\/photo\/[a-fA-F0-9]+(-[0-9]\.[\w]+?)?$/)) {

			// Add a unique class to all the images of a certain post, to allow scrolling through
			var cBoxClass = $(this).closest(".wall-item-body").attr("id") + "-lightbox";
			$(this).addClass(cBoxClass);

			aElem.colorbox({
				maxHeight: '90%',
				photo: true, // Colorbox doesn't recognize a URL that don't end in .jpg, etc. as a photo
				rel: cBoxClass //$(this).attr("class").match(/wall-item-body-[\d]+-lightbox/)[0]
			});
		}
	});
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


$(function() {

	$("#cnftheme").click(function() {
		$.colorbox({
			width: 800,
			height: '90%',
			href: baseurl + "/admin/themes/" + $("#id_theme :selected").val(),
			onComplete: function() {
				$("div#fancybox-content form").submit(function(e) {
					var url = $(this).attr('action');
					// can't get .serialize() to work...
					var data={};
					$(this).find("input").each(function() {
						data[$(this).attr('name')] = $(this).val();
					});
					$(this).find("select").each(function() {
						data[$(this).attr('name')] = $(this).children(":selected").val();
					});
					console.log(":)", url, data);

					$.post(url, data, function(data) {
						if (timer) {
							clearTimeout(timer);
						}
						NavUpdate();
						$.colorbox.close();
					})

					return false;
				});

			}
		});
		return false;
	});
});


function homeRedirect() {
	$('html').fadeOut('slow', function() {
		window.location = baseurl + "/login";
	});
}


if (typeof window.photoEdit != 'undefined') {

	$(document).keydown(function(event) {

			if (window.prevLink != '') { if (event.ctrlKey && event.keyCode == 37) { event.preventDefault(); window.location.href = window.prevLink; }}
			if (window.nextLink != '') { if (event.ctrlKey && event.keyCode == 39) { event.preventDefault(); window.location.href = window.nextLink; }}

	});
}

function showEvent(eventid) {
	$.get(
		baseurl + window.eventModuleUrl + '/?id=' + eventid,
		function(data) {
			$.colorbox({html:data});
			$.colorbox.resize();
		}
	);
}

function doEventPreview() {
        $('#event-edit-preview').val(1);
        $.post('events',$('#event-edit-form').serialize(), function(data) {
                $.colorbox({ html: data });
        });
        $('#event-edit-preview').val(0);
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

function showNavMenu(menuID) {

	if (window.navMenuTimeout[menuID + '-closing']) {
		window.navMenuTimeout[menuID + '-closing'] = false;
		clearTimeout(window.navMenuTimeout[menuID + '-timeout']);
	} else {
		window.navMenuTimeout[menuID + '-opening'] = true;

		window.navMenuTimeout[menuID + '-timeout'] = setTimeout( function () {
			$(menuID).slideDown('fast').show();
			window.navMenuTimeout[menuID + '-opening'] = false;
		}, 200);
	}
}

function hideNavMenu(menuID) {

	if (window.navMenuTimeout[menuID + '-opening']) {
		window.navMenuTimeout[menuID + '-opening'] = false;
		clearTimeout(window.navMenuTimeout[menuID + '-timeout']);
	} else {
		window.navMenuTimeout[menuID + '-closing'] = true;

		window.navMenuTimeout[menuID + '-timeout'] = setTimeout( function () {
			$(menuID).slideUp('fast');
			window.navMenuTimeout[menuID + '-closing'] = false;
		}, 500);
	}
}



/*
 * Editor
 */

var editor = false;
var textlen = 0;

function initEditor(callback) {
	if(editor == false) {
		$("#profile-jot-text-loading").show();

		$("#profile-jot-text-loading").hide();
		$("#profile-jot-text").css({ 'height': 200, 'color': '#000' });
		$("#profile-jot-text").editor_autocomplete(baseurl+"/acl");
		$(".jothidden").show();
		// setup acl popup
		$("a#jot-perms-icon").colorbox({
			'inline' : true,
			'transition' : 'elastic'
		});

		editor = true;
	}
	if (typeof callback != "undefined") {
		callback();
	}
}

function enableOnUser() {
	if (editor) {
		return;
	}
	$(this).val("");
	initEditor();
}

function msgInitEditor() {
	$("#prvmail-text").editor_autocomplete(baseurl+"/acl");
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

function jotClearLocation() {
	$('#jot-coord').val('');
	$('#profile-nolocation-wrapper').hide();
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


function linkdropper(event) {
	var linkFound = event.dataTransfer.types.contains("text/uri-list");
	if (linkFound)
		event.preventDefault();
}


function linkdrop(event) {
	var reply = event.dataTransfer.getData("text/uri-list");
	event.preventDefault();
	if (reply && reply.length) {
		reply = bin2hex(reply);
		$('#profile-rotator').show();
		$.get('parse_url?binurl=' + reply, function(data) {
			addeditortext(window.jotId, data);
			$('#profile-rotator').hide();
		});
	}
}


if (typeof window.geoTag === 'function') window.geoTag();


/*
 * Items
 */

function confirmDelete() { return confirm(window.delItem); }

function deleteCheckedItems(delID) {
	if (confirm(window.delItems)) {
		var checkedstr = '';

		$(delID).hide();
		$(delID + '-rotator').show();
		$('.item-select').each( function() {
			if ($(this).is(':checked')) {
				if (checkedstr.length != 0) {
					checkedstr = checkedstr + ',' + $(this).val();
				} else {
					checkedstr = $(this).val();
				}
			}
		});
		$.post('item', { dropitems: checkedstr }, function(data) {
			window.location.reload();
		});
	}
}

function itemTag(id) {
	reply = prompt(window.term);
	if (reply && reply.length) {
		reply = reply.replace('#','');
		if (reply.length) {

			commentBusy = true;
			$('body').css('cursor', 'wait');

			$.get('tagger/' + id + '?term=' + reply, NavUpdate);
			liking = 1;
		}
	}
}

function itemFiler(id) {

	var bordercolor = $("input").css("border-color");

	$.get('filer/', function(data) {
		$.colorbox({html:data});
		$.colorbox.resize();
		$("#id_term").keypress(function() {
			$(this).css("border-color",bordercolor);
		})
		$("#select_term").change(function() {
			$("#id_term").css("border-color",bordercolor);
		})

		$("#filer_save").click(function(e) {
			e.preventDefault();
			reply = $("#id_term").val();
			if (reply && reply.length) {
				commentBusy = true;
				$('body').css('cursor', 'wait');
				$.get('filer/' + id + '?term=' + reply, NavUpdate);
				liking = 1;
				$.colorbox.close();
			} else {
				$("#id_term").css("border-color","#FF0000");
			}
			return false;
		});
	});

}


/*
 * Comments
 */

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
	$("#comment-edit-bb-" + id).show();
}
function cmtBbClose(id) {
	$("#comment-edit-bb-" + id).hide();
}

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
