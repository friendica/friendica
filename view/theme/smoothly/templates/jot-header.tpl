

<script language="javascript" type="text/javascript">

var editor = false;
var textlen = 0;

function initEditor(callback) {
	if (editor == false){
		$("#profile-jot-text-loading").show();
		$("#profile-jot-text-loading").hide();
		$("#profile-jot-text").css({ 'height': 200, 'color': '#000' });
		$("#profile-jot-text").editor_autocomplete(baseurl+"/acl");
		$("#profile-jot-text").bbco_autocomplete('bbcode');
		$(".jothidden").show();
		$("a#jot-perms-icon").colorbox({
			'inline' : true,
			'transition' : 'elastic'
		});
		$("#profile-jot-submit-wrapper").show();
	{{if $newpost}}
		$("#profile-upload-wrapper").show();
		$("#profile-attach-wrapper").show();
		$("#profile-link-wrapper").show();
		$("#profile-video-wrapper").show();
		$("#profile-audio-wrapper").show();
		$("#profile-location-wrapper").show();
		$("#profile-nolocation-wrapper").show();
		$("#profile-title-wrapper").show();
		$("#profile-jot-plugin-wrapper").show();
		$("#jot-preview-link").show();
	{{/if}}

		editor = true;
    }
	if (typeof callback != "undefined") {
		callback();
	}
} // initEditor

function enableOnUser(){
	if (editor) {
		return;
	}
	$(this).val("");
	initEditor();
}

</script>

<script type="text/javascript" src="js/ajaxupload.js" >
</script>

<script>
	var ispublic = '{{$ispublic}}';

	$(document).ready(function() {

		/* enable editor on focus and click */
		$("#profile-jot-text").focus(enableOnUser);
		$("#profile-jot-text").click(enableOnUser);

		var uploader = new window.AjaxUpload(
			'wall-image-upload',
			{ action: 'wall_upload/{{$nickname}}',
				name: 'userfile',
				onSubmit: function(file,ext) { $('#profile-rotator').show(); },
				onComplete: function(file,response) {
					addeditortext(response);
					$('#profile-rotator').hide();
				}
			}
		);

		var file_uploader = new window.AjaxUpload(
			'wall-file-upload',
			{ action: 'wall_attach/{{$nickname}}',
				name: 'userfile',
				onSubmit: function(file,ext) { $('#profile-rotator').show(); },
				onComplete: function(file,response) {
					addeditortext(response);
					$('#profile-rotator').hide();
				}
			}
		);
		$('#contact_allow, #contact_deny, #group_allow, #group_deny').change(function() {
			var selstr;
			$('#contact_allow option:selected, #contact_deny option:selected, #group_allow option:selected, #group_deny option:selected').each( function() {
				selstr = $(this).text();
				$('#jot-perms-icon').removeClass('unlock').addClass('lock');
				$('#jot-public').hide();
				$('.profile-jot-net input').attr('disabled', 'disabled');
			});
			if(selstr == null) {
				$('#jot-perms-icon').removeClass('lock').addClass('unlock');
				$('#jot-public').show();
				$('.profile-jot-net input').attr('disabled', false);
			}

		}).trigger('change');

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
			$.get('parse_url?binurl=' + reply, function(data) {
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

	function jotTitle() {
		reply = prompt("{{$title}}", $('#jot-title').val());
		if(reply && reply.length) {
			$('#jot-title').val(reply);
		}
	}

	function jotShare(id) {
		$('#like-rotator-' + id).show();
		$.get('share/' + id, function(data) {
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
			$.get('parse_url?binurl=' + reply, function(data) {
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

				$.get('tagger/' + id + '?term=' + reply);
				if(timer) clearTimeout(timer);
				timer = setTimeout(NavUpdate,3000);
				liking = 1;
			}
		}
	}

	function itemFiler(id) {

		var bordercolor = $("input").css("border-color");

		$.get('filer/', function(data){
			$.colorbox({html:data});
			$("#id_term").keypress(function(){
				$(this).css("border-color",bordercolor);
			})
			$("#select_term").change(function(){
				$("#id_term").css("border-color",bordercolor);
			})

			$("#filer_save").click(function(e){
				e.preventDefault();
				reply = $("#id_term").val();
				if(reply && reply.length) {
					commentBusy = true;
					$('body').css('cursor', 'wait');
					$.get('filer/' + id + '?term=' + reply, NavUpdate);
//					if(timer) clearTimeout(timer);
//					timer = setTimeout(NavUpdate,3000);
					liking = 1;
					$.colorbox.close();
				} else {
					$("#id_term").css("border-color","#FF0000");
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


	{{$geotag}}

</script>
