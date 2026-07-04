{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div class="contact-entry-wrapper" id="contact-entry-wrapper-{{$contact.id}}">
	<div class="contact-entry-photo-wrapper">
		<div class="contact-entry-photo mframe" id="contact-entry-photo-{{$contact.id}}">
		<!-- onmouseover="if (typeof t{{$contact.id}} != 'undefined') clearTimeout(t{{$contact.id}}); openMenu('contact-photo-menu-button-{{$contact.id}}')" 
		onmouseout="t{{$contact.id}}=setTimeout('closeMenu(\'contact-photo-menu-button-{{$contact.id}}\'); closeMenu(\'contact-photo-menu-{{$contact.id}}\');',200)"> -->

			<!-- <a href="{{$contact.url}}" title="{{$contact.img_hover}}" /></a> -->
			<img src="{{$contact.thumb}}" {{$contact.sparkle}} alt="{{$contact.name}}" loading="lazy"/>

			{{if $multiselect}}
			<input type="checkbox" class="contact-select" name="contact_batch[]" value="{{$contact.id}}">
			{{/if}}
			{{if $contact.photo_menu}}
			<!-- <span onclick="openClose('contact-photo-menu-{{$contact.id}}');" class="fakelink contact-photo-menu-button" id="contact-photo-menu-button-{{$contact.id}}">menu</span> -->
			<div class="contact-photo-menu" id="contact-photo-menu-{{$contact.id}}">
				<ul role="menu" aria-haspopup="true">
					{{foreach $contact.photo_menu as $k=>$c}}
					{{if $c.2}}
					<li role="menuitem"><a class="{{$k}}" target="redir" href="{{$c.1}}">{{$c.0}}</a></li>
					{{else}}
					<li role="menuitem"><a class="{{$k}}" href="{{$c.1}}">{{$c.0}}</a></li>
					{{/if}}
					{{/foreach}}
				</ul>
			</div>
			{{/if}}
		</div>
			
	</div>
	<div class="contact-entry-photo-end"></div>
	
	<div class="contact-entry-desc">
		<div class="contact-entry-name" id="contact-entry-name-{{$contact.id}}">
			{{$contact.name}}
			{{if $contact.account_type == 4}}
				{{$acct_icon = "ri-broadcast-line"}}
			{{else if $contact.account_type == 3}}
				{{if $contact.private == 1}}
					{{$acct_icon = "ri-spy-line"}}
				{{else if $contact.manually_approve == 1}}
					{{$acct_icon = "ri-group-3-line"}}
				{{else}}
					{{$acct_icon = "ri-team-line"}}
				{{/if}}
			{{else if $contact.account_type == 2}}
				{{$acct_icon = "ri-newspaper-line"}}
			{{else if $contact.account_type == 1}}
				{{$acct_icon = "ri-building-4-line"}}
			{{else if $contact.account_type == 0 && $contact.manually_approve == 0}}
				{{$acct_icon = "ri-megaphone-line"}}
			{{else}}
				{{$acct_icon = ""}}
			{{/if}}
			{{if $contact.account_type_name}} <small class="contact-entry-details" id="contact-entry-accounttype-{{$contact.id}}">(<i class="ri {{$acct_icon}}" aria-hidden="true"></i> {{$contact.account_type_name}})</small>
			{{else}}
				<small class="contact-entry-details"><i class="ri {{$acct_icon}}" aria-hidden="true"></i></small>
			{{/if}}
			</h4>
			{{if $contact.is_admin}}<span class="badge badge-admin"><i class="ri ri-medal-2-fill" aria-hidden="true"></i> {{$contact.admin_title}}</span>{{/if}}
			{{if $contact.is_mod}}<span class="badge badge-mod"><i class="ri ri-shield-user-line" aria-hidden="true"></i> {{$contact.moderator_title}}</span>{{/if}}
		</div>
		{{if $contact.alt_text}}<div class="contact-entry-details" id="contact-entry-rel-{{$contact.id}}">{{$contact.alt_text}}</div>{{/if}}
		<div class="contact-entry-details">
		{{if $contact.itemurl}}<span class="contact-entry-details" id="contact-entry-url-{{$contact.id}}">{{$contact.itemurl}}</span>{{/if}}
		{{if $contact.network}}<span class="contact-entry-details" id="contact-entry-network-{{$contact.id}}"> ({{$contact.network}})</span>{{/if}}
		</div>
		{{if $contact.tags}}<div class="contact-entry-details" id="contact-entry-tags-{{$contact.id}}">{{$contact.tags}}</div>{{/if}}
		{{if $contact.details}}<div class="contact-entry-details" id="contact-entry-details-{{$contact.id}}">{{$contact.details}}</div>{{/if}}
	</div>


	<div class="contact-entry-end"></div>
</div>
