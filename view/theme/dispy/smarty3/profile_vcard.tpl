<div class="vcard">

	{{if $profile.edit}}
	<div class="action">
	<span class="icon-profile-edit" rel="#profiles-menu"></span>
	<a href="#" rel="#profiles-menu" class="ttright" id="profiles-menu-trigger" title="{{$profile.edit.3}}">{{$profile.edit.1}}</a>
	<ul id="profiles-menu" class="menu-popup">
		{{foreach $profile.menu.entries as $e}}
		<li>
			<a href="profiles/{{$e.id}}"><img src='{{$e.photo}}'>{{$e.profile_name}}</a>
		</li>
		{{/foreach}}
		<li><a href="profile_photo">{{$profile.menu.chg_photo}}</a></li>
		<li><a href="profiles/new" id="profile-listing-new-link">{{$profile.menu.cr_new}}</a></li>
	</ul>
	</div>
	{{/if}}

	<div class="fn label">{{$profile.name}}</div>

	{{if $pdesc}}
    <div class="title">{{$profile.pdesc}}</div>
    {{/if}}
	<div id="profile-photo-wrapper">
		<img class="photo" width="175" height="175" src="{{$profile.photo}}?rev={{$profile.picdate}}" alt="{{$profile.name}}" />
    </div>

	{{if $location}}
		<div class="location">
        <span class="location-label">{{$location}}</span>
		<div class="adr">
			{{if $profile.address}}
            <div class="street-address">{{$profile.address}}</div>{{/if}}
			<span class="city-state-zip">
				<span class="locality">{{$profile.locality}}</span>{{if $profile.locality}}, {{/if}}
				<span class="region">{{$profile.region}}</span>
				<span class="postal-code">{{$profile.postal-code}}</span>
			</span>
			{{if $profile.country-name}}<span class="country-name">{{$profile.country-name}}</span>{{/if}}
		</div>
		</div>
	{{/if}}

	{{if $gender}}
    <div class="mf">
        <span class="gender-label">{{$gender}}</span>
        <span class="x-gender">{{$profile.gender}}</span>
    </div>
    {{/if}}
	
	{{if $profile.pubkey}}
    <div class="key" style="display:none;">{{$profile.pubkey}}</div>
    {{/if}}

	{{if $marital}}
    <div class="marital">
    <span class="marital-label">
    <span class="heart">&hearts;</span>{{$marital}}</span>
    <span class="marital-text">{{$profile.marital}}</span>
    </div>
    {{/if}}

	{{if $homepage}}
    <div class="homepage">
    <span class="homepage-label">{{$homepage}}</span>
    <span class="homepage-url"><a href="{{$profile.homepage}}"
    target="external-link">{{$profile.homepage}}</a></span>
    </div>{{/if}}

	{{include file="diaspora_vcard.tpl"}}
	
	<div id="profile-extra-links">
		<ul>
			{{if $connect}}
				<li><a id="dfrn-request-link" href="dfrn_request/{{$profile.nickname}}">{{$connect}}</a></li>
			{{/if}}
		</ul>
	</div>
</div>

{{$contact_block}}

