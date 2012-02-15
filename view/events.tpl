$tabs
<h2>$title</h2>

<div id="new-event-link"><a href="$new_event.0" >$new_event.1</a></div>

<div id="event-calendar-wrapper">
	<a href="$previus.0" class="prevcal $previus.2"><div id="event-calendar-prev" class="icon s22 prev" title="$previus.1"></div></a>
	$calendar
	<a href="$next.0" class="nextcal $next.2"><div id="event-calendar-prev" class="icon s22 next" title="$next.1"></div></a>
</div>
<div class="event-calendar-end"></div>


{{ for $events as $event }}
	<div class="event">
	{{ if $event.is_first }}<hr /><a name="link-$event.j" ><div class="event-list-date">$event.d</div></a>{{ endif }}
	{{ if $event.item.author-name }}<a href="$event.item.author-link" ><img src="$event.item.author-avatar" height="32" width="32" />$event.item.author-name</a>{{ endif }}
	$event.html
	{{ if $event.item.plink }}<a href="$event.plink.0" title="$event.plink.1" target="external-link" class="plink-event-link icon s22 remote-link"></a>{{ endif }}
	{{ if $event.edit }}<a href="$event.edit.0" title="$event.edit.1" class="edit-event-link icon s22 pencil"></a>{{ endif }}
	</div>
	<div class="clear"></div>

{{ endfor }}
