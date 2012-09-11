<div class="widget{{ if $class }} $class{{ endif }}">
	{{if $title}}<h3>$title</h3>{{endif}}
	{{if $desc}}<div class="desc">$desc</div>{{endif}}
	
	<ul>
		{{ for $items as $item }}
			<li class="widget-list"><a href="$item.url" class="{{ if $item.selected }}selected{{ endif }}">$item.label</a></li>
		{{ endfor }}
	</ul>
	
</div>
