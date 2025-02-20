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
			<i class="fa fa-inbox"></i>
		{{elseif $direction.direction == 2}}
			<i class="fa fa-download"></i>
		{{elseif $direction.direction == 3}}
			<i class="fa fa-retweet"></i>
		{{elseif $direction.direction == 4}}
			<i class="fa fa-hashtag"></i>
		{{elseif $direction.direction == 5}}
			<i class="fa fa-comment-o"></i>
		{{elseif $direction.direction == 6}}
			<i class="fa fa-user"></i>
		{{elseif $direction.direction == 7}}
			<i class="fa fa-at"></i>
		{{elseif $direction.direction == 8}}
			<i class="fa fa-share"></i>
		{{elseif $direction.direction == 9}}
			<i class="fa fa-globe"></i>
		{{elseif $direction.direction == 10}}
			<i class="fa fa-inbox"></i>
		{{/if}}
	</span>
{{/if}}