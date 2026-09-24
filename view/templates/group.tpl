{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<div class="group-header">
		<a href="{{$profile}}" class="group-header-avatar"><img src="{{$thumb}}" alt="{{$title}}"></a>
		<div>
			<h1><a href="{{$profile}}">{{$title}}</a></h1>
			<span class="group-header-stat"><strong>{{$threads_count}}</strong> {{$threads_label}}</span>
			{{if !$readonly}}<span class="group-header-stat"><strong>{{$unread_count}}</strong> {{$unread}}</span>{{/if}}
		</div>
	</div>
	{{if $about}}<div class="group-header-about">{{$about nofilter}}</div>{{/if}}

	<form method="post" action="group/{{$id}}" class="group-toolbar" up-submit>
		<input type="hidden" name="form_security_token" value="{{$form_token}}">
		{{if $back_link}}<a href="{{$back_link}}" class="btn btn-default" data-spa-back><i class="ri ri-arrow-left-line" aria-hidden="true"></i> {{$back}}</a>{{/if}}
		{{if $readonly}}
			<a href="{{$follow}}" class="btn btn-primary"><i class="ri ri-user-add-line" aria-hidden="true"></i> {{$join}}</a>
			{{if $register}}<a href="register" class="btn btn-default"><i class="ri ri-user-line" aria-hidden="true"></i> {{$register}}</a>{{/if}}
			{{if $fetch}}<button type="submit" name="fetch" value="1" class="btn btn-default"><i class="ri ri-download-line" aria-hidden="true"></i> {{$fetch}}</button>{{/if}}
		{{else}}
			<button type="submit" class="btn btn-default"><i class="ri ri-check-double-line" aria-hidden="true"></i> {{$mark_seen}}</button>
		{{/if}}
	</form>
	{{$editor nofilter}}

	<div id="group-content" data-reload-after-post>
		{{if !$threads}}
			<p>{{$no_threads}}</p>
		{{else}}
			<ul class="group-list">
				{{foreach $threads as $thread}}
				<li class="group-list-entry">
					<a href="{{$thread.link}}" class="group-list-avatar"><img src="{{$thread.thumb}}" alt="{{$thread.author}}"></a>
					<div class="group-list-title">
						<a href="display/{{$thread.guid}}" class="group-list-name{{if $thread.unseen}} unseen{{/if}}">{{$thread.title}}</a>
						<div class="group-list-about"><a href="{{$thread.link}}">{{$thread.author}}</a>, {{$thread.created}}</div>
					</div>
					<div class="group-list-stats">
						<div class="group-list-stat"><span class="group-list-count">{{$thread.comments}}</span> {{$comments}}</div>
						{{if !$readonly}}<div class="group-list-stat{{if $thread.unread}} group-list-stat-unread{{/if}}"><span class="group-list-count">{{$thread.unread}}</span> {{$unread}}</div>{{/if}}
					</div>
					{{include file="group/latest.tpl" latest=$thread.latest empty=$no_comments}}
				</li>
				{{/foreach}}
			</ul>
		{{/if}}

		{{$paginate nofilter}}
	</div>
</div>
