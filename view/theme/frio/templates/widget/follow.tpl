{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<nav id="follow-sidebar" class="widget">
	<h3>{{$connect}}</h3>

	<form action="contact/follow" method="post">
		{{* The input field - For visual consistence we are using a search input field*}}
		<div class="form-group form-group-search">
			<input id="side-follow-url" class="search-input form-control form-search" type="text" name="follow-url" value="{{$value}}" placeholder="{{$hint}}" data-toggle="tooltip" title="{{$hint}}" />
			<button id="side-follow-submit" class="btn btn-default btn-sm form-button-search" type="submit">{{$follow}}</button>
		</div>
	</form>
</nav>