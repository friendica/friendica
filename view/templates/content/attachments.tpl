{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<details class="body-attach">
	<summary>{{$summary}}</summary>
	{{foreach from=$attachments item=attachment}}
		<a href="{{$attachment.url}}" class="attachlink" target="_blank" rel="noopener noreferrer">
			{{if $attachment.class}}<span class="attachtype {{$attachment.class}}"></span>{{/if}}
			<span class="attachname">{{$attachment.name}}</span>
			{{if $attachment.size}}<span class="attachsize">{{$attachment.size}}</span>{{/if}}
		</a>
	{{/foreach}}
</details>
