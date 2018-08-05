
<script type="text/javascript">
	$("#comment-edit-text-input").editor_autocomplete(baseurl+"/acl");

	$(document).ready(function() {
		$("#comment-edit-text-input").bbco_autocomplete('bbcode');
		$('#mail-conversation').perfectScrollbar();
		$('#message-preview').perfectScrollbar();
		// Scroll to the bottom of the mail conversation.
		var $el = $('#mail-conversation');
		if ($el.length) {
			$el.scrollTop($el.get(0).scrollHeight);
		}
	});
</script>
