{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<nav id="peoplefind-sidebar" class="widget">
	<h3>{{$nv.findpeople}}</h3>

	<form action="dirfind" method="get">
		{{* The search field *}}
		<label for="side-peoplefind-url" id="peoplefind-desc">{{$nv.desc}}</label>
		<div class="form-group form-group-search">
			<input id="side-peoplefind-url" class="search-input form-control form-search" type="text" name="search" data-toggle="tooltip" title="{{$nv.hint}}" />
			<button id="side-peoplefind-submit" class="btn btn-default btn-sm form-button-search" type="submit">{{$nv.findthem}}</button>
		</div>
	</form>

	{{* Directory links *}}
	<div class="side-link" id="side-directory-link"><a href="directory" class="side-link-link">{{$nv.local_directory}}</a></div>
	<div class="side-link" id="side-directory-link"><a href="{{$nv.global_dir}}" class="side-link-link" target="extlink">{{$nv.directory}}</a></div>
	{{* Additional links *}}
	<div class="side-link" id="side-match-link"><a href="contact/match" class="side-link-link">{{$nv.similar}}</a></div>
	<div class="side-link" id="side-suggest-link"><a href="contact/suggestions" class="side-link-link">{{$nv.suggest}}</a></div>
	<div class="side-link" id="side-random-profile-link"><a href="randprof" class="side-link-link" target="extlink">{{$nv.random}}</a></div>

	{{if $nv.inv}}
		<div class="side-link" id="side-invite-link"><button type="button" class="btn-link side-link-link" onclick="addToModal('invite'); return false;">{{$nv.inv}}</button></div>
	{{/if}}
</nav>