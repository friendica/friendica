<div class="widget">
	{{if $title}}<h3>$title</h3>{{endif}}
	{{if $desc}}<div class="desc">$desc</div>{{endif}}
	
	<ul>
		{{ for $items as $item }}
			<li class="tool {{ if $item.selected }}selected{{ endif }}"><a href="$item.url" class="link">$item.label</a></li>
		{{ endfor }}
	</ul>
	
</div>
