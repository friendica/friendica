{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>
{{if $pcount eq 0}}
	<div class="error-message">
	{{$noplugshint}}
	</div>
{{else}}
	<p><a class="btn btn-primary" href="{{$baseurl}}/admin/{{$function}}?action=reload&amp;t={{$form_security_token}}">{{$reload}}</a></p>
	<ul id="addonslist">
	{{foreach $addons as $p}}
		<li class="addon {{$p.1}}">
			<span class="offset-anchor" id="{{$p.0}}"></span>
			<a class="toggleaddon" href="{{$baseurl}}/admin/{{$function}}?action=toggle&amp;addon={{$p.0}}&amp;t={{$form_security_token}}#{{$p.0}}" title="{{if $p.1==on}}Disable{{else}}Enable{{/if}}">
				<span class="icon {{$p.1}}"></span>
			</a>
			<a href="{{$baseurl}}/admin/{{$function}}/{{$p.0}}"><span class="name">{{$p.2.name}}</span></a> - <span class="version">{{$p.2.version}}</span>
			{{if $p.2.experimental}} {{$experimental}} {{/if}}{{if $p.2.unsupported}} {{$unsupported}} {{/if}}
			<div class="desc">{{$p.2.description nofilter}}</div>
		</li>
	{{/foreach}}
	</ul>
{{/if}}
</div>
