
<h3>{{$title}}</h3>
<div id="identity-manage-desc">{{$desc}}</div>
<div id="identity-manage-choose">{{$choose}}</div>

<div id="identity-selector-wrapper" role="menu">
	<form action="manage" method="post" >

	{{foreach $identities as $id}}
		<div class="itentity-match-wrapper {{if $id.selected}}selected-identity{{/if}}" id="identity-match-{{$id.uid}}">
			<div class="identity-match-photo" id="identity-match-photo-{{$id.uid}}">
				<button name="identity" value="{{$id.uid}}" onclick="this.form.submit();" title="{{$id.username}}">
					<img src="{{$id.thumb}}" alt="{{$id.username}}" />
				</button>
			</div>

			<div class="identity-match-break"></div>

			<div class="identity-match-desc">
				<div class="identity-match-name" id="identity-match-name-{{$id.uid}}">
					{{if $id.selected}}{{$id.username}}{{else}}<a role="menuitem" href="manage?identity={{$id.uid}}">{{$id.username}}</a>{{/if}}
				</div>
				<div class="identity-match-details" id="identity-match-nick-{{$id.uid}}">({{$id.nickname}})</div>
			</div>

			<div class="identity-match-end"></div>
		</div>
	{{/foreach}}

	<div class="identity-match-break"></div>

	</form>
</div>
