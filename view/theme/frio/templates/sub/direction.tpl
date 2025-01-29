{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
{{if $direction.direction > 0}}
<span class="direction">
	<span aria-hidden="true">&bull;</span>
	{{if $direction.direction == 1}}
		<i class="fa fa-inbox" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 2}}
		<i class="fa fa-download" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 3}}
		<i class="fa fa-retweet" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 4}}
		<i class="fa fa-hashtag" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 5}}
		<i class="fa fa-comment-o" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 6}}
		<i class="fa fa-user" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 7}}
		<i class="fa fa-at" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 8}}
		<i class="fa fa-share" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 9}}
		<i class="fa fa-globe" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 10}}
		<i class="fa fa-inbox" aria-hidden="true" title="{{$direction.title}}"></i>
	{{/if}}
</span>
{{/if}}
