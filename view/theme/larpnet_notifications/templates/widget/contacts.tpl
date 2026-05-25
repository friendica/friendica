{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div id="contact-block">
{{if $micropro}}
	<a id="contact-block-view-contacts" href="profile/{{$nickname}}/contacts">
		<h3 class="contact-block-h4">{{$contacts}}</h3>
		<span class="sr-only">{{$viewcontacts}}</span>
	</a>
{{else}}
	<h3 class="contact-block-h4">{{$contacts}}</h3>
{{/if}}

{{if $micropro}}
	<div class='contact-block-content'>
	{{foreach $micropro as $m}}
		{{$m nofilter}}
	{{/foreach}}
	</div>
{{/if}}
</div>
<div class="clear"></div>
