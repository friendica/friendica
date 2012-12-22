{{foreach $events as $event}}
	<div class="event">
	
	{{if $event.item.author-name}}<a href="{{$event.item.author-link}}" ><img src="{{$event.item.author-avatar}}" height="32" width="32" />{{$event.item.author-name}}</a>{{/if}}
	{{$event.html}}
	{{if $event.item.plink}}<a href="{{$event.plink.0}}" title="{{$event.plink.1}}" target="external-link" class="plink-event-link icon s22 remote-link"></a>{{/if}}
	{{if $event.edit}}<a href="{{$event.edit.0}}" title="{{$event.edit.1}}" class="edit-event-link icon s22 pencil"></a>{{/if}}
	</div>
	<div class="clear"></div>
{{/foreach}}
