{{* This template is for the "circle" module. It provides the user the possibility to
    modify a specific contact circle (remove contact circle, edit contact circle name,
    add or remove contacts to the contact circle.
*}}

<script type="text/javascript" src="view/theme/frio/js/mod_circle.js?v={{$smarty.const.FRIENDICA_VERSION}}"></script>

<div class="generic-page-wrapper">
	{{if $editable == 1}}
	{{* The buttons for editing the contact circle (edit name / remove contact circle) *}}
	<div class="circle-actions pull-right">
		<button type="button" id="circle-rename" class="btn btn-clear" onclick="showHide('circle-edit-wrapper'); showHide('circle-edit-header'); return false;" title="{{$edit_name}}" data-toggle="tooltip">
			<i class="fa fa-pencil" aria-hidden="true"></i>
		</button>
		{{if $drop}}{{$drop nofilter}}{{/if}}
	</div>
	{{/if}}

	<div class="section-title-wrapper">
		<div id="circle-edit-header">
			<h2>{{$title}}</h2>
		</div>

		{{* Edit the name of the circle *}}
		<div id="circle-edit-wrapper">

			<form action="circle/{{$gid}}" id="circle-edit-form" method="post">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

				<div class="pull-left">
				{{include file="field_input.tpl" field=$gname label=false}}
				</div>
				<div id="circle-edit-submit-wrapper" class="form-group pull-right">
					<button class="btn btn-primary btn-small" type="submit" name="submit" value="{{$submit}}">
						{{$submit}}
					</button>
				</div>
			</form>
		</div>

		<div class="clear"></div>
	</div>

	{{* The search input field to search for contacts *}}
	<div id="contacts-search-wrapper">
		<form id="contacts-search-form" class="navbar-form" role="search" method="get">
			<div class="row">
				<div class="form-group form-group-search">
					<input type="text" name="search" id="contacts-search" class="search-input form-control form-search" onfocus="this.select();" onkeyup="filterList(); return false;" />
					<button class="btn btn-default btn-sm form-button-search" onclick="filterList(); return false;">{{$submit_filter}}</button>
				</div>
			</div>
		</form>
	</div>

	<div id="contacts-search-end"></div>

	{{if $circle_editor}}
	{{* The buttons to switch between the different view modes *}}
	<div id="circle-list-view-switcher" class="btn-group btn-group-sm pull-right">
		<button type="button" id="circle-list-big" class="active circle-list-switcher btn btn-default">
			<i class="fa fa-align-justify" aria-hidden="true"></i>
		</button>
		<button type="button" id="circle-list-small" class="btn btn-default circle-list-switcher">
			<i class="fa fa-th-large" aria-hidden="true"></i>
		</button>
	</div>
	<div class="clear"></div>

	{{* The contact circle list *}}
	<div id="circle-update-wrapper">
		{{include file="circle_editor.tpl"}}
	</div>
	{{/if}}
</div>
