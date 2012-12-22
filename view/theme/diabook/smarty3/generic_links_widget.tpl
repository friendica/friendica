<div id="widget_{{$title}}">
	{{if $title}}<h3 style="border-bottom: 1px solid #D2D2D2;">{{$title}}</h3>{{/if}}
	{{if $desc}}<div class="desc">{{$desc}}</div>{{/if}}
	
	<ul  class="rs_tabs">
		{{foreach $items as $item}}
			<li><a href="{{$item.url}}" class="rs_tab button {{if $item.selected}}selected{{/if}}">{{$item.label}}</a></li>
		{{/foreach}}
	</ul>
	
</div>
