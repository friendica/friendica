<div class="vcard">

	<div class="fn label">$profile.name</div>
	
				
	
	{{ if $pdesc }}<div class="title">$profile.pdesc</div>{{ endif }}
	<div id="profile-photo-wrapper"><img class="photo" width="175" height="175" src="$profile.photo?rev=$profile.picdate" alt="$profile.name"></div>



	{{ if $location }}
		<dl class="location"><dt class="location-label">$location</dt> 
		<dd class="adr">
			{{ if $profile.address }}<div class="street-address">$profile.address</div>{{ endif }}
			<span class="city-state-zip">
				<span class="locality">$profile.locality</span>{{ if $profile.locality }}, {{ endif }}
				<span class="region">$profile.region</span>
				<span class="postal-code">$profile.postal-code</span>
			</span>
			{{ if $profile.country-name }}<span class="country-name">$profile.country-name</span>{{ endif }}
		</dd>
		</dl>
	{{ endif }}

	{{ if $gender }}<dl class="mf"><dt class="gender-label">$gender</dt> <dd class="x-gender">$profile.gender</dd></dl>{{ endif }}
	
	{{ if $profile.pubkey }}<div class="key" style="display:none;">$profile.pubkey</div>{{ endif }}

	{{ if $marital }}<dl class="marital"><dt class="marital-label"><span class="heart">&hearts;</span>$marital</dt><dd class="marital-text">$profile.marital</dd></dl>{{ endif }}

	{{ if $homepage }}<dl class="homepage"><dt class="homepage-label">$homepage</dt><dd class="homepage-url"><a href="$profile.homepage" target="external-link">$profile.homepage</a></dd></dl>{{ endif }}

	{{ inc diaspora_vcard.tpl }}{{ endinc }}
	
	<div id="profile-extra-links">
		<ul>
			{{ if $connect }}
				<li><a id="dfrn-request-link" href="dfrn_request/$profile.nickname">$connect</a></li>
			{{ endif }}
		</ul>
	</div>
</div>

$contact_block


