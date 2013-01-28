<a name="acl-wrapper-target"></a>
<div id="acl-wrapper">
	<div id="acl-public-switch">
		<a href="$return_path#acl-wrapper-target" {{ if $is_private == 1 }}class="acl-public-switch-selected"{{ endif }} >$private</a>
		<a href="$return_path$public_link#acl-wrapper-target" {{ if $is_private == 0 }}class="acl-public-switch-selected"{{ endif }} >$public</a>
	</div>
	<div id="acl-list">
		<div id="acl-list-content">
			<div id="acl-html-groups" class="acl-html-select-wrapper">
			$group_perms<br />
			<select name="group_allow[]" multiple {{ if $is_private == 0 }}disabled{{ endif }} id="acl-html-group-select" class="acl-html-select" size=7>
				{{ for $acl_data.groups as $group }}
				<option value="$group.id" {{ if $is_private == 1 }}{{ if $group.selected }}selected{{ endif }}{{ endif }}>$group.name</option>
				{{ endfor }}
			</select>
			</div>
			<div id="acl-html-contacts" class="acl-html-select-wrapper">
			$contact_perms<br />
			<select name="contact_allow[]" multiple {{ if $is_private == 0 }}disabled{{ endif }} id="acl-html-contact-select" class="acl-html-select" size=7>
				{{ for $acl_data.contacts as $contact }}
				<option value="$contact.id" {{ if $is_private == 1 }}{{ if $contact.selected }}selected{{ endif }}{{ endif }}>$contact.name ($contact.networkName)</option>
				{{ endfor }}
			</select>
			</div>
		</div>
	</div>
	<span id="acl-fields"></span>
</div>

