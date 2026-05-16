{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="exception" class="generic-page-wrapper">
    <img class="hare" src="images/friendica-404_svg_flaxy-o-hare.png" title="Flaxy O'Hare"/>
    <h1>{{$title}}</h1>
    <p>{{$message}}</p>
{{if $thrown}}
	<pre>{{$thrown}}
{{$stack_trace}}
{{$trace}}</pre>
{{/if}}
{{if $request_id}}
	<pre>Request: {{$request_id}}</pre>
{{/if}}
{{if $back}}
	<p><button type="button" onclick="window.history.back()" class="btn btn-primary">{{$back}}</button></p>
{{/if}}
</div>
