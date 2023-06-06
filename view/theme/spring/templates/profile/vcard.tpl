<div class="vcard h-card">

	<div class="tool">
		<div class="fn p-name" dir="auto">{{$profile.name}}</div>
		{{if $profile.edit}}
			<div class="action">
				<a class="icon s16 edit ttright" href="{{$profile.edit.0}}" title="{{$profile.edit.3}}"><span>{{$profile.edit.1}}</span></a>
			</div>
		{{/if}}
	</div>

	{{if $profile.addr}}<div class="p-addr">{{$profile.addr}}</div>{{/if}}

	<div id="profile-photo-wrapper"><a href="{{$profile.url}}"><img class="photo u-photo" src="{{$profile.photo}}" alt="{{$profile.name}}"></a></div>

	{{if $account_type}}<div class="account-type">{{$account_type}}</div>{{/if}}
	{{if $profile.network_link}}<dl class="network"><dt class="network-label">{{$network}}</dt><dd class="x-network">{{$profile.network_link nofilter}}</dd></dl>{{/if}}
	{{if $location}}
		<dl class="location" dir="auto">
			<dt class="location-label">{{$location}}</dt>
			<dd class="adr h-adr">
				{{if $profile.address}}<p class="street-address p-street-address">{{$profile.address nofilter}}</p>{{/if}}
				{{if $profile.location}}<p class="p-location">{{$profile.location}}</p>{{/if}}
			</dd>
		</dl>
	{{/if}}

	{{if $profile.xmpp}}
		<dl class="xmpp">
			<dt class="xmpp-label">{{$xmpp}}</dt>
			<dd class="xmpp-data">{{$profile.xmpp}}</dd>
		</dl>
	{{/if}}

	{{if $profile.matrix}}
		<dl class="matrix">
			<dt class="matrix-label">{{$matrix}}</dt>
			<dd class="matrix-data">{{$profile.matrix}}</dd>
		</dl>
	{{/if}}

	{{if $profile.upubkey}}<div class="key u-key" style="display:none;">{{$profile.upubkey}}</div>{{/if}}

	{{if $contacts}}<div class="contacts" style="display:none;">{{$contacts}}</div>{{/if}}

	{{if $updated}}<div class="updated" style="display:none;">{{$updated}}</div>{{/if}}

	{{if $homepage}}<dl class="homepage"><dt class="homepage-label">{{$homepage}}</dt><dd class="homepage-url"><a href="{{$profile.homepage}}" class="u-url" rel="me" target="_blank" rel="noopener noreferrer">{{$profile.homepage}}</a>{{if $profile.homepage_verified}} <span title="{{$homepage_verified}}">✔</span>{{/if}}</dd></dl>{{/if}}

	{{if $about}}<dl class="about"><dt class="about-label">{{$about}}</dt><dd class="x-network" dir="auto">{{$profile.about nofilter}}</dd></dl>{{/if}}

	{{include file="diaspora_vcard.tpl"}}

	<div id="profile-extra-links">
		<ul>
			{{if $unfollow_link}}
				<li><a id="dfrn-request-link" href="{{$unfollow_link}}">{{$unfollow}}</a></li>
			{{/if}}
			{{if $follow_link}}
				<li><a id="dfrn-request-link" href="{{$follow_link}}">{{$follow}}</a></li>
			{{/if}}
			{{if $wallmessage_link}}
				<li><a id="wallmessage-link" href="{{$wallmessage_link}}">{{$wallmessage}}</a></li>
			{{/if}}
			{{if $subscribe_feed_link}}
				<li><a id="subscribe-feed-link" href="{{$subscribe_feed_link}}">{{$subscribe_feed}}</a></li>
			{{/if}}
		</ul>
	</div>
</div>

{{$contact_block nofilter}}
