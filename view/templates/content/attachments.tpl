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
			<span class="attachtype attach-{{$attachment.kind}}" role="img" title="{{$attachment.type}}" aria-label="{{$attachment.type}}"></span>
			<span class="attachname">{{$attachment.name}}</span>
			<span class="attachsize"><span class="attachkind">{{$attachment.type}}{{if $attachment.size}} · {{/if}}</span>{{$attachment.size}}</span>
		</a>
	{{/foreach}}
{{if $collapsible}}
</details>
{{else}}
</div>
{{/if}}
