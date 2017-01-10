<div class="shared-wrapper">
	<div class="shared_header">
		{{if $avatar}}
			<a href="{{$profile}}" target="_blank" class="shared-userinfo">
			<img src="{{$avatar}}" height="32" width="32">
			</a>
		{{/if}}
		{{*<span><a href="{{$profile}}" target="_blank" class="shared-wall-item-name">{{$author}}</a> wrote the following <a href="{{$link}}" target="_blank">post</a>{{$reldate}}:</span>*}}
		<div><a href="{{$profile}}" target="_blank" class="shared-wall-item-name"><span class="shared-author">{{$author}}</span></a></div>
		<div class="shared-wall-item-ago"><small><a href="{{$link}}" target="_blank"><span class="shared-time">{{$posted}}</a></a></small></div>
	</div>
	<blockquote class="shared_content">{{$content}}</blockquote>
</div>
