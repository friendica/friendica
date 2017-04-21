
{{* Template for the contact group list *}}

{{* The contacts who are already members of the contact group *}}
<div id="group">
	<h3>{{$groupeditor.label_members}}</h3>
	<div id="group-members" class="contact_list">

		{{if $groupeditor.members }}

		{{foreach $groupeditor.members as $c}}
			{{* If there are too many contacts we use another view mode *}}
			{{if $shortmode}}
			<div class="contact-block-textdiv mpgroup">
				<a class="contact-block-link mpgroup  fakelink" target="redir" onclick="groupChangeMember({{$c.change_member.gid}},{{$c.change_member.cid}},'{{$c.change_member.sec_token}}'); return true;" title="{{$c.name}} [{{$c.itemurl}}]" alt="{{$c.name}}">
					{{$c.name}}"
				</a>
			</div>
			{{else}}
			{{* The normal view mode *}}
			<div class="contact-block-div mpgroup">
				<a class="contact-block-link mpgroup  fakelink" target="redir" onclick="groupChangeMember({{$c.change_member.gid}},{{$c.change_member.cid}},'{{$c.change_member.sec_token}}'); return true;">
					<img class="contact-block-img mpgroup " src="{{$c.thumb}}" title="{{$c.name}} [{{$c.itemurl}}]" alt="{{$c.name}}">
				</a>
			</div>
			{{/if}}
		{{/foreach}}

		{{else}}
		{{$groupeditor.group_is_empty}}
		{{/if}}
	</div>

	<div id="group-members-end"></div>
		<hr id="group-separator" />
</div>

{{* The contacts who are not members of the contact group *}}
<div id="contacts">
	<h3>{{$groupeditor.label_contacts}}</h3>
	<div id="group-all-contacts" class="contact_list">
		{{foreach $groupeditor.contacts as $m}}
			{{* If there are too many contacts we use another view mode *}}
			{{if $shortmode}}
			<div class="contact-block-textdiv mpall">
				<a class="contact-block-link mpall  fakelink" target="redir" onclick="groupChangeMember({{$m.change_member.gid}},{{$m.change_member.cid}},'{{$m.change_member.sec_token}}'); return true;" title="{{$m.name}} [{{$m.itemurl}}]" alt="{{$m.name}}">
					{{$m.name}}
				</a>
			</div>
			{{else}}
			{{* The normal view mode *}}
			<div class="contact-block-div mpall">
				<a class="contact-block-link mpall  fakelink" target="redir" onclick="groupChangeMember({{$m.change_member.gid}},{{$m.change_member.cid}},'{{$m.change_member.sec_token}}'); return true;">
					<img class="contact-block-img mpall " src="{{$m.thumb}}" title="{{$m.name}} [{{$m.itemurl}}]" alt="{{$m.name}}">
				</a>
			</div>
			{{/if}}
		{{/foreach}}
	</div>
	<div id="group-all-contacts-end"></div>
</div>

