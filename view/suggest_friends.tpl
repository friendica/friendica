<div class="profile-match-wrapper">
	<a href="$ignlnk" title="$ignore" class="icon drophide profile-match-ignore" onmouseout="imgdull(this);" onmouseover="imgbright(this);" onclick="return confirmDelete();" ></a>
	<div class="profile-match-photo">
		<a href="$url">
			<img src="$photo" alt="$name" width="80" height="80" title="$name [$url]" />
		</a>
	</div>
	<div class="profile-match-break"></div>
	<div class="profile-match-name">
		<a href="$url" title="$name">$name</a>
	</div>
	<div class="profile-match-end"></div>
	{{ if $connlnk }}
	<div class="profile-match-connect"><a href="$connlnk" title="$conntxt">$conntxt</a></div>
	{{ endif }}
</div>