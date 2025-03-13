{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<nav class="widget{{if $class}} {{$class}}{{/if}}">
	{{if $title}}<h3>{{$title}}</h3>{{/if}}
	{{if $desc}}<div class="desc">{{$desc nofilter}}</div>{{/if}}

	<ul>
		{{foreach $items as $item}}
			<li class="tool"><a href="{{$item.url}}" {{if $item.accesskey}}accesskey="{{$item.accesskey}}" {{/if}} class="{{if $item.selected}}selected{{/if}}">{{$item.label}}</a></li>
		{{/foreach}}
	</ul>

</nav>