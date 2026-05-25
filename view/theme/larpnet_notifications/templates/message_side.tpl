{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<nav id="message-sidebar" class="widget">
	{{if $tabs}}
		<div id="message-preview" class="panel panel-default">
			<ul class="media-list">
				{{$tabs nofilter}}
			</ul>
		</div>
	{{/if}}

</nav>