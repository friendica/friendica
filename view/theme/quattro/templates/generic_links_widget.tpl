<div class="widget">
	{{if $title}}<h3>{{$title}}</h3>{{/if}}
	{{if $desc}}<div class="desc">{{$desc}}</div>{{/if}}

	<ul role="menu">
		{{foreach $items as $item}}
			<li role="menuitem" class="tool {{if $item.selected}}selected{{/if}}"><a href="{{$item.url}}" {{if $item.accesskey}}accesskey="{{$item.accesskey}}"{{/if}} class="link">{{$item.label}}</a></li>
		{{/foreach}}
	</ul>

</div>
