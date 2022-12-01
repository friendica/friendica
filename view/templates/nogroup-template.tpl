
<h1>{{$header}}</h1>

{{foreach $contacts as $contact}}
	{{include file="contact/entry.tpl"}}
{{/foreach}}
<div id="contact-edit-end"></div>

{{$paginate nofilter}}
