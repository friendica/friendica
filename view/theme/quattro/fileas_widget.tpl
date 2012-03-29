<div id="fileas-sidebar" class="widget">
	<h3>$title</h3>
	<div id="nets-desc">$desc</div>
	
	<ul class="fileas-ul">
		<li class="tool {{ if $sel_all }}selected{{ endif }}"><a href="$base" class="fileas-link fileas-all">$all</a></li>
		{{ for $terms as $term }}
			<li class="tool {{ if $term.selected }}selected{{ endif }}"><a href="$base?f=&file=$term.name" class="fileas-link">$term.name</a></li>
		{{ endfor }}
	</ul>
	
</div>
