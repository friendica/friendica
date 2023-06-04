
{{* Template for the contact circle list *}}

{{if $editable == 1}}
{{* The contacts who are already members of the contact circle *}}
<div id="circle">
	<h3>{{$circle_editor.label_members}}</h3>
	<div id="circle-members" class="contact_list">

		{{if $circle_editor.members }}

		{{foreach $circle_editor.members as $c}}
			{{* If there are too many contacts we use another view mode *}}
			{{if $shortmode}}
			<div class="contact-block-textdiv mpcircle">
				<a class="contact-block-link mpcircle fakelink" target="redir" onclick="circleChangeMember({{$c.change_member.gid}},{{$c.change_member.cid}},'{{$c.change_member.sec_token}}'); return true;" title="{{$c.name}} [{{$c.itemurl}}]" alt="{{$c.name}}">
					{{$c.name}}"
				</a>
			</div>
			{{else}}
			{{* The normal view mode *}}
			<div class="contact-block-div mpcircle">
				<a class="contact-block-link mpcircle fakelink" target="redir" onclick="circleChangeMember({{$c.change_member.gid}},{{$c.change_member.cid}},'{{$c.change_member.sec_token}}'); return true;">
					<img class="contact-block-img mpcircle" src="{{$c.thumb}}" title="{{$c.name}} [{{$c.itemurl}}]" alt="{{$c.name}}">
				</a>
			</div>
			{{/if}}
		{{/foreach}}

		{{else}}
		{{$circle_editor.circle_is_empty}}
		{{/if}}
	</div>

	<div id="circle-members-end"></div>
		<hr id="circle-separator" />
</div>
{{/if}}

{{* The contacts who are not members of the contact circle *}}
<div id="contacts">
	<h3>{{$circle_editor.label_contacts}}</h3>
	<div id="circle-all-contacts" class="contact_list">
		{{foreach $circle_editor.contacts as $m}}
			<div class="contact-block-textdiv mpall">
				{{if $editable == 1}}
				<a class="contact-block-link mpall  fakelink" target="redir" onclick="circleChangeMember({{$m.change_member.gid}},{{$m.change_member.cid}},'{{$m.change_member.sec_token}}'); return true;" title="{{$m.name}} [{{$m.itemurl}}]" alt="{{$m.name}}">
				{{else}}
				<a class="contact-block-link mpall" href="{{$m.url}}" title="{{$m.name}} [{{$m.itemurl}}]" alt="{{$m.name}}">
				{{/if}}
					{{* If there are too many contacts we use another view mode *}}
					{{if $shortmode}}
						{{$m.name}}
					{{else}}
						<img class="contact-block-img mpall " src="{{$m.thumb}}" title="{{$m.name}} [{{$m.itemurl}}]" alt="{{$m.name}}">
					{{/if}}
				</a>
			</div>
		{{/foreach}}
	</div>
	<div id="circle-all-contacts-end"></div>
</div>
