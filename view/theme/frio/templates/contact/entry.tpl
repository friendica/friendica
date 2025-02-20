{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div class="contact-wrapper media" id="contact-entry-wrapper-{{$contact.id}}">

		{{* This is a wrapper for the contact picture *}}
		<div class="contact-photo-wrapper dropdown media-left">
			<div class="contact-entry-photo mframe" id="contact-entry-photo-{{$contact.id}}">

				<div class="contact-photo-image-wrapper hidden-xs">
					<a href="{{if !empty($contact.photo_menu.edit)}}{{$contact.photo_menu.edit.1}}{{else}}{{$contact.url}}{{/if}}">
						<img class="contact-photo media-object xl" src="{{$contact.thumb}}" {{$contact.sparkle}} alt="{{$contact.name}}" loading="lazy"/>
					</a>
				</div>

				{{* For very small displays we use a dropdown menu for contact relating actions *}}
				<button type="button" class="btn btn-default dropdown-toggle visible-xs" id="contact-photo-menu-button-{{$contact.id}}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					{{* use a smaller picture on very small displays (e.g. mobiles) *}}
					<div class="contact-photo-image-wrapper visible-xs">
						<img class="contact-photo-xs media-object" src="{{$contact.thumb}}" {{$contact.sparkle}} alt="{{$contact.name}}"  loading="lazy"/>

						{{* Overlay background on hover the avatar picture *}}
						<div class="contact-photo-overlay">
							<span class="contact-photo-overlay-content overlay-xs"><i class="fa fa-angle-down" aria-hidden="true"></i></span>
						</div>
					</div>
				</button>

				{{if $contact.photo_menu}}
				<ul class="contact-photo-menu menu-popup dropdown-menu hidden-lg hidden-md hidden-sm" id="contact-photo-menu-{{$contact.id}}" role="menu" aria-labelledby="contact-photo-menu-{{$contact.id}}">
					{{foreach $contact.photo_menu as $c}}
					{{if $c.2}}
					<li><a role="menuitem" target="redir" href="{{$c.1}}">{{$c.0}}</a></li>
					{{elseif $c.3}}
					<li><button role="menuitem" type="button" class="btn-link" onclick="addToModal('{{$c.1}}')">{{$c.0}}</button></li>
					{{else}}
					<li><a role="menuitem" href="{{$c.1}}">{{$c.0}}</a></li>
					{{/if}}
					{{/foreach}}
				</ul>

				{{/if}}
			</div>
		</div>

		<div class="media-body">
			{{if $contact.photo_menu}}
			{{* The contact actions like private mail, delete contact, edit contact and so on *}}
			<div class="btn-group contact-actions pull-right nav-pills preferences hidden-xs" role="group">
				{{if $contact.photo_menu.pm}}
				<button type="button" class="contact-action-link btn btn-default" onclick="addToModal('{{$contact.photo_menu.pm.1}}'); return false;" data-toggle="tooltip" title="{{$contact.photo_menu.pm.0}}">
					<i class="fa fa-envelope" aria-hidden="true"></i>
				</button>
				{{/if}}
				{{if $contact.photo_menu.network}}
				<a class="contact-action-link btn btn-default" href="{{$contact.photo_menu.network.1}}" data-toggle="tooltip" title="{{$contact.photo_menu.network.0}}">
					<i class="fa fa-cloud" aria-hidden="true"></i>
				</a>
				{{/if}}
				{{if $contact.photo_menu.follow}}
				<a class="contact-action-link btn btn-default" href="{{$contact.photo_menu.follow.1}}" data-toggle="tooltip" title="{{$contact.photo_menu.follow.0}}">
					<i class="fa fa-user-plus" aria-hidden="true"></i>
				</a>
				{{/if}}
				{{if $contact.photo_menu.unfollow}}
				<a class="contact-action-link btn btn-default" href="{{$contact.photo_menu.unfollow.1}}" data-toggle="tooltip" title="{{$contact.photo_menu.unfollow.0}}">
					<i class="fa fa-user-times" aria-hidden="true"></i>
				</a>
				{{/if}}
				{{if $contact.photo_menu.hide}}
				<a class="contact-action-link btn btn-default" href="{{$contact.photo_menu.hide.1}}" data-toggle="tooltip" title="{{$contact.photo_menu.hide.0}}">
					<i class="fa fa-times" aria-hidden="true"></i>
				</a>
				{{/if}}
			</div>
			{{/if}}

			{{* The button to add or remove contacts from a contact group - group edit page *}}
			{{if $contact.change_member}}
			<div class="contact-group-actions pull-right nav-pills preferences">
				<button type="button" class="contact-action-link btn contact-group-link btn-default contact-circle-actions contact-circle-link" onclick="circleChangeMember({{$contact.change_member.gid}},{{$contact.change_member.cid}},'{{$contact.change_member.sec_token}}'); return true;" data-toggle="tooltip" title="{{$contact.change_member.title}}">
					{{if $contact.label == "members"}}
					<i class="fa fa-times-circle" aria-hidden="true"></i>
					{{elseif $contact.label == "contacts"}}
					<i class="fa fa-plus-circle" aria-hidden="true"></i>
					{{/if}}
				</button>
			</div>
			{{/if}}

			{{* The contact description (e.g. Name, Network, kind of connection and so on *}}
			<div class="contact-entry-desc">
				<div class="contact-entry-name" id="contact-entry-name-{{$contact.id}}">
					<h4 class="media-heading"><a href="{{if !empty($contact.photo_menu.edit)}}{{$contact.photo_menu.edit.1}}{{else}}{{$contact.url}}{{/if}}">{{$contact.name}}</a>
					{{if $contact.account_type}} <small class="contact-entry-details" id="contact-entry-accounttype-{{$contact.id}}">({{$contact.account_type}})</small>{{/if}}
					{{if $contact.account_type == 'Group'}}<i class="fa fa-comments-o" aria-hidden="true"></i>{{/if}}
					{{* @todo this needs some changing in core because $contact.account_type contains a translated string which may not be the same in every language *}}
					</h4>
				</div>
				{{if $contact.alt_text}}<div class="contact-entry-details contact-entry-rel" id="contact-entry-rel-{{$contact.id}}">{{$contact.alt_text}}</div>{{/if}}
				{{if $contact.itemurl}}<div class="contact-entry-details contact-entry-url" id="contact-entry-url-{{$contact.id}}">{{$contact.itemurl}}</div>{{/if}}
				{{if $contact.tags}}<div class="contact-entry-details" id="contact-entry-tags-{{$contact.id}}">{{$contact.tags}}</div>{{/if}}
				{{if $contact.details}}<div class="contact-entry-details contact-entry-tags" id="contact-entry-details-{{$contact.id}}">{{$contact.details}}</div>{{/if}}
				{{if $contact.network}}<div class="contact-entry-details contact-entry-network" id="contact-entry-network-{{$contact.id}}">{{$contact.network}}</div>{{/if}}
			</div>

			{{* The checkbox to perform batch actions to these contacts (for batch actions have a look at contacts-template.tpl) *}}
			{{* if !$no_contacts_checkbox *}}
			{{if $multiselect}}
			<div class="checkbox contact-entry-checkbox pull-right">
				<input id="checkbox-{{$contact.id}}" type="checkbox" class="contact-select pull-right" name="contact_batch[]" value="{{$contact.id}}">
				<label for="checkbox-{{$contact.id}}"></label>
			</div>
			{{/if}}
		</div>

</div>
