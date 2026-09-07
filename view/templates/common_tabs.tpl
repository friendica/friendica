{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<ul role="tablist" class="tabs">
	{{foreach $tabs as $tab}}
		<li id="{{$tab.id}}"><a role="tab" aria-selected="{{if $tab.sel}}true{{else}}false{{/if}}" href="{{$tab.url}}" {{if $tab.accesskey}}accesskey="{{$tab.accesskey}}"{{/if}} class="tab button {{$tab.sel}}"{{if $tab.title}} title="{{$tab.title}}"{{/if}}>{{$tab.label}}</a></li>
	{{/foreach}}
</ul>
