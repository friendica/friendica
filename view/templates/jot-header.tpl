{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}


<script language="javascript" type="text/javascript">

var editor = false;
var textlen = 0;

function initEditor(callback) {
	if (editor == false) {
		var  colorbox_options = {
			{{if $is_mobile}}
			'width' : '100%',
			'height' : '100%',
			{{/if}}
			'inline' : true,
			'transition' : 'elastic'
		}

		$("#profile-jot-text-loading").show();
		$("#profile-jot-text-loading").hide();
		$("#profile-jot-text").css({ 'height': 200, 'color': '#000' });
		$("#profile-jot-text").editor_autocomplete(baseurl + '/search/acl');
		$("#profile-jot-text").bbco_autocomplete('bbcode');
		$("a#jot-perms-icon").colorbox(colorbox_options);
		$(".jothidden").show();

		$("#profile-jot-text").keyup(function(){
			var textlen = $(this).val().length;
			$('#character-counter').text(textlen);
		});

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
	$(this).val('');
	initEditor();
}

</script>
<script type="text/javascript" src="{{$baseurl}}/view/js/ajaxupload.js?v={{$VERSION}}"></script>
<script>
	var ispublic = '{{$ispublic nofilter}}';


	$(document).ready(function() {

		/* enable editor on focus and click */
		$("#profile-jot-text").focus(enableOnUser);
		$("#profile-jot-text").click(enableOnUser);

		/* show images / file browser window
		 *
		 **/

		/* callback */
		$('body').on('fbrowser.photo.main', function(e, filename, embedcode, id) {
			$.colorbox.close();
			addeditortext(embedcode);
		});
		$('body').on('fbrowser.attachment.main', function(e, filename, embedcode, id) {
			$.colorbox.close();
			addeditortext(embedcode);
		});

		$('#wall-image-upload').on('click', function(){
			Dialog.doImageBrowser("main");
		});

		$('#wall-file-upload').on('click', function(){
			Dialog.doFileBrowser("main");
		});
	});


	function deleteCheckedItems() {
		if(confirm('{{$delitems}}')) {
			var checkedstr = '';

			$("#item-delete-selected").hide();
			$('#item-delete-selected-rotator').show();

			$('.item-select').each( function() {
				if($(this).is(':checked')) {
					if(checkedstr.length != 0) {
						checkedstr = checkedstr + ',' + $(this).val();
					}
					else {
						checkedstr = $(this).val();
					}
				}
			});
			$.post('item', { dropitems: checkedstr }, function(data) {
				window.location.reload();
			});
		}
	}

	function jotGetLink() {
		reply = prompt("{{$linkurl}}");
		if(reply && reply.length) {
			reply = bin2hex(reply);
			$('#profile-rotator').show();
			$.get('parseurl?binurl=' + reply, function(data) {
				addeditortext(data);
				$('#profile-rotator').hide();
			});
		}
	}

	function jotVideoURL() {
		reply = prompt("{{$vidurl}}");
		if(reply && reply.length) {
			addeditortext('[video]' + reply + '[/video]');
		}
	}

	function jotAudioURL() {
		reply = prompt("{{$audurl}}");
		if(reply && reply.length) {
			addeditortext('[audio]' + reply + '[/audio]');
		}
	}


	function jotGetLocation() {
		reply = prompt("{{$whereareu}}", $('#jot-location').val());
		if(reply && reply.length) {
			$('#jot-location').val(reply);
		}
	}

	function jotShare(id) {
		if ($('#jot-popup').length != 0) $('#jot-popup').show();

		$('#like-rotator-' + id).show();
		$.get('post/' + id + '/share', function(data) {
			if (!editor) $("#profile-jot-text").val("");
			initEditor(function(){
				addeditortext(data);
				$('#like-rotator-' + id).hide();
				$(window).scrollTop(0);
			});

		});
	}

	function linkdropper(event) {
		var linkFound = event.dataTransfer.types.contains("text/uri-list");
		if(linkFound)
			event.preventDefault();
	}

	function linkdrop(event) {
		var reply = event.dataTransfer.getData("text/uri-list");
		event.target.textContent = reply;
		event.preventDefault();
		if(reply && reply.length) {
			reply = bin2hex(reply);
			$('#profile-rotator').show();
			$.get('parseurl?binurl=' + reply, function(data) {
				if (!editor) $("#profile-jot-text").val("");
				initEditor(function(){
					addeditortext(data);
					$('#profile-rotator').hide();
				});
			});
		}
	}

	function itemTag(id) {
		reply = prompt("{{$term}}");
		if(reply && reply.length) {
			reply = reply.replace('#','');
			if(reply.length) {

				commentBusy = true;
				$('body').css('cursor', 'wait');

				$.post('post/' + id + '/tag/add', {term: reply});
				if(timer) clearTimeout(timer);
				timer = setTimeout(NavUpdate,3000);
				liking = 1;
			}
		}
	}

	function itemFiler(id) {
		const bordercolor = $("input").css("border-color");

		$.get('filer/', function (data) {
			$.colorbox({html: data});
			$("#id_term").keypress(function () {
				$(this).css("border-color", bordercolor);
			})
			$("#select_term").change(function () {
				$("#id_term").css("border-color", bordercolor);
			})

			$("#filer_save").click(function (e) {
				e.preventDefault();
				const reply = $("#id_term").val();
				if (reply && reply.length) {
					commentBusy = true;
					formModified = true;
					$('body').css('cursor', 'wait');
					$.get('filer/' + id + '?term=' + reply)
						.done(function () {
							$.colorbox.close();
							resetFormModifiedFlag();
						})
						.always(function () {
							liking = 1;
							force_update = true;
							update_item = id;
							NavUpdate();
						});
				} else {
					$("#id_term").css("border-color", "#FF0000");
				}

				return false;
			});
		});
	}

	function jotClearLocation() {
		$('#jot-coord').val('');
		$('#profile-nolocation-wrapper').hide();
	}

	function addeditortext(data) {
		var currentText = $("#profile-jot-text").val();
		$("#profile-jot-text").val(currentText + data);
	}

	{{$geotag nofilter}}

</script>

