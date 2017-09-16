
{{foreach $events as $event}}
	<div class="event">
	
	{{if $event.item.author_name}}<a href="{{$event.item.author_link}}" ><img src="{{$event.item.author_avatar}}" height="32" width="32" />{{$event.item.author_name}}</a>{{/if}}
	{{$event.html}}
	{{if $event.item.plink}}<a href="{{$event.plink.0}}" title="{{$event.plink.1}}" target="_blank" class="plink-event-link icon s22 remote-link"></a>{{/if}}
	{{if $event.edit}}<a href="{{$event.edit.0}}" title="{{$event.edit.1}}" class="edit-event-link icon s22 pencil"></a>{{/if}}
	{{if $event.drop}}<a href="{{$event.drop.0}}" onclick="return confirmDelete();" title="{{$event.drop.1}}" class="drop-event-link icon s22 delete"></a>{{/if}}
	</div>
	<div class="clear"></div>
{{/foreach}}
