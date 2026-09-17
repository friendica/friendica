{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="iframe-placeholder{{if $contain}} iframe-placeholder-contain{{/if}}" data-host="{{$host}}" style="{{$iframe_style}}height:{{$height}};width:{{$width}};{{if $preview}}background-image:url('{{$preview}}');{{/if}}">
	<div class="iframe-placeholder-dialog">
		<p>{{$question}}</p>
		<button type="button" class="btn btn-sm btn-primary" onclick="iframePlaceholderOnce(this);">{{$once}}</button>
		<button type="button" class="btn btn-sm btn-primary" onclick="iframePlaceholderAlways(this);">{{$always}}</button>
	</div>
	<template>{{$iframe nofilter}}</template>
</div>
