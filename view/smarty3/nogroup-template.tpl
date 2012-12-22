<h1>{{$header}}</h1>

{{foreach $contacts as $c}}
	{{include file="file:{{$contact_template}}" contact=$c}}
{{/foreach}}
<div id="contact-edit-end"></div>

{{$paginate}}




