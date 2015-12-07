
{{include file="section_title.tpl"}}

{{foreach $contacts as $contact}}
	{{include file="contact_template.tpl"}}
{{/foreach}}

<div id="view-contact-end"></div>

{{$paginate}}
