{{if $direction.direction > 0}}
<span class="direction">
	&bull;
	{{if $direction.direction == 1}}
		<i class="icon-inbox" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 2}}
		<i class="icon-download" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 3}}
		<i class="icon-retweet" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 4}}
		<i class="icon-tag" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 5}}
		<i class="icon-commenting" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 6}}
		<i class="icon-user" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 7}}
		<i class="icon-forward" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 8}}
		<i class="icon-share" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 9}}
		<i class="icon-globe" aria-hidden="true" title="{{$direction.title}}"></i>
	{{elseif $direction.direction == 10}}
		<i class="icon-inbox" aria-hidden="true" title="{{$direction.title}}"></i>
	{{/if}}
</span>
{{/if}}
