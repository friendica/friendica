{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

{{* important notes: The frio theme hides under certain conditions some parts of the templates through css.
Some parts of this template will be moved by js to other places (see theme.js) - E.g. the save-search button *}}

<div id="{{$id}}" {{* class="input-group" *}}>
	<div id="search-wrapper">
		<form action="search" method="get">
			<div class="row">
				<div class="col-md-2"></div>
				<div class="col-md-8 ">

					<div class="form-group form-group-search">
						<input type="text" name="q" id="search-text" class="search-input form-control form-search" data-toggle="tooltip" title="{{$search_hint}}" placeholder="{{$search_label}}" value="{{$s}}" />
						<button id="search-submit" class="btn btn-default btn-sm form-button-search" type="submit">{{$search_label}}</button>
					</div>

					<div class="col-md-4"></div>
					<div class="col-md-8">
						{{* The button to save searches *}}
						{{if $s}}
						<a href="search/saved/add?term={{$q}}&amp;return_url={{$return_url}}" class="btn btn-primary btn-small pull-right">{{$action_text}}</a>
						{{/if}}

						{{* The select popup menu to select what kind of results the user would like to search for *}}
						{{if $search_options}}
						<div class="col-md-6 pull-right">
							<div class="form-group field select">
								<select name="search-option" id="search-options" class="form-control form-control-sm">
                                {{foreach $search_options as $value => $label}}
									<option value="{{$value}}">{{$label}}</option>
                                {{/foreach}}
								</select>
							</div>
						</div>
						{{/if}}

					</div>
				</div>
				<div class="col-md-2"></div>

				<div class="clearfix"></div>

			</div>

		</form>
	</div>

{{if $s}}
	<a href="search/saved/add?term={{$q}}&amp;return_url={{$return_url}}" class="action-button btn btn-primary pull-right" id="search-save">
		<i class="fa fa-lg fa-floppy-o" aria-hidden="true"></i>
		<span>{{$action_text}}</span>
	</a>
{{/if}}
</div>
