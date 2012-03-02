<div id="group">
<h3>$groupeditor.label_members</h3>
<div id="group-members" class="contact_list">
{{ for $groupeditor.members as $c}} $c {{ endfor }}
</div>
<div id="group-members-end"></div>
<hr id="group-separator" />
</div>

<div id="contacts">
<h3>$groupeditor.label_contacts</h3>
<div id="group-all-contacts" class="contact_list">
{{ for $groupeditor.contacts as $m}} $m {{ endfor }}
</div>
<div id="group-all-contacts-end"></div>
</div>
