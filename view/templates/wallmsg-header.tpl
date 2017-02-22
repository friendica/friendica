<script language="javascript" type="text/javascript">
	$("#prvmail-text").editor_autocomplete(baseurl+"/acl");
</script>
<script>

	function jotGetLink() {
		reply = prompt("{{$linkurl}}");
		if(reply && reply.length) {
			$('#profile-rotator').show();
			$.get('parse_url?url=' + reply, function(data) {
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
			$.get('parse_url?url=' + reply, function(data) {
				addeditortext(data);
				$('#profile-rotator').hide();
			});
		}
	}

</script>

