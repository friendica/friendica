
<div class="directory-item" id="directory-item-$id" >
	<div class="directory-photo-wrapper" id="directory-photo-wrapper-$id" > 
		<div class="directory-photo" id="directory-photo-$id" >
			<a href="$profile-link" class="directory-profile-link" id="directory-profile-link-$id" >
				<img class="directory-photo-img photo" src="$photo" alt="$alt-text" title="$alt-text" />
			</a>
		</div>
	</div>
	<div class="directory-profile-wrapper" id="directory-profile-wrapper-$id" >
		<div class="contact-name" id="directory-name-$id">$name</div>
		<div class="page-type">$page-type</div>
		{{ if $pdesc }}<div class="directory-profile-title">$profile.pdesc</div>{{ endif }}
    	<div class="directory-detailcolumns-wrapper" id="directory-detailcolumns-wrapper-$id">
        	<div class="directory-detailscolumn-wrapper" id="directory-detailscolumn1-wrapper-$id">	
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
			</div>	
			<div class="directory-detailscolumn-wrapper" id="directory-detailscolumn2-wrapper-$id">	
				{{ if $marital }}<dl class="marital"><dt class="marital-label"><span class="heart">&hearts;</span>$marital</dt><dd class="marital-text">$profile.marital</dd></dl>{{ endif }}

				{{ if $homepage }}<dl class="homepage"><dt class="homepage-label">$homepage</dt><dd class="homepage-url"><a href="$profile.homepage" target="external-link">$profile.homepage</a></dd></dl>{{ endif }}
			</div>
		</div>
	  	<div class="directory-copy-wrapper" id="directory-copy-wrapper-$id" >
			{{ if $about }}<dl class="directory-copy"><dt class="directory-copy-label">$about</dt><dd class="directory-copy-data">$profile.about</dd></dl>{{ endif }}
  		</div>
	</div>
</div>
