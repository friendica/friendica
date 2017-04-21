
{{* Template for the contact group list *}}
<div id="group" class="contact_list">

	<ul id="contact-group-list" class="viewcontact_wrapper media-list">

		{{* The contacts who are already members of the contact group *}}
		{{foreach $groupeditor.members as $contact}} 
			<li class="members active">{{include file="contact_template.tpl"}}</li>
		{{/foreach}}

		{{* The contacts who are not members of the contact group *}}
		{{foreach $groupeditor.contacts as $contact}}
			<li class="contacts">{{include file="contact_template.tpl"}}</li>
		{{/foreach}}

	</ul>
	<div class="clear"></div>
</div>
