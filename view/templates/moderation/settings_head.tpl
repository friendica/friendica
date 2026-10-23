{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<script>
	window.onDocumentReady('body', function() {
		$('.settings-content-block').hide();
		$('.settings-heading').click(function(){
			$('.settings-content-block').hide();
			$(this).next('.settings-content-block').toggle();
		});
	});
</script>
