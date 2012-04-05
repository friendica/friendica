<div id="nets-sidebar" class="widget">
	<h3>$title</h3>
	<div id="nets-desc">$desc</div>
	<a href="$base" class="nets-link{{ if $sel_all }} nets-selected{{ endif }} nets-all">$all</a>
	<ul class="nets-ul">
	{{ for $nets as $net }}
	<li><a href="$base?nets=$net.ref" class="nets-link{{ if $net.selected }} nets-selected{{ endif }}">$net.name</a></li>
	{{ endfor }}
	</ul>
</div>
