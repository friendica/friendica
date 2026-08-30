{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<h3>{{$title}}</h3>
<div class="settings-section">
	{{foreach $options as $o}}
		<dl>
    		<dt><a href="{{$o.0}}">{{$o.1}}</a></dt>
   		 <dd>{{$o.2}}</dd>
		</dl>
	{{/foreach}}
</div>
