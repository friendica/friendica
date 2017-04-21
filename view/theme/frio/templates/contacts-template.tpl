
{{$tabs}}

<div id="contacts" class="generic-page-wrapper">

	{{* The page heading with it's contacts counter *}}
	<h2 class="heading">{{$header}} {{if $total}} ({{$total}}) {{/if}}</h2>

	{{if $finding}}<h4>{{$finding}}</h4>{{/if}}

	{{* The search input field to search for contacts *}}
	<div id="contacts-search-wrapper">
		<form id="contacts-search-form" class="navbar-form" role="search" action="{{$cmd}}" method="get" >
			<div class="row">
				<div class="col-md-2"></div>
				<div class="col-md-8 ">
					<div class="form-group form-group-search">
						<input type="text" name="search" id="contacts-search" class="search-input form-control form-search" onfocus="this.select();" value="{{$search|escape:'html'}}" placeholder="{{$desc}}"/>
						<button class="btn btn-default btn-sm form-button-search" type="submit" id="contacts-search-submit">{{$submit}}</button>
					</div>
				</div>
				<div class="col-md-2"></div>
			</div>
		</form>
	</div>

	<hr>
	<div id="contacts-search-end"></div>

	{{* we need the form container to make batch actions work *}}
	<form name="batch_actions_submit" action="{{$baseurl}}/contacts/batch/" method="POST">

		{{* we put here a hidden input element. This is needed to transmit the batch actions with javascript*}}
		<input type="hidden" class="batch-action no-input fakelist" name="batch_submit" value="{{$l|escape:'html'}}">

		{{* We put the contact batch actions in a dropdown menu *}}
		<ul class="nav nav-pills preferences">
			<li class="dropdown pull-right">
				<button type="button" class="btn btn-link btn-sm dropdown-toggle" id="BatchActionDropdownMenuTools" data-toggle="dropdown" aria-expanded="true">
					<i class="fa fa-angle-down"></i>&nbsp;{{$h_batch_actions}}
				</button>
				<ul class="dropdown-menu pull-right" role="menu" aria-labelledby="BatchActionDropdownMenuTools">
				{{foreach $batch_actions as $n=>$l}}
					<li role="menuitem">
						{{* call the js batch_submit_handler. Have a look at the buttom of this file *}}
						<button type="button" class="btn-link" onclick="batch_submit_handler('{{$n}}', '{{$l}}')">{{$l}}</button>
					</li>
				{{/foreach}}
				</ul>
			</li>
		</ul>
		<div class="clear"></div>
		<div id="contact-list">
			{{* format each contact with the contact_template.tpl *}}
			<ul id="viewcontact_wrapper" class="viewcontact_wrapper media-list">
			{{foreach $contacts as $contact}}
				<li>{{include file="contact_template.tpl"}}</li>
			{{/foreach}}
			</ul>
		</div>
		<div id="contact-edit-end"></div>
	</form>

	{{$paginate}}
</div>

<script>
 $(document).ready(function() {
  // javascript dialog to batch actions
  $(".batch-action").click(function(e){
    if (confirm($(this).attr('value')+" ?")) {
     return true;
    } else {
     e.preventDefault();
     return false;
    }
  });

  // add javascript confirm dialog to "drop" links. Plain html url have "?confirm=1" to show confirmation form, we need to remove it
  $(".drop").each(function() {
   $(this).attr('href', $(this).attr('href').replace("confirm=1","") );
   $(this).click(function(e){
    if (confirm("{{$contact_drop_confirm}}")) {
     return true;
    } else {
     e.preventDefault();
     return false;
    }
   });

  });
 });

/**
 * @brief This function submits the form with the batch action values
 *
 * @param string name The name of the batch action
 * @param string value If it isn't empty the action will be posted
 */
function batch_submit_handler(name, value) {
    // set the value of the hidden input element with the name batch_submit
    document.batch_actions_submit.batch_submit.value=value;
    // change the name of the input element from batch_submit according to the
    // name which is transmitted to this function
    document.batch_actions_submit.batch_submit.name=name;
    // transmit the form
    document.batch_actions_submit.submit() ;
}
 </script>

