
{{* This template is for the "group" module. It provides the user the possibility to
    modify a specific contact group (remove contact group, edit contact group name,
    add or remove contacts to the contact group.
*}}

<script type="text/javascript" src="view/theme/frio/js/mod_group.js"></script>

<div class="generic-page-wrapper">
	{{if $editable == 1}}
	{{* The buttons for editing the contact group (edit name / remove contact group) *}}
	<div class="group-actions pull-right">
		<button type="button" id="group-rename" class="btn btn-clear" onclick="showHide('group-edit-wrapper'); showHide('group-edit-header'); return false;" title="{{$edit_name}}" data-toggle="tooltip">
			<i class="fa fa-pencil" aria-hidden="true"></i>
		</button>
		{{if $drop}}{{$drop}}{{/if}}
	</div>
	{{/if}}

	<div class="section-title-wrapper">
		<div id="group-edit-header">
			<h2>{{$title}}</h2>
		</div>

		{{* Edit the name of the group *}}
		<div id="group-edit-wrapper">

			<form action="group/{{$gid}}" id="group-edit-form" method="post">

				<div class="pull-left">
				{{include file="field_input.tpl" field=$gname label=false}}
				</div>
				<div id="group-edit-submit-wrapper" class="form-group pull-right">
					<button class="btn btn-primary btn-small" type="submit" name="submit" value="{{$submit|escape:'html'}}">
						{{$submit|escape:'html'}}
					</button>
				</div>
			</form>
		</div>

		<div class="clear"></div>
	</div>

	{{* The search input field to search for contacts *}}
	<div id="contacts-search-wrapper">
		<form id="contacts-search-form" class="navbar-form" role="search" method="get" >
			<div class="row">
				<div class="form-group form-group-search">
					<input type="text" name="search" id="contacts-search" class="search-input form-control form-search" onfocus="this.select();" onkeyup="filterList(); return false;" />
					<button class="btn btn-default btn-sm form-button-search" onclick="filterList(); return false;">{{$submit_filter}}</button>
				</div>
			</div>
		</form>
	</div>

	<div id="contacts-search-end"></div>

	{{if $groupeditor}}
	{{* The buttons to switch between the different view modes *}}
	<div id="group-list-view-switcher" class="btn-group btn-group-sm pull-right">
		<botton type="button" id="group-list-big" class="active group-list-switcher btn btn-default">
			<i class="fa fa-align-justify" aria-hidden="true"></i>
		</botton>
		<button type="button" id="group-list-small" class="btn btn-default group-list-switcher">
			<i class="fa fa-th-large" aria-hidden="true"></i>
		</button>
	</div>
	<div class="clear"></div>

	{{* The contact group list *}}
	<div id="group-update-wrapper">
		{{include file="groupeditor.tpl"}}
	</div>
	{{/if}}
</div>
