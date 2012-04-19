<div id="widget_$title">
	{{if $title}}<h3 style="border-bottom: 1px solid #D2D2D2;">$title</h3>{{endif}}
	{{if $desc}}<div class="desc">$desc</div>{{endif}}
	
	<ul  class="rs_tabs">
		{{ for $items as $item }}
			<li><a href="$item.url" class="rs_tab button {{ if $item.selected }}selected{{ endif }}">$item.label</a></li>
		{{ endfor }}
	</ul>
	
</div>
