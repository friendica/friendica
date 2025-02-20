{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<nav id="sidebar-photos-albums" class="widget">
	<div class="pull-left">
		<h3>{{$title}}</h3>
	</div>

	<div class="pull-right">
		{{if $can_post}}
			<div class="photos-upload-link">
				<a href="{{$upload.1}}" title="{{$upload.0}}" class="widget-action faded-icon" data-toggle="tooltip">
					<i class="fa fa-plus"></i>
				</a>
			</div>
		{{/if}}
	</div>

	<ul role="menubar" class="sidebar-photos-albums-ul clear">
		<li role="menuitem" class="sidebar-photos-albums-li">
			<a href="profile/{{$nick}}/photos" class="sidebar-photos-albums-element" title="{{$title}}">{{$recent}}</a>
		</li>

		{{if $albums}}
			{{foreach $albums as $al}}
				{{if $al.text}}
					<li role="menuitem" class="sidebar-photos-albums-li">
						<a href="photos/{{$nick}}/album/{{$al.bin2hex}}" class="sidebar-photos-albums-element">
							<span class="badge pull-right">{{$al.total}}</span>{{$al.text}}
						</a>
					</li>
				{{/if}}
			{{/foreach}}
		{{/if}}
	</ul>
</nav>