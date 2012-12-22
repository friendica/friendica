<div class="widget">
	{{if $title}}<h3>{{$title}}</h3>{{/if}}
	{{if $desc}}<div class="desc">{{$desc}}</div>{{/if}}
	
	<ul>
		{{foreach $items as $item}}
			<li class="tool {{if $item.selected}}selected{{/if}}"><a href="{{$item.url}}" class="link">{{$item.label}}</a></li>
		{{/foreach}}
	</ul>
	
</div>
