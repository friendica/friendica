
<div id="remote-friends-in-common" class="bigwidget">
	<div id="rfic-desc">{{$desc}} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{if $linkmore}}<a href="{{$base}}/common/rem/{{$uid}}/{{$cid}}">{{$more}}</a>{{/if}}</div>
	{{if $items}}
	{{foreach $items as $item}}
	<div class="profile-match-wrapper">
		<div class="profile-match-photo">
			<a href="{{$item.url}}">
				<img src="{{$item.photo}}" width="80" height="80" alt="{{$item.name}}" title="{{$item.name}}" />
			</a>
		</div>
		<div class="profile-match-break"></div>
		<div class="profile-match-name">
			<a href="{{$item.url}}" title="{{$item.name}}">{{$item.name}}</a>
		</div>
		<div class="profile-match-end"></div>
	</div>
	{{/foreach}}
	{{/if}}
	<div id="rfic-end" class="clear"></div>
</div>

