{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<nav>
	<ul class="nav nav-tabs">
	{{foreach $tabs as $tab}}
		<li id="{{$tab.id}}"{{if $tab.sel}} class="{{$tab.sel}}"{{/if}}>
			<a{{if $tab.sel}} aria-current="page"{{/if}} href="{{$tab.url}}"{{if $tab.accesskey}} accesskey="{{$tab.accesskey}}"{{/if}}{{if $tab.title}} title="{{$tab.title}}"{{/if}}>{{$tab.label}}</a>
		</li>
	{{/foreach}}
	</ul>
</nav>
