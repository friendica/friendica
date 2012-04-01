<div id="message-sidebar" class="widget">
	<div id="message-new" class="{{ if $new.sel }}selected{{ endif }}"><a href="$new.url">$new.label</a> </div>
	
	<ul class="message-ul">
		{{ for $tabs as $t }}
			<li class="tool {{ if $t.sel }}selected{{ endif }}"><a href="$t.url" class="message-link">$t.label</a></li>
		{{ endfor }}
	</ul>
	
</div>
