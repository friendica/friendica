var ispublic = '$ispublic';

$(document).ready(function() {
	
	/* enable tinymce on focus and click */
	$("#profile-jot-text").focus(enableOnUser);
	$("#profile-jot-text").click(enableOnUser);

	var uploader = new window.AjaxUpload(
		'wall-image-upload',
		{ action: 'wall_upload/$nickname',
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
		{ action: 'wall_attach/$nickname',
			name: 'userfile',
			onSubmit: function(file,ext) { $('#profile-rotator').show(); },
			onComplete: function(file,response) {
				addeditortext(response);
				$('#profile-rotator').hide();
			}				 
		}
	);


});

function deleteCheckedItems() {
	var checkedstr = '';

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

function jotGetLink() {
	reply = prompt("$linkurl");
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
	reply = prompt("$vidurl");
	if(reply && reply.length) {
		addeditortext('[video]' + reply + '[/video]');
	}
}

function jotAudioURL() {
	reply = prompt("$audurl");
	if(reply && reply.length) {
		addeditortext('[audio]' + reply + '[/audio]');
	}
}


function jotGetLocation() {
	reply = prompt("$whereareu", $('#jot-location').val());
	if(reply && reply.length) {
		$('#jot-location').val(reply);
	}
}

function jotShare(id) {
	if ($('#jot-popup').length != 0) $('#jot-popup').show();

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
	reply = prompt("$term");
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
		$.fancybox(data);
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
				$.fancybox.close();
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
	if(plaintext == 'none') {
		var currentText = $("#profile-jot-text").val();
		$("#profile-jot-text").val(currentText + data);
	}
	else
		tinyMCE.execCommand('mceInsertRawHTML',false,data);
}	

$geotag
