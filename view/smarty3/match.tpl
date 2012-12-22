<div class="profile-match-wrapper">
	<div class="profile-match-photo">
		<a href="{{$url}}">
			<img src="{{$photo}}" alt="{{$name}}" title="{{$name}}[{{$tags}}]" />
		</a>
	</div>
	<div class="profile-match-break"></div>
	<div class="profile-match-name">
		<a href="{{$url}}" title="{{$name}}[{{$tags}}]">{{$name}}</a>
	</div>
	<div class="profile-match-end"></div>
	{{if $connlnk}}
	<div class="profile-match-connect"><a href="{{$connlnk}}" title="{{$conntxt}}">{{$conntxt}}</a></div>
	{{/if}}

</div>
