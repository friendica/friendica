{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="iframe-placeholder{{if $contain}} iframe-placeholder-contain{{/if}}" data-host="{{$host}}" data-token="{{$form_security_token}}" style="{{$iframe_style}}height:{{$height}};width:{{$width}};{{if $preview}}background-image:url('{{$preview}}');{{/if}}">
	<div class="iframe-placeholder-dialog">
		<p>{{$question}}</p>
		<button type="button" class="btn btn-sm btn-primary" onclick="iframePlaceholderOnce(this);">{{$once}}</button>
		{{if $host}}<button type="button" class="btn btn-sm btn-primary" onclick="iframePlaceholderAlways(this);">{{$always}}</button>{{/if}}
		{{if $link}}<p><a href="{{$link}}" target="_blank" rel="noopener noreferrer">{{$link}}</a></p>{{/if}}
	</div>
	<template>{{$iframe nofilter}}</template>
</div>
