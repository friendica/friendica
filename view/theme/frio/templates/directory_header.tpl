
{{if $gdirpath}}
<ul class="list-unstyled pull-right">
	<li><div id="global-directory-link"><a href="{{$gdirpath}}">{{$globaldir}}</a></div></li>
</ul>
{{/if}}

{{include file="section_title.tpl"}}


{{if $findterm}}
<h4 class="search-heading">{{$finding}} {{$findterm}}</h4>
{{/if}}

{{* The search input field to search for contacts *}}
<div id="directory-search-wrapper">
	<form id="directory-search-form" class="navbar-form" role="search" action="directory" method="get" >
		<div class="row">
			<div class="col-md-2"></div>
			<div class="col-md-8 ">
				<div class="form-group form-group-search">
					<input type="text" name="search" id="directory-search" class="search-input form-control form-search" onfocus="this.select();" value="{{$search|escape:'html'}}" placeholder="{{$desc}}"/>
					<button class="btn btn-default btn-sm form-button-search" type="submit" id="directory-search-submit">{{$submit}}</button>
				</div>
			</div>
			<div class="col-md-2"></div>
		</div>
	</form>
</div>

<hr>

<div id="directory-search-end"></div>

{{* format each contact with the contact_template.tpl *}}
<ul id="viewcontact_wrapper" class="viewcontact_wrapper media-list">
{{foreach $contacts as $contact}}
	<li>{{include file="contact_template.tpl"}}</li>
{{/foreach}}
</ul>

<div class="directory-end" ></div>

{{$paginate}}
