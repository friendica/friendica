
<script language="javascript" type="text/javascript">
	$("#comment-edit-text-input").editor_autocomplete(baseurl+"/acl");
</script>

<script>
	$(document).ready(function() {
		$("#comment-edit-text-input").bbco_autocomplete('bbcode');
		$('#mail-conversation').perfectScrollbar();
		$('#message-preview').perfectScrollbar();
		$('#mail-conversation').scrollTop($('#mail-conversation')[0].scrollHeight);
	});
</script>

