
{{$tabs nofilter}}

<div id="contacts" class="generic-page-wrapper">

	{{* The page heading with it's contacts counter *}}
	<h2 class="heading">{{$header}} {{if $total}} ({{$total}}) {{/if}}</h2>

	{{if $finding}}<h4>{{$finding}}</h4>{{/if}}

	{{* The search input field to search for contacts *}}
	<div id="contacts-search-wrapper">
		<form id="contacts-search-form" class="navbar-form" role="search" action="{{$cmd}}" method="get">
			<div class="row">
				<div class="form-group form-group-search">
					<input type="text" name="search" id="contacts-search" class="search-input form-control form-search" onfocus="this.select();" value="{{$search}}" placeholder="{{$desc nofilter}}"/>
					<button class="btn btn-default btn-sm form-button-search" type="submit" id="contacts-search-submit">{{$submit}}</button>
				</div>
			</div>
		</form>
	</div>

	<hr>
	<div id="contacts-search-end"></div>

	{{* we need the form container to make batch actions work *}}
	<form name="batch_actions_submit" action="{{$baseurl}}/contact/batch/" method="POST">
		<input type="hidden" name="redirect_url" value="{{$cmd}}" />
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}" />

		{{* we put here a hidden input element. This is needed to transmit the batch actions with javascript*}}
		<input type="hidden" class="batch-action no-input fakelist" name="batch_submit" value="{{$l}}">

		{{* We put the contact batch actions in a dropdown menu *}}
		<ul class="nav nav-pills preferences">
			<li class="dropdown pull-right">
				<button type="button" class="btn btn-link dropdown-toggle" id="BatchActionDropdownMenuTools" data-toggle="dropdown" aria-expanded="false">
					<i class="fa fa-angle-down"></i>&nbsp;{{$h_batch_actions}}
				</button>
				<ul class="dropdown-menu pull-right" role="menu" aria-labelledby="BatchActionDropdownMenuTools">
				{{foreach $batch_actions as $n=>$l}}
					<li role="menuitem">
						{{* call the js batch_submit_handler. Have a look at the file mod_contacts.js *}}
						<button type="button" class="btn-link" onclick="batch_submit_handler('{{$n}}', '{{$l}}')">{{$l}}</button>
					</li>
				{{/foreach}}
				</ul>
			</li>
		</ul>
		<div class="clear"></div>
		<div id="contact-list">
			{{* format each contact with the contact/entry.tpl *}}
			<ul id="viewcontact_wrapper" class="viewcontact_wrapper media-list">
			{{foreach $contacts as $contact}}
				<li>{{include file="contact/entry.tpl"}}</li>
			{{/foreach}}
			</ul>
		</div>
		<div id="contact-edit-end" class="clear"></div>
	</form>

	{{$paginate nofilter}}
</div>
