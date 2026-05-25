{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div class="pull-left">
	<h2 class="heading">{{$messages}}</h2>
</div>

<div id="message-new" class="pull-right">
	{{if $button.sel == "new"}}
	<a href="{{$button.url}}" accesskey="m" class="btn btn-primary newmessage-selected page-action" data-toggle="tooltip">
		<i class="fa fa-plus"></i>
		<span>{{$button.label}}</span>
	</a>
	{{else}}
	<a href="{{$button.url}}" title="{{$button.label}}" class="faded-icon page-action" data-toggle="tooltip">
		<i class="fa fa-close"></i>
	</a>
	{{/if}}
</div>

<div class="clear"></div>
