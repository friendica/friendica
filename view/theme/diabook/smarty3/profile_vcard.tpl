<div class="vcard">

	<div class="tool">
		<div class="fn label">{{$profile.name}}</div>
		{{if $profile.edit}}
			<div class="action">
			<a class="icon s16 edit ttright" href="#" rel="#profiles-menu" title="{{$profile.edit.3}}"><span>{{$profile.edit.1}}</span></a>
			<ul id="profiles-menu" class="menu-popup">
				{{foreach $profile.menu.entries as $e}}
				<li>
					<a href="profiles/{{$e.id}}"><img src='{{$e.photo}}'>{{$e.profile_name}}</a>
				</li>
				{{/foreach}}
				<li><a href="profile_photo" >{{$profile.menu.chg_photo}}</a></li>
				<li><a href="profiles/new" id="profile-listing-new-link">{{$profile.menu.cr_new}}</a></li>
				<li><a href="profiles" >{{$profile.edit.3}}</a></li>
								
			</ul>
			</div>
		{{/if}}
	</div>
				
	

	<div id="profile-photo-wrapper"><img class="photo" src="{{$profile.photo}}?rev={{$profile.picdate}}" alt="{{$profile.name}}" /></div>
	{{if $pdesc}}<div class="title">{{$profile.pdesc}}</div>{{/if}}


	{{if $location}}
		<dl class="location"><dt class="location-label">{{$location}}</dt><br> 
		<dd class="adr">
			{{if $profile.address}}<div class="street-address">{{$profile.address}}</div>{{/if}}
			<span class="city-state-zip">
				<span class="locality">{{$profile.locality}}</span>{{if $profile.locality}}, {{/if}}
				<span class="region">{{$profile.region}}</span>
				<span class="postal-code">{{$profile.postal-code}}</span>
			</span>
			{{if $profile.country-name}}<span class="country-name">{{$profile.country-name}}</span>{{/if}}
		</dd>
		</dl>
	{{/if}}

	{{if $gender}}<dl class="mf"><dt class="gender-label">{{$gender}}</dt> <dd class="x-gender">{{$profile.gender}}</dd></dl>{{/if}}
	
	{{if $profile.pubkey}}<div class="key" style="display:none;">{{$profile.pubkey}}</div>{{/if}}

	{{if $marital}}<dl class="marital"><dt class="marital-label"><span class="heart">&hearts;</span>{{$marital}}</dt><dd class="marital-text">{{$profile.marital}}</dd></dl>{{/if}}

	{{if $homepage}}<dl class="homepage"><dt class="homepage-label">{{$homepage}}</dt><dd class="homepage-url"><a href="{{$profile.homepage}}" target="external-link">{{$profile.homepage}}</a></dd></dl>{{/if}}

	{{include file="diaspora_vcard.tpl" diaspora=$diaspora_info}}
	
	<div id="profile-extra-links">
		<ul>
			{{if $connect}}
				<li><a id="dfrn-request-link" href="dfrn_request/{{$profile.nickname}}">{{$connect}}</a></li>
			{{/if}}
		</ul>
	</div>
</div>

{{$contact_block}}


