<div class="profile-match-wrapper">
	<div class="profile-match-photo">
		<a href="$url">
			<img src="$photo" alt="$name" />
		</a>
	</div>
	<span><a href="$url">$name</a>$inttxt<br />$tags</span>
	<div class="profile-match-break"></div>
	{{ if $connlnk }}
	<div class="profile-match-connect"><a href="$connlnk" title="$conntxt">$conntxt</a></div>
	{{ endif }}
	<div class="profile-match-end"></div>
</div>