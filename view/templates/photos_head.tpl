{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}


<script>

	var ispublic = "{{$ispublic nofilter}}";


	window.onDocumentReady('body', function() {

		$('#contact_allow, #contact_deny, #circle_allow, #circle_deny')
			.off('change.photos-head')
			.on('change.photos-head', function() {
			var selstr;
			$('#contact_allow option:selected, #contact_deny option:selected, #circle_allow option:selected, #circle_deny option:selected').each( function() {
				selstr = $(this).html();
				$('#jot-perms-icon').removeClass('unlock').addClass('lock');
				$('#jot-public').hide();
			});
			if(selstr == null) {
				$('#jot-perms-icon').removeClass('lock').addClass('unlock');
				$('#jot-public').show();
			}

		}).trigger('change');

	});

</script>

