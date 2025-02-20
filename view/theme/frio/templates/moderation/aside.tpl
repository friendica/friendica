{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<script type="text/javascript">
	// update pending count //
	$(function() {
		$('#topbar-first').bind('nav-update', function(e, data) {
			var elm = $('#pending-update');
			var register = parseInt($(data).find('register').text());
			if (register > 0) {
				elm.html(register);
			}
		});
	});
</script>

{{foreach $subpages as $page}}
	<nav class="widget">
		<h3>{{$page.0}}</h3>
		<ul>
			{{foreach $page.1 as $item}}
				<li class="{{$item.2}}">
					<a href="{{$item.0}}" {{if $item.accesskey}}accesskey="{{$item.accesskey}}" {{/if}}>
						{{$item.1}}
						{{if $name == "users"}}
							<span id="pending-update" class="badge pull-right"></span>
						{{/if}}
					</a>
				</li>
			{{/foreach}}
		</ul>
	</nav>
{{/foreach}}