<script language="javascript" type="text/javascript">
	$("#prvmail-text").editor_autocomplete(baseurl + '/search/acl');
</script>
<script type="text/javascript" src="view/js/ajaxupload.js?v={{$smarty.const.FRIENDICA_VERSION}}"></script>
<script>
	$(document).ready(function() {
		var uploader = new window.AjaxUpload(
			'prvmail-upload',
			{ action: 'profile/{{$nickname}}/photos/upload',
				name: 'userfile',
				onSubmit: function(file,ext) { $('#profile-rotator').show(); },
				onComplete: function(file,response) {
					addeditortext(response);
					$('#profile-rotator').hide();
				}
			}
		);

		$("#prvmail-text").bbco_autocomplete('bbcode');
	});

	function jotGetLink() {
		reply = prompt("{{$linkurl}}");
		if(reply && reply.length) {
			$('#profile-rotator').show();
			$.get('parseurl?url=' + reply, function(data) {
				addeditortext(data);
				$('#profile-rotator').hide();
			});
		}
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
			$('#profile-rotator').show();
			$.get('parseurl?url=' + reply, function(data) {
				addeditortext(data);
				$('#profile-rotator').hide();
			});
		}
	}

	function addeditortext(data) {
		var currentText = $("#prvmail-text").val();
		$("#prvmail-text").val(currentText + data);
	}

</script>

