{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

{{* Template for the contact circle list *}}
<div id="circle" class="contact_list">

	<ul id="contact-circle-list" class="viewcontact_wrapper media-list">

		{{* The contacts who are already members of the contact circle *}}
		{{foreach $circle_editor.members as $contact}}
			<li class="members active">{{include file="contact/entry.tpl"}}</li>
		{{/foreach}}

		{{* The contacts who are not members of the contact circle *}}
		{{foreach $circle_editor.contacts as $contact}}
			<li class="contacts">{{include file="contact/entry.tpl"}}</li>
		{{/foreach}}

	</ul>
	<div class="clear"></div>
</div>
