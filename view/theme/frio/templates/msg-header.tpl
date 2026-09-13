{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<script type="text/javascript">
	$("#comment-edit-text-input").editor_autocomplete(baseurl + '/search/acl');

	window.onDocumentReady('body', function() {
		$("#comment-edit-text-input").bbco_autocomplete('bbcode');
		$('#mail-conversation').perfectScrollbar();
		$('#message-preview').perfectScrollbar();
		// Scroll to the bottom of the mail conversation.
		var $el = $('#mail-conversation');
		if ($el.length) {
			$el.scrollTop($el.get(0).scrollHeight);
		}

		// Validate message form - disable submit button if message is empty
		function validateMessageForm() {
			var messageText = $('#comment-edit-text-input').val().trim();
			$('#prvmail-submit').prop('disabled', messageText.length === 0);
		}

		// Check on page load and on input
		validateMessageForm();
		$('#comment-edit-text-input').on('input keyup', validateMessageForm);
	});
</script>
