<div class="shared-wrapper">
	<div class="shared_header">
		{{if $avatar}}
			<a href="{{$profile}}" class="shared-userinfo">
			<img src="{{$avatar}}" height="32" width="32">
			</a>
		{{/if}}
		<div><a href="{{$profile}}" class="shared-wall-item-name"><span class="shared-author">{{$author}}</span></a></div>
		<div class="shared-wall-item-ago"><small><a href="{{$link}}"><span class="shared-time">{{$posted}}</a></a></small></div>
	</div>
	<blockquote class="shared_content">{{$content nofilter}}</blockquote>
</div>
