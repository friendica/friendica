{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="friendica">
	<h1>Friendica</h1>
	<p>{{$about nofilter}}</p>
	<p>{{$friendica nofilter}}</p>
	<p>{{$bugs nofilter}}</p>
	<p>{{$info nofilter}}</p>

	<p>{{$visible_addons.title nofilter}}</p>
{{if $visible_addons.list}}
	<div style="margin-left: 25px; margin-right: 25px; margin-bottom: 25px;">{{$visible_addons.list}}</div>
{{/if}}

{{if $tos}}
	<p>{{$tos nofilter}}</p>
{{/if}}

{{if $block_list}}
	<div id="about_blocklist">
		<p>{{$block_list.title}}</p>
		<table class="table">
			<thead>
				<tr>
					<th>{{$block_list.header[0]}}</th>
					<th>{{$block_list.header[1]}}</th>
				</tr>
			</thead>
			<tbody>
			{{foreach $block_list.list as $blocked}}
				<tr>
					<td>{{$blocked.domain}}</td>
					<td>{{$blocked.reason}}</td>
				</tr>
			{{/foreach}}
			</tbody>
		</table>
		<p><a rel="nofollow" href="/blocklist/domain/download"><i class="ri ri-download-line"></i> {{$block_list.download}}</a></p>
	</div>
{{/if}}

	{{$hooked nofilter}}
</div>
