<div id="fileas-sidebar" class="widget">
	<h3>$title</h3>
	<div id="nets-desc">$desc</div>
	
	<ul class="fileas-ul">
		<li class="tool"><a href="$base" class="fileas-link fileas-all{{ if $sel_all }} fileas-selected{{ endif }}">$all</a></li>
		{{ for $terms as $term }}
			<li class="tool"><a href="$base?f=&file=$term.name" class="fileas-link{{ if $term.selected }} fileas-selected{{ endif }}">$term.name</a></li>
		{{ endfor }}
	</ul>
	
</div>
