<h1>{{$header}}</h1>

{{foreach $contacts as $c}}
	{{include file="contact_template.tpl" contact=$c}}
{{/foreach}}
<div id="contact-edit-end"></div>

{{$paginate}}




