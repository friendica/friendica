{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div class="generic-page-wrapper">
	{{if $gdirpath}}
	<ul class="list-unstyled pull-right">
		<li><div id="global-directory-link"><a href="{{$gdirpath}}">{{$globaldir}}</a></div></li>
	</ul>
	{{/if}}

	{{include file="section_title.tpl"}}

	{{* The search input field to search for contacts *}}
	<div id="directory-search-wrapper">
		<form id="directory-search-form" class="navbar-form" role="search" action="{{$search_mod}}" method="get">
			<div class="row">
				<div class="col-md-2"></div>
				<div class="col-md-8 ">
					<div class="form-group form-group-search">
						<input type="text" name="search" id="directory-search" class="search-input form-control form-search" onfocus="this.select();" value="{{$search}}" placeholder="{{$desc}}"/>
						<button class="btn btn-default btn-sm form-button-search" type="submit" id="directory-search-submit">{{$submit}}</button>
					</div>
				</div>
				<div class="col-md-2"></div>
			</div>
		</form>
	</div>

	<hr>

	<div id="directory-search-end" class="clear"></div>

	{{* format each contact with the contact/entry.tpl *}}
	<ul id="viewcontact_wrapper" class="viewcontact_wrapper media-list">
	{{foreach $contacts as $contact}}
		<li>{{include file="contact/entry.tpl"}}</li>
	{{/foreach}}
	</ul>

	<div class="directory-end"></div>

	{{$paginate nofilter}}
</div>
