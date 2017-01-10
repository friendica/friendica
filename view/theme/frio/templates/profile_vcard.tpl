<div class="vcard h-card widget">

	{{if $profile.picdate}}
		<div id="profile-photo-wrapper" class="thumbnail"><a href="{{$profile.url}}"><img class="photo u-photo" src="{{$profile.photo}}?rev={{$profile.picdate}}" alt="{{$profile.name}}" /></a>
	{{else}}
		<div id="profile-photo-wrapper" class="thumbnail"><a href="{{$profile.url}}"><img class="photo u-photo" src="{{$profile.photo}}" alt="{{$profile.name}}" /></a>
	{{/if}}
			<div class="tool visible-lg visible-md">

			{{if $profile.edit}}
				<div class="action">
					<a class="" href="{{$profile.edit.0}}" title="{{$profile.edit.3}}"><i class="fa fa-pencil-square-o"></i></a>
				</div>
			{{else}}
				{{if $profile.menu}}
					<div class="profile-edit-side-div"><a class="profile-edit-side-link icon edit" title="{{$editprofile}}" href="profiles" ></a></div>
				{{/if}}
			{{/if}}
			</div>
		
		</div>

	{{* The short information which will appended to the second navbar by scrollspy *}}
	<div id="vcard-short-info-wrapper" style="display: none;">
		<div id="vcard-short-info" class="media" style="display: none">
			<div id="vcard-short-photo-wrapper" class="pull-left">
				<img class="media-object" src="{{$profile.photo}}" alt="{{$profile.name}}" />
			</div>

			<div id="vcard-short-desc" class="media-body">
				<h4 class="media-heading">{{$profile.name}}</h4>
				{{if $profile.addr}}<div class="vcard-short-addr">{{$profile.addr}}</div>{{/if}}
			</div>
		</div>
	</div>

	<div class="panel-body">
		<div class="profile-header">
			<div class="fn p-name">{{$profile.name}}</div>

			{{if $profile.addr}}<div class="p-addr">{{$profile.addr}}</div>{{/if}}

			{{if $profile.pdesc}}<div class="title">{{$profile.pdesc}}</div>{{/if}}
		</div>

		<div id="profile-extra-links">
				{{if $connect}}
					<div id="dfrn-request-link-button">
					{{if $remoteconnect}}
						<a id="dfrn-request-link" class="btn btn-primary btn-sm" href="{{$remoteconnect}}">
							<span class=""><i class="fa fa-user-plus"></i></span>
							<span class="">{{$connect}}</span>
						</a>
					{{else}}
						<a id="dfrn-request-link" class="btn btn-labeled btn-primary btn-sm" href="dfrn_request/{{$profile.nickname}}">
							<span class=""><i class="fa fa-user-plus"></i></span>
							<span class="">{{$connect}}</span>
						</a>
					{{/if}}
					</div>
				{{/if}}
				{{if $wallmessage}}
				<div id="wallmessage-link-botton">
					<a id="wallmessage-link" class="btn btn-labeled btn-primary btn-sm" onclick="openWallMessage('{{$wallmessage_link}}')">
						<span class=""><i class="fa fa-envelope"></i></span>
						<span class="">{{$wallmessage}}</span>
					</a>
				</div>
				{{/if}}
			
		</div>

		<div class="clear"></div>

		{{if $location}}
			<div class="location detail">
				<span class="location-label icon"><i class="fa fa-map-marker"></i></span> 
				<span class="adr">
					{{if $profile.address}}<span class="street-address p-street-address">{{$profile.address}}</span>{{/if}}
					<span class="city-state-zip">
						<span class="locality p-locality">{{$profile.locality}}</span>{{if $profile.locality}}, {{/if}}
						<span class="region p-region">{{$profile.region}}</span>
						<span class="postal-code p-postal-code">{{$profile.postal_code}}</span>
					</span>
					{{if $profile.country_name}}<span class="country-name p-country-name">{{$profile.country_name}}</span>{{/if}}
				</span>
			</div>
		{{/if}}

		{{if $profile.xmpp}}
			<div class="xmpp">
				<span class="xmpp-label icon"><i class="fa fa-comments"></i></span> 
				<span class="xmpp-data">{{$profile.xmpp}}</span>
			</div>
		{{/if}}

		{{if $gender}}
		<div class="mf detail">
			<span class="gender-label icon"><i class="fa fa-venus-mars"></i></span>
			<span class="p-gender">{{$profile.gender}}</span>
		</div>
		{{/if}}

		{{if $profile.pubkey}}<div class="key u-key" style="display:none;">{{$profile.pubkey}}</div>{{/if}}

		{{if $contacts}}<div class="contacts" style="display:none;">{{$contacts}}</div>{{/if}}

		{{if $updated}}<div class="updated" style="display:none;">{{$updated}}</div>{{/if}}

		{{if $marital}}
		<div class="marital detail">
			<span class="marital-label icon"><i class="fa fa-heart"></i></span>
			<span class="marital-text icon">{{$profile.marital}}</span>
		</div>
		{{/if}}

		{{if $homepage}}
		<div class="homepage detail">
			<span class="homepage-label icon"><i class="fa fa-external-link-square"></i></span>
			<span class="homepage-url u-url"><a href="{{$profile.homepage}}" rel="me" target="_blank">{{$profile.homepage}}</a></span>
		</div>
		{{/if}}

		{{if $about}}<dl class="about"  style="display:none;"><dt class="about-label">{{$about}}</dt><dd class="x-network">{{$profile.about}}</dd></dl>{{/if}}

		{{include file="diaspora_vcard.tpl"}}
	</div>

</div>

{{if $contact_block}}
<div class="widget" id="widget-contacts">
	{{$contact_block}}
</div>
{{/if}}
