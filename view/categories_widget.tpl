<div class="clear"></div>
<div id="categories-sidebar" class="widget">
	<h3>$title</h3>
	<div id="nets-desc">$desc</div>
	
	<ul class="categories-ul">
		<li class="tool"><a href="$base" class="categories-link categories-all{{ if $sel_all }} categories-selected{{ endif }}">$all</a></li>
		{{ for $terms as $term }}
			<li class="tool"><a href="$base?f=&category=$term.name" class="categories-link{{ if $term.selected }} categories-selected{{ endif }}">$term.name</a></li>
		{{ endfor }}
	</ul>
	
</div>
