

<div class="contact-entry-wrapper" id="contact-entry-wrapper-{{$contact.id}}" >
	<div class="contact-entry-photo-wrapper" >
		<div class="contact-entry-photo mframe" id="contact-entry-photo-{{$contact.id}}"
		onmouseover="if (typeof t{{$contact.id}} != 'undefined') clearTimeout(t{{$contact.id}}); openMenu('contact-photo-menu-button-{{$contact.id}}')" 
		onmouseout="t{{$contact.id}}=setTimeout('closeMenu(\'contact-photo-menu-button-{{$contact.id}}\'); closeMenu(\'contact-photo-menu-{{$contact.id}}\');',200)" >

			<a href="{{$contact.url}}" title="{{$contact.img_hover}}" /><img src="{{$contact.thumb}}" {{$contact.sparkle}} alt="{{$contact.name}}" /></a>

			{{if $contact.photo_menu}}
			<span onclick="openClose('contact-photo-menu-{{$contact.id}}');" class="fakelink contact-photo-menu-button" id="contact-photo-menu-button-{{$contact.id}}">menu</span>
		<div class="contact-photo-menu" id="contact-photo-menu-{{$contact.id}}">
			<ul>
						{{foreach $contact.photo_menu as $c}}
						{{if $c.2}}
						<li><a target="redir" href="{{$c.1}}">{{$c.0}}</a></li>
						{{else}}
						<li><a href="{{$c.1}}">{{$c.0}}</a></li>
						{{/if}}
						{{/foreach}}
			</ul>
		</div>
			{{/if}}
		</div>
			
	</div>

	<div class="contact-entry-desc">
		<div class="contact-entry-name" id="contact-entry-name-{{$contact.id}}" >
			{{$contact.name}}
			{{if $contact.account_type}} <span class="contact-entry-details" id="contact-entry-accounttype-{{$contact.id}}">({{$contact.account_type}})</span>{{/if}}
		</div>
		{{if $contact.alt_text}}<div class="contact-entry-details" id="contact-entry-rel-{{$contact.id}}" >{{$contact.alt_text}}</div>{{/if}}
		{{if $contact.itemurl}}<div class="contact-entry-details" id="contact-entry-url-{{$contact.id}}" >{{$contact.itemurl}}</div>{{/if}}
		{{if $contact.tags}}<div class="contact-entry-details" id="contact-entry-tags-{{$contact.id}}" >{{$contact.tags}}</div>{{/if}}
		{{if $contact.details}}<div class="contact-entry-details" id="contact-entry-details-{{$contact.id}}" >{{$contact.details}}</div>{{/if}}
		{{if $contact.network}}<div class="contact-entry-network" id="contact-entry-network-{{$contact.id}}" >{{$contact.network}}</div>{{/if}}
	</div>

	<div class="contact-entry-end" ></div>
</div>
