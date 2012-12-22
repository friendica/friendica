<h3>{{$title}}</h3>

{{foreach $contacts as $c}}
	{{include file="file:{{$contact_template}}" contact=$c}}
{{/foreach}}

<div id="view-contact-end"></div>

{{$paginate}}
