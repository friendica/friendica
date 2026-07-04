{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
{{if $direction.direction > 0}}
	<span class="direction" title="{{$direction.title}}">
		<span aria-hidden="true">&bull;</span>
		{{if $direction.direction == 1}}
			<i class="ri ri-inbox-line"></i>
		{{elseif $direction.direction == 2}}
			<i class="ri ri-download-line"></i>
		{{elseif $direction.direction == 3}}
			<i class="ri ri-repeat-line"></i>
		{{elseif $direction.direction == 4}}
			<i class="ri ri-hashtag"></i>
		{{elseif $direction.direction == 5}}
			<i class="ri ri-chat-1-line"></i>
		{{elseif $direction.direction == 6}}
			<i class="ri ri-user-line"></i>
		{{elseif $direction.direction == 7}}
			<i class="ri ri-at-line"></i>
		{{elseif $direction.direction == 8}}
			<i class="ri ri-save-line"></i>
		{{elseif $direction.direction == 9}}
			<i class="ri ri-global-line"></i>
		{{elseif $direction.direction == 10}}
			<i class="ri ri-server-line"></i>
		{{elseif $direction.direction == 11}}
			<i class="ri ri-broadcast-line"></i>
		{{/if}}
	</span>
{{/if}}