<div class="vcard">

	<div class="fn label">$profile.name</div>
	
	{{ if $pdesc }}
    <div class="title">$profile.pdesc</div>
    {{ endif }}
	<div id="profile-photo-wrapper">
    <img class="photo" width="175" height="175" src="$profile.photo" alt="$profile.name" />
    </div>

	{{ if $location }}
		<div class="location">
        <span class="location-label">$location</span>
		<div class="adr">
			{{ if $profile.address }}
            <div class="street-address">$profile.address</div>{{ endif }}
			<span class="city-state-zip">$profile.zip</span>
            <span class="locality">$profile.locality</span>{{ if $profile.locality }}, {{ endif }}
            <span class="region">$profile.region</span>
            <span class="postal-code">$profile.postal-code</span>
			{{ if $profile.country-name }}<span class="country-name">$profile.country-name</span>{{ endif }}
		</div>
		</div>
	{{ endif }}

	{{ if $gender }}
    <div class="mf">
        <span class="gender-label">$gender</span>
        <span class="x-gender">$profile.gender</span>
    </div>
    {{ endif }}
	
	{{ if $profile.pubkey }}
    <div class="key" style="display:none;">$profile.pubkey</div>
    {{ endif }}

	{{ if $marital }}
    <div class="marital">
    <span class="marital-label">
    <span class="heart">&hearts;</span>$marital</span>
    <span class="marital-text">$profile.marital</span>
    </div>
    {{ endif }}

	{{ if $homepage }}
    <div class="homepage">
    <span class="homepage-label">$homepage</span>
    <span class="homepage-url"><a href="$profile.homepage"
    target="external-link">$profile.homepage</a></span>
    </div>{{ endif }}

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


