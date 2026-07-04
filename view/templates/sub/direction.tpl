{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
{{if $direction.direction > 0}}
<span class="direction">
	&bull;
	{{if $direction.direction == 1}}
		<i class="ri ri-inbox-line" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 2}}
		<i class="ri ri-download-line" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 3}}
		<i class="ri ri-repeat-line" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 4}}
		<i class="ri ri-hashtag" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 5}}
		<i class="ri ri-chat-1-line" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 6}}
		<i class="ri ri-user-line" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 7}}
		<i class="ri ri-at-line" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 8}}
		<i class="ri ri-save-line" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 9}}
		<i class="ri ri-server-line" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 10}}
		<i class="ri ri-broadcast-line" aria-hidden="true" title="{{$direction.title}}"></i>
	{{/if}}
</span>
{{/if}}
