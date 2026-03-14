{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

{{include file="section_title.tpl"}}

{{if $gdirpath}}
	<ul>
		<li><div id="global-directory-link"><a href="{{$gdirpath}}">{{$globaldir}}</a></div></li>
	</ul>
{{/if}}

<div id="directory-search-wrapper">
	<form id="directory-search-form" action="{{$search_mod}}" method="get">
		<span class="dirsearch-desc">{{$desc}}</span>
		<input type="text" name="search" id="directory-search" class="search-input" onfocus="this.select();" value="{{$search}}" />
		<input type="submit" name="submit" id="directory-search-submit" value="{{$submit}}" class="button" />
		<p id="num-results">{{$num_results_text}}</p>
	</form>
</div>

{{if $findterm}}
	<h4>{{$finding}} '{{$findterm}}'</h4>
{{/if}}

<div id="directory-search-end"></div>

{{foreach $contacts as $contact}}
	{{include file="contact/entry.tpl"}}
{{foreachelse}}
	<div class="no-results">{{$no_results}}</div>
{{/foreach}}

<div class="directory-end"></div>

{{$paginate nofilter}}
