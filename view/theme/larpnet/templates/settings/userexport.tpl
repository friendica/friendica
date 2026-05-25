{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div class="generic-page-wrapper">
	{{* include the title template for the settings title *}}
	{{include file="section_title.tpl" title=$title}}

	{{foreach $options as $o}}
	<dl>
		<dt><a href="{{$o.0}}">{{$o.1}}</a></dt>
		<dd>{{$o.2}}</dd>
	</dl>
	{{/foreach}}
</div>
