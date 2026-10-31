{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
{{if $collapsible}}
<details class="body-attach"{{if $open}} open{{/if}}>
	<summary>{{$summary}}</summary>
{{else}}
<div class="body-attach">
{{/if}}
	{{foreach from=$attachments item=attachment}}
		<a href="{{$attachment.url}}" class="attachlink" target="_blank" rel="noopener noreferrer">
			{{if $attachment.class}}<span class="attachtype {{$attachment.class}}"></span>{{/if}}
			<span class="attachname">{{$attachment.name}}</span>
			{{if $attachment.size}}<span class="attachsize">{{$attachment.size}}</span>{{/if}}
		</a>
	{{/foreach}}
{{if $collapsible}}
</details>
{{else}}
</div>
{{/if}}
